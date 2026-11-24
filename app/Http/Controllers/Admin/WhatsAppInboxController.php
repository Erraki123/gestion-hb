<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppInboundMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppInboxController extends Controller
{
    public function index(): View
    {
        $messages = WhatsAppInboundMessage::with('client:id,name,company')
            ->orderByDesc('received_at')->paginate(20);
        $unreadCount = WhatsAppInboundMessage::whereNull('read_at')->count();
        return view('admin.whatsapp-inbox', compact('messages', 'unreadCount'));
    }

    public function markRead(Request $request, WhatsAppInboundMessage $message): RedirectResponse
    {
        if ($message->read_at === null) {
            $message->forceFill(['read_at' => now()])->save();
        }
        return redirect()->route('admin.whatsapp.inbox')->with('success', 'Message marqué comme lu.');
    }
}
