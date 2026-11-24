HN BLOOM - Phase 1B / Approbation des devis sensibles

IMPORTANT : ce ZIP est un PATCH INCREMENTAL et suppose que Phase 1A est deja installee.
Avant d'extraire : sauvegarde de la base + sauvegarde complete du projet, sur une copie de test.
Le patch contient uniquement les fichiers modifies, aux chemins Laravel standards.

Ce qui change :
- Devis contenant remise > 0, ligne libre ou prix catalogue modifie : validation admin obligatoire.
- Le commercial demande validation; l'admin approuve ou refuse avec motif.
- Envoi manuel, envoi automatique et job en file d'attente bloques avant approbation.
- Conversion de devis sensibles en commande bloquee avant approbation.
- Modifier un devis annule son approbation precedente (si remise/prix special maintenu).
- Tous les devis standards sans ligne libre/remise/prix special continuent leur parcours normal.
- Devis historiques avec remise ou ligne libre passent en pending (requiert revue admin).

Limitations / travaux restants :
- Pas encore de workflow de prix sur les factures directes ni de demande d'ajustement du prix catalogue (ProductController reste admin only).
- Pas encore de centre global des validations ni notifications push/email admin.
- Le PDF telecharge manuellement et les liens PDF existants ne sont pas bloques : le document sensible reste en brouillon a usage interne. Ne pas diffuser manuellement avant validation.
- Il faut lancer tests d'integration et tester l'envoi avec le vrai worker avant production.

Installation Powershell DANS la racine du projet (avec artisan) :
$zip = "$env:USERPROFILE\Downloads\HN_BLOOM_Phase1B_Remises_Devis.zip"
if (-not (Test-Path $zip)) { throw "ZIP introuvable: $zip" }
$backup = "backup-phase1B-$(Get-Date -Format yyyyMMdd-HHmmss)"
New-Item -ItemType Directory -Path $backup | Out-Null
$paths = @('app/Models/Quote.php','app/Http/Controllers/QuoteController.php','app/Services/DocumentSender.php','app/Jobs/SendDocumentJob.php','app/Http/Controllers/DocumentSendController.php','routes/web.php','resources/views/quotes/show.blade.php')
foreach ($f in $paths) { $dest=Join-Path $backup $f; New-Item -ItemType Directory -Force (Split-Path $dest) | Out-Null; Copy-Item $f $dest }
Expand-Archive $zip -DestinationPath . -Force
php artisan migrate
php artisan optimize:clear
php artisan route:list --path=quotes

Pour tester : devis standard sans remise, devis avec remise, devis avec ligne libre, commercial demande, admin refuse/approuve, modifier devis approuve, envoyer avant/apres, convertir avant/apres.
