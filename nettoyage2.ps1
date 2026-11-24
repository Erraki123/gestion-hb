# Déplace la liste des commandes (doublon de la page Commandes) dans _corbeille_vues
$f = 'resources\views\orders\index.blade.php'
New-Item -ItemType Directory -Force '_corbeille_vues' | Out-Null
if (Test-Path $f) { Move-Item $f (Join-Path '_corbeille_vues' ($f -replace '\\','__')) -Force; "déplacé : $f" } else { "absent  : $f" }
php artisan route:clear
php artisan view:clear
