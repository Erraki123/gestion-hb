<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WhatsAppInboundMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UltraMsgWebhookController extends Controller
{
    public function receive(Request $request, string $secret): JsonResponse
    {
        $configured = (string) config('ultramsg_inbox.webhook_secret', '');
        if (strlen($configured) < 32 || ! hash_equals($configured, $secret)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (! $request->isJson() || strlen($request->getContent()) > 32768) {
            return response()->json(['error' => 'Invalid body'], 422);
        }
        $payload = $request->json()->all();
        if (($payload['event_type'] ?? null) !== 'message_received') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }
        $instance = (string) config('ultramsg_inbox.instance_id', '');
        if (! preg_match('/^instance([0-9]+)$/', $instance, $parts) ||
            ! hash_equals($parts[1], (string) ($payload['instanceId'] ?? ''))) {
            return response()->json(['error' => 'Instance mismatch'], 403);
        }
        $data = $payload['data'] ?? null;
        if (! is_array($data) || ($data['fromMe'] ?? null) !== false || ($data['type'] ?? '') !== 'chat') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }
        $providerId = $data['id'] ?? null;
        $senderRaw = $data['from'] ?? null;
        $body = $data['body'] ?? null;
        if (! is_string($providerId) || strlen($providerId) < 3 || strlen($providerId) > 200 ||
            ! is_string($senderRaw) || ! preg_match('/^[0-9]{10,15}@c\.us$/', $senderRaw) ||
            ! is_string($body) || trim($body) === '' || mb_strlen($body) > 10000) {
            return response()->json(['error' => 'Invalid message'], 422);
        }
        $phone = explode('@', $senderRaw, 2)[0];
        // Reconciliation: compare normalized professional numbers; avoid false positives.
        $clientId = null;
        $candidates = Client::withoutGlobalScope('owner')->whereNotNull('phone')
            ->select(['id', 'phone'])->cursor();
        foreach ($candidates as $client) {
            if (WhatsAppService::normalize($client->phone) === $phone) {
                if ($clientId !== null) {
                    // Shared phone: leave unassigned for director review.
                    $clientId = null;
                    break;
                }
                $clientId = $client->id;
            }
        }
        $receivedAt = null;
        if (isset($data['time']) && is_numeric($data['time'])) {
            $time = (int) $data['time'];
            if ($time > 946684800 && $time < now()->addDay()->timestamp) {
                $receivedAt = \Carbon\Carbon::createFromTimestamp($time);
            }
        }
        // Unique provider ID makes repeated webhook deliveries idempotent.
        DB::table('whatsapp_inbound_messages')->insertOrIgnore([
            'provider_message_id' => $providerId,
            'client_id' => $clientId,
            'sender' => $phone,
            'body' => $body,
            'received_at' => $receivedAt ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }
}
