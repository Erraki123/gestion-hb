<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HnbDirectionNotificationCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HnbDirectionNotificationsController extends Controller
{
    public function index(Request $request, HnbDirectionNotificationCenter $center): View
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $alerts = $center->withSeen((int) $request->user()->id);
        $unread = count(array_filter($alerts, fn ($a) => ! $a['seen']));
        return view('admin.direction-notifications', compact('alerts', 'unread'));
    }

    public function seen(Request $request, HnbDirectionNotificationCenter $center): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:90', 'regex:/^[a-z_]+:[0-9]+$/'],
            'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ]);
        $active = $center->active();
        $alert = $active[$data['key']] ?? null;
        abort_unless($alert && hash_equals($alert['fingerprint'], $data['fingerprint']), 409,
            'Alerte expirée : actualisez la page.');

        DB::table('hnb_direction_alert_reads')->updateOrInsert(
            ['user_id' => $request->user()->id, 'alert_key' => $alert['key']],
            ['fingerprint' => $alert['fingerprint'], 'seen_at' => now(), 'updated_at' => now()]
        );
        return redirect()->route('admin.direction.notifications.index')->with('success', 'Notification marquée comme vue. Le dossier reste à traiter.');
    }
}
