<?php
namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Models\WhatsAppInboundMessage;
use App\Models\HnbWhatsAppOutboundMessage as Outbound;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HnbWhatsAppConversation
{
    public const TEMPLATES = [
        'ack_fr'=>'Bonjour, merci pour votre message. Nous allons examiner votre demande et revenir vers vous.',
        'info_fr'=>'Bonjour, merci de nous préciser votre besoin afin que nous puissions vous renseigner.',
        'ack_ar'=>'السلام عليكم، شكراً على رسالتكم. سنراجع طلبكم ونعود إليكم قريباً.',
        'info_ar'=>'السلام عليكم، المرجو توضيح طلبكم حتى نتمكن من مساعدتكم.',
    ];

    public function __construct(private HnbWhatsAppMessageAccess $access, private WhatsAppService $whatsapp) {}

    // Revalidate the current link, even for the director; no arbitrary destination.
    public function target(User $user, int $id): WhatsAppInboundMessage
    {
        $message = $this->access->query($user)->findOrFail($id);
        if (!$this->validTarget($message)) {
            throw ValidationException::withMessages(['body'=>'Contact non rattaché, numéro modifié ou partagé. Vérifiez la fiche client avant tout envoi.']);
        }
        return $message;
    }

    public function validTarget(WhatsAppInboundMessage $message): bool
    {
        $number = WhatsAppService::normalize($message->sender);
        if (!$message->client_id || !$number || $number !== $message->sender) return false;
        $matched = null;
        foreach (Client::withoutGlobalScope('owner')->whereNotNull('phone')->select(['id','phone'])->cursor() as $client) {
            if (WhatsAppService::normalize($client->phone) !== $number) continue;
            if ($matched !== null) return false;
            $matched = (int) $client->id;
        }
        return $matched === (int) $message->client_id;
    }

    public function create(User $user, WhatsAppInboundMessage $message, array $data): Outbound
    {
        $preset = $data['preset'] ?? null;
        $body = $preset ? self::TEMPLATES[$preset] : trim($data['body'] ?? '');
        if ($body === '') throw ValidationException::withMessages(['body'=>'Message vide.']);
        $direct = $user->isAdmin() || $preset !== null;
        if ($direct && !$this->whatsapp->enabled()) throw ValidationException::withMessages(['body'=>'WhatsApp non configuré. Aucun envoi effectué.']);
        $row = Outbound::firstOrCreate(['request_key'=>$data['request_key']], [
            'inbound_id'=>$message->id, 'client_id'=>$message->client_id,
            'created_by'=>$user->id,'recipient'=>$message->sender,'body'=>$body,
            // A persisted draft has no effect until a human explicitly submits it.
            'status'=>$direct ? 'ready' : 'pending',
            'decided_by'=>$user->isAdmin() ? $user->id : null,
            'decided_at'=>$user->isAdmin() ? now() : null,
        ]);
        abort_unless((int)$row->created_by === (int)$user->id && (int)$row->inbound_id === (int)$message->id && $row->recipient === $message->sender && (int)$row->client_id === (int)$message->client_id && $row->body === $body, 409);
        if ($direct) $this->deliver($row, $user);
        return $row->refresh();
    }

    public function decide(User $admin, int $id, bool $approve): Outbound
    {
        abort_unless($admin->isAdmin(),403);
        if ($approve && !$this->whatsapp->enabled()) throw ValidationException::withMessages(['body'=>'WhatsApp non configuré. La demande reste en attente.']);
        $row = DB::transaction(function () use ($admin,$id,$approve) {
            $row=Outbound::query()->lockForUpdate()->findOrFail($id);
            if ($row->status !== 'pending') return $row;
            $row->update(['status'=>$approve?'ready':'rejected','decided_by'=>$admin->id,'decided_at'=>now()]);
            return $row;
        });
        if ($approve && $row->status === 'ready') $this->deliver($row,$admin);
        return $row->refresh();
    }

    private function deliver(Outbound $row, User $actor): void
    {
        // Claim exactly once. A crashed worker is left in 'sending', never retried blindly.
        $claimed=Outbound::whereKey($row->id)->where('status','ready')->update(['status'=>'sending','attempted_at'=>now()]);
        if (!$claimed) return;
        try {
            $message=WhatsAppInboundMessage::find($row->inbound_id);
            $origin=User::find($row->created_by);
            if (!$message || !$origin || !$origin->is_active || !$this->validTarget($message)
                || (int)$message->client_id !== (int)$row->client_id || $message->sender !== $row->recipient
                || !$this->access->query($origin)->whereKey($message->id)->exists()
                || !$this->access->query($actor)->whereKey($message->id)->exists()) {
                $row->update(['status'=>'blocked','reason'=>'Contact ou autorisation modifié depuis la préparation.']);
                return;
            }
            if (!$this->whatsapp->enabled()) {
                $row->update(['status'=>'blocked','reason'=>'WhatsApp non configuré au moment de l’envoi.']);
                return;
            }
            $result=$this->whatsapp->sendText($row->recipient,$row->body,'hnb-wa-'.$row->request_key);
            $providerId=trim((string)($result['message_id'] ?? ''));
            $row->update(['status'=>$providerId!==''?'accepted':'unconfirmed','provider_message_id'=>$providerId!==''?substr($providerId,0,200):null,'reason'=>$providerId===''?'Identifiant fournisseur absent. Vérifier avant tout nouvel envoi.':null]);
        } catch (\Throwable $e) {
            // Never record credentials, remote raw bodies, or automatically repeat an uncertain send.
            $row->update(['status'=>'unconfirmed','reason'=>'Envoi non confirmé. Vérifier le journal fournisseur avant tout nouvel envoi.']);
        }
    }
}
