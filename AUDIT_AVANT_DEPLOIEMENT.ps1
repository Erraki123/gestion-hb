# HN BLOOM - Audit local de preparation au deploiement (lecture seule).
# Execute from the Laravel project root in PowerShell.
$ErrorActionPreference = 'Continue'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel (artisan absent).' }
$reportFolder = Join-Path (Get-Location) 'storage\app\audit-local'
New-Item -ItemType Directory -Path $reportFolder -Force | Out-Null
$report = Join-Path $reportFolder ('audit-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.txt')
$lines = New-Object System.Collections.Generic.List[string]
function Record([string]$title,[string]$status,[string]$details='') {
    $line = "[$status] $title"; Write-Host $line; $script:lines.Add($line)
    if ($details) { $script:lines.Add($details) }
}
function RunCheck([string]$title,[string[]]$argv) {
    $out = & $argv[0] $argv[1..($argv.Length-1)] 2>&1 | Out-String
    if ($LASTEXITCODE -eq 0) { Record $title 'OK' $out }
    else { Record $title 'A_VERIFIER' $out }
}
Record 'Projet Laravel detecte' 'OK'
foreach($f in @('vendor\autoload.php','public\index.php','resources\views\layouts\app.blade.php')) {
    if (Test-Path $f) { Record "Fichier: $f" 'OK' } else { Record "Fichier: $f" 'A_VERIFIER' }
}
RunCheck 'Version PHP' @('php','-v')
RunCheck 'Version Laravel' @('php','artisan','--version')
RunCheck 'Migrations (lecture seule)' @('php','artisan','migrate:status')
RunCheck 'Routes IA flottante' @('php','artisan','route:list','--path=admin/ai-floating','-v')
RunCheck 'Routes Prospects' @('php','artisan','route:list','--path=prospects')
RunCheck 'Routes WhatsApp' @('php','artisan','route:list','--path=integrations/ultramsg')
RunCheck 'Scheduler' @('php','artisan','schedule:list')
# These suites use their own test DB; do not run against the production DB.
foreach($suite in @('RegistrationTest','UltraMsgWebhookTest','DirectionAlertsTest','CommercialAiServiceTest','CrmAiAccessTest','CrmAiIntentServiceTest','CrmAgentPlannerTest','ProspectDiscoveryServiceTest','ProspectQualificationServiceTest','ProspectCsvImportServiceTest')) {
    RunCheck "Test: $suite" @('php','artisan','test',"--filter=$suite")
}
$lines.Add('')
$lines.Add('Limitations : cet audit ne confirme pas les APIs externes, le scraping reel, les permissions exhaustives, le backup/restore ni la securite de production.')
$lines | Set-Content -Path $report -Encoding UTF8
Write-Host "`nRapport : $report" -ForegroundColor Cyan
Write-Host 'Ne partagez jamais .env, token API, mots de passe ou dump DB.' -ForegroundColor Yellow
