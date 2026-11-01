<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function index()
    {
        $last   = $this->lastBackup();
        $checks = $this->checks($last);

        return view('system.index', compact('last', 'checks'));
    }

    public function backup()
    {
        set_time_limit(300);

        try {
            $code = Artisan::call('backup:run', ['--only-db' => true]);
        } catch (\Throwable $e) {
            report($e);
            $code = 1;
        }

        ActivityLogger::log(
            'backup',
            $code === 0 ? 'Sauvegarde manuelle de la base de données' : 'Échec de la sauvegarde manuelle',
            null,
            ['data' => ['code' => $code]]
        );

        return $code === 0
            ? back()->with('success', 'Sauvegarde de la base créée.')
            : back()->withErrors(['backup' => 'La sauvegarde a échoué. Consultez storage/logs/laravel.log.']);
    }

    private function lastBackup(): ?array
    {
        try {
            $name = config('backup.backup.destination.disks')[0] ?? 'backups';
            $disk = Storage::disk($name);

            $files = collect($disk->files(config('backup.backup.name')))
                ->filter(fn ($f) => str_ends_with($f, '.zip'));

            if ($files->isEmpty()) {
                return null;
            }

            $latest = $files->sortByDesc(fn ($f) => $disk->lastModified($f))->first();

            return [
                'name'  => basename($latest),
                'at'    => Carbon::createFromTimestamp($disk->lastModified($latest)),
                'size'  => $disk->size($latest),
                'count' => $files->count(),
                'disk'  => $name,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function checks(?array $last): array
    {
        $admin       = User::where('email', 'admin@example.com')->first();
        $defaultPass = $admin && Hash::check('ChangeMoi123!', $admin->password);
        $fails       = ActivityLog::where('action', 'login_failed')->where('created_at', '>=', now()->subDay())->count();

        $norm       = fn ($p) => str_replace('\\', '/', (string) $p);
        $backupRoot = $norm(config('filesystems.disks.backups.root'));
        $outside    = $backupRoot !== '' && ! str_starts_with($backupRoot, $norm(base_path()));

        return [
            ['label' => 'Mot de passe admin par défaut changé', 'ok' => ! $defaultPass, 'level' => 'danger',
             'detail' => $defaultPass ? 'Le compte admin@example.com utilise encore le mot de passe d\'origine : changez-le immédiatement.' : 'OK'],
            ['label' => 'Sauvegarde récente (moins de 26 h)', 'ok' => $last && $last['at']->gt(now()->subHours(26)), 'level' => 'danger',
             'detail' => $last ? 'Dernière : ' . $last['at']->format('d/m/Y H:i') : 'Aucune sauvegarde trouvée.'],
            ['label' => 'Mode débogage désactivé', 'ok' => ! config('app.debug'), 'level' => 'danger',
             'detail' => config('app.debug') ? 'APP_DEBUG=true : à désactiver en production.' : 'OK'],
            ['label' => 'Environnement de production', 'ok' => app()->isProduction(), 'level' => 'warning',
             'detail' => 'APP_ENV=' . config('app.env')],
            ['label' => 'Connexion en HTTPS', 'ok' => request()->isSecure(), 'level' => 'warning',
             'detail' => request()->isSecure() ? 'OK' : 'Connexion non chiffrée : obligatoire en production.'],
            ['label' => 'Cookie de session sécurisé', 'ok' => (bool) config('session.secure'), 'level' => 'warning',
             'detail' => 'SESSION_SECURE_COOKIE=' . (config('session.secure') ? 'true' : 'false')],
            ['label' => 'Archive de sauvegarde chiffrée', 'ok' => filled(config('backup.backup.password')), 'level' => 'warning',
             'detail' => filled(config('backup.backup.password')) ? 'OK' : 'Définissez BACKUP_ARCHIVE_PASSWORD.'],
            ['label' => 'Sauvegardes hors du dossier du projet', 'ok' => $outside, 'level' => 'warning',
             'detail' => $outside ? 'OK' : 'Utilisez BACKUP_PATH vers un autre disque ou serveur.'],
            ['label' => 'Échecs de connexion (24 h)', 'ok' => $fails < 10, 'level' => 'warning',
             'detail' => $fails . ' tentative(s) échouée(s)'],
        ];
    }
}
