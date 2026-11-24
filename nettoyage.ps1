# Nettoyage : déplace les vues inutilisées dans _corbeille_vues (rien n'est supprimé définitivement)
$files = @(
    'resources\views\invoices\_pay-modal.blade.php',
    'resources\views\invoices\pay.blade.php',
    'resources\views\welcome.blade.php',
    'resources\views\admin\commerciaux\dashboard.blade.php',
    'resources\views\profile\partials\delete-user-form.blade.php',
    'resources\views\components\application-logo.blade.php',
    'resources\views\components\nav-link.blade.php',
    'resources\views\components\responsive-nav-link.blade.php',
    'resources\views\components\pdf-layout.blade.php'
)
$bak = '_corbeille_vues'
New-Item -ItemType Directory -Force $bak | Out-Null
foreach ($f in $files) {
    if (Test-Path $f) {
        Move-Item $f (Join-Path $bak ($f -replace '\\','__')) -Force
        "déplacé : $f"
    } else {
        "absent  : $f"
    }
}
php artisan view:clear
php artisan route:clear
