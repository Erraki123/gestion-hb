<?php
namespace App\Services;
use App\Models\{Client,User,HnbEmailMessage as Outbound};
use Illuminate\Support\Facades\{DB,Mail};
use Illuminate\Validation\ValidationException;
class HnbEmailConversation
{
    public const TEMPLATES=[
        'ack_fr'=>['Votre demande — HN BLOOM','Bonjour, merci pour votre message. Nous allons examiner votre demande et revenir vers vous.'],
        'info_fr'=>['Précisions sur votre demande — HN BLOOM','Bonjour, pourriez-vous préciser votre besoin afin que nous puissions vous renseigner ? Merci.'],
        'ack_ar'=>['طلبكم — HN BLOOM','السلام عليكم، شكراً على رسالتكم. سنراجع طلبكم ونعود إليكم قريباً.'],
    ];
    public function clients(User $user) {
        abort_unless($user->is_active && ($user->isAdmin() || $user->isCommercial()),403);
        return Client::withoutGlobalScope('owner')->when(!$user->isAdmin(),fn($q)=>$q->where('commercial_id',$user->id));
    }
    public function enabled(): bool {
        return (bool)config('notify.email.enabled') && config('mail.default')==='smtp' && config('mail.mailers.smtp.transport')==='smtp';
    }
    public function recipient(Client $client): ?string {
        $email=trim((string)$client->email);
        if (strlen($email)>254 || !filter_var($email,FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/',$email)) return null;
        if ($client->prospectDetail()->where('do_not_contact',true)->exists()) return null;
        // Shared addresses require correction by a human before sending.
        if (Client::withoutGlobalScope('owner')->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($email)])->where('id','!=',$client->id)->exists()) return null;
        return $email;
    }
    public function create(User $actor,Client $client,array $data): Outbound {
        $client=$this->clients($actor)->findOrFail($client->id);
        $to=$this->recipient($client);
        if (!$to) throw ValidationException::withMessages(['body'=>'Adresse absente, partagée, invalide ou contact opposé aux communications.']);
        $preset=$data['preset']??null;
        [$subject,$body]=$preset ? self::TEMPLATES[$preset] : [trim($data['subject']??''),trim($data['body']??'')];
        if ($subject==='' || $body==='' || preg_match('/[\r\n]/',$subject)) throw ValidationException::withMessages(['subject'=>'Objet et message requis, objet sur une seule ligne.']);
        $direct=$actor->isAdmin() || $preset!==null;
        if ($direct && !$this->enabled()) throw ValidationException::withMessages(['body'=>'Envoi SMTP désactivé. Aucun email envoyé.']);
        $row=Outbound::firstOrCreate(['request_key'=>$data['request_key']],['client_id'=>$client->id,'created_by'=>$actor->id,'recipient'=>$to,'subject'=>$subject,'body'=>$body,'status'=>$direct?'ready':'pending','decided_by'=>$actor->isAdmin()?$actor->id:null,'decided_at'=>$actor->isAdmin()?now():null]);
        abort_unless((int)$row->created_by===(int)$actor->id && (int)$row->client_id===(int)$client->id && $row->recipient===$to && $row->subject===$subject && $row->body===$body,409);
        if ($direct) $this->deliver($row);
        return $row->refresh();
    }
    public function decide(User $admin,int $id,bool $approve): Outbound {
        abort_unless($admin->isAdmin() && $admin->is_active,403);
        if ($approve && !$this->enabled()) throw ValidationException::withMessages(['body'=>'SMTP désactivé. Demande conservée.']);
        $row=DB::transaction(function() use($id,$admin,$approve){
            $row=Outbound::query()->lockForUpdate()->findOrFail($id);
            if ($row->status==='pending') $row->update(['status'=>$approve?'ready':'rejected','decided_by'=>$admin->id,'decided_at'=>now()]);
            return $row;
        });
        if ($approve && $row->status==='ready') $this->deliver($row);
        return $row->refresh();
    }
    private function deliver(Outbound $row): void {
        if (!Outbound::whereKey($row->id)->where('status','ready')->update(['status'=>'sending','attempted_at'=>now()])) return;
        try {
            $origin=User::find($row->created_by);
            $client=$origin && $origin->is_active && ($origin->isCommercial()||$origin->isAdmin()) ? $this->clients($origin)->find($row->client_id):null;
            $director=$row->decided_by ? User::find($row->decided_by):null;
            if (!$client || $this->recipient($client)!==$row->recipient || ($row->decided_by && (!$director || !$director->is_active || !$director->isAdmin())) || !$this->enabled()) {
                $row->update(['status'=>'blocked','reason'=>'Contact, autorisation, opposition ou SMTP modifié.']); return;
            }
            // No attachments, arbitrary recipients, HTML, CC, BCC or automatic retries in this phase.
            $sent=Mail::raw($row->body, function($message) use($row){ $message->to($row->recipient)->subject($row->subject); });
            $row->update(['status'=>$sent!==null?'submitted':'unconfirmed','submitted_at'=>$sent!==null?now():null,'reason'=>$sent===null?'Résultat SMTP absent : vérifier avant tout nouvel envoi.':null]);
        } catch (\Throwable $e) {
            // Outcome can be uncertain even when the transport throws. Never automatically resend.
            $row->update(['status'=>'unconfirmed','reason'=>'Résultat SMTP non confirmé. Vérifier avant tout nouvel envoi.']);
        }
    }
}
