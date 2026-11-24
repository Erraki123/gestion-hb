# =====================================================================
#  export-projet.ps1  —  Résumé complet d'un projet Laravel (SANS secrets)
#  Lancer depuis la racine du projet (là où se trouve le fichier "artisan") :
#     powershell -ExecutionPolicy Bypass -File .\export-projet.ps1
#  Résultat : projet-info.txt sur le Bureau
#  Ne lit PAS le fichier .env et n'exporte AUCUNE donnée de la base
#  (uniquement la structure : tables, colonnes).
# =====================================================================
$ErrorActionPreference = 'Continue'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) {
    Write-Host "Lance ce script depuis la racine du projet (ou se trouve le fichier 'artisan')." -ForegroundColor Red
    exit 1
}

$out = Join-Path $env:USERPROFILE 'Desktop\projet-info.txt'
$sb  = New-Object System.Text.StringBuilder

function Add-Line([string]$t = '') { [void]$sb.AppendLine($t) }
function Add-Title([string]$t) { Add-Line ''; Add-Line ('=' * 72); Add-Line $t; Add-Line ('=' * 72) }
function Rel([string]$p) { if ($p.Length -gt $root.Length) { $p.Substring($root.Length + 1) } else { $p } }
function Short([string]$s, [int]$n = 160) { $s = ($s -replace '\s+', ' ').Trim(); if ($s.Length -gt $n) { $s.Substring(0, $n) + '…' } else { $s } }

function List-Files([string]$dir, [string]$filter = '*.php') {
    if (Test-Path $dir) {
        Get-ChildItem $dir -Recurse -Filter $filter -File | Sort-Object FullName | ForEach-Object { Add-Line ('  ' + (Rel $_.FullName)) }
    } else { Add-Line '  (dossier absent)' }
}

function Get-Methods([string]$file) {
    $txt = Get-Content $file -Raw
    $m = [regex]::Matches($txt, 'public\s+(?:static\s+)?function\s+(\w+)\s*\(([^)]*)\)')
    foreach ($x in $m) { '{0}({1})' -f $x.Groups[1].Value, (Short $x.Groups[2].Value 80) }
}

Write-Host 'Collecte des informations…' -ForegroundColor Cyan
Add-Line 'RÉSUMÉ DU PROJET LARAVEL (généré par export-projet.ps1)'
Add-Line ('Généré le : ' + (Get-Date -Format 'yyyy-MM-dd HH:mm'))
Add-Line ('Dossier   : ' + $root)

# ---------------------------------------------------------------- 1. versions
Add-Title '1. VERSIONS ET PACKAGES'
try { Add-Line ('Laravel : ' + ((php artisan --version 2>$null) -join ' ')) } catch {}
try { Add-Line ('PHP     : ' + (php -r 'echo PHP_VERSION;' 2>$null)) } catch {}
if (Test-Path "$root\composer.json") {
    $c = Get-Content "$root\composer.json" -Raw | ConvertFrom-Json
    Add-Line 'composer require :'
    foreach ($p in $c.require.PSObject.Properties) { Add-Line ('  {0} {1}' -f $p.Name, $p.Value) }
    if ($c.'require-dev') {
        Add-Line 'composer require-dev :'
        foreach ($p in $c.'require-dev'.PSObject.Properties) { Add-Line ('  {0} {1}' -f $p.Name, $p.Value) }
    }
}
if (Test-Path "$root\package.json") {
    $n = Get-Content "$root\package.json" -Raw | ConvertFrom-Json
    foreach ($k in 'dependencies', 'devDependencies') {
        if ($n.$k) {
            Add-Line ("package.json $k :")
            foreach ($p in $n.$k.PSObject.Properties) { Add-Line ('  {0} {1}' -f $p.Name, $p.Value) }
        }
    }
}

# ---------------------------------------------------------------- 2. routes
Add-Title '2. ROUTES  (méthode | uri | nom | action | middleware)'
try {
    $routes = (php artisan route:list --json 2>$null | Out-String) | ConvertFrom-Json
    foreach ($r in $routes) {
        if ($r.action -match '^(Illuminate|Laravel)\\') { continue }
        $mw = ($r.middleware | ForEach-Object { ($_ -split '\\')[-1] }) -join ','
        Add-Line ('{0,-10} {1,-48} {2,-40} {3}  [{4}]' -f $r.method, $r.uri, $r.name, $r.action, $mw)
    }
} catch { Add-Line "(route:list impossible : $_)" }

# ---------------------------------------------------------------- 3. controllers
Add-Title '3. CONTROLLERS  (méthodes publiques)'
if (Test-Path "$root\app\Http\Controllers") {
    Get-ChildItem "$root\app\Http\Controllers" -Recurse -Filter *.php -File | Sort-Object FullName | ForEach-Object {
        Add-Line ''
        Add-Line (Rel $_.FullName)
        foreach ($m in (Get-Methods $_.FullName)) { Add-Line ('    - ' + $m) }
    }
}

# ---------------------------------------------------------------- 4. models
Add-Title '4. MODELS  (table, fillable, relations, scopes, constantes, méthodes)'
if (Test-Path "$root\app\Models") {
    Get-ChildItem "$root\app\Models" -Recurse -Filter *.php -File | Sort-Object FullName | ForEach-Object {
        $txt = Get-Content $_.FullName -Raw
        Add-Line ''
        Add-Line (Rel $_.FullName)

        $m = [regex]::Match($txt, '(?m)^\s{4}use\s+([^;{]+);')
        if ($m.Success) { Add-Line ('    traits    : ' + (Short $m.Groups[1].Value)) }

        $m = [regex]::Match($txt, '\$table\s*=\s*''([^'']+)''')
        if ($m.Success) { Add-Line ('    table     : ' + $m.Groups[1].Value) }

        $m = [regex]::Match($txt, '\$fillable\s*=\s*\[(.*?)\]\s*;', 'Singleline')
        if ($m.Success) { Add-Line ('    fillable  : ' + (Short $m.Groups[1].Value 400)) }

        $rel = [regex]::Matches($txt, 'public\s+function\s+(\w+)\s*\(\)(?:\s*:\s*[\w\\]+)?\s*\{\s*return\s+(\$this->(?:hasMany|hasOne|belongsTo|belongsToMany|hasManyThrough|hasOneThrough|morphTo|morphMany|morphOne|morphToMany)\([^;]*);', 'Singleline')
        foreach ($r in $rel) { Add-Line ('    relation  : {0}  =>  {1}' -f $r.Groups[1].Value, (Short ($r.Groups[2].Value -replace '^\$this->', '') 140)) }

        $sc = [regex]::Matches($txt, 'function\s+scope(\w+)')
        if ($sc.Count) { Add-Line ('    scopes    : ' + (($sc | ForEach-Object { $_.Groups[1].Value }) -join ', ')) }

        foreach ($k in [regex]::Matches($txt, '(?m)^\s*(?:public\s+)?const\s+(\w+)\s*=\s*([^;]+);')) {
            Add-Line ('    const     : {0} = {1}' -f $k.Groups[1].Value, (Short $k.Groups[2].Value 150))
        }

        $relNames = @($rel | ForEach-Object { $_.Groups[1].Value })
        $others = Get-Methods $_.FullName | Where-Object { ($_ -replace '\(.*$', '') -notin $relNames -and $_ -notmatch '^scope' }
        if ($others) { Add-Line ('    méthodes  : ' + ($others -join ', ')) }
    }
}

# ---------------------------------------------------------------- 5. services & autres classes
Add-Title '5. SERVICES, MIDDLEWARE, REQUESTS, POLICIES, COMMANDES (méthodes publiques)'
foreach ($d in 'app\Services', 'app\Http\Middleware', 'app\Http\Requests', 'app\Policies', 'app\Console\Commands', 'app\Exports', 'app\Observers', 'app\Jobs', 'app\Mail', 'app\Notifications', 'app\View\Components') {
    if (Test-Path "$root\$d") {
        Add-Line ''
        Add-Line ("[$d]")
        Get-ChildItem "$root\$d" -Recurse -Filter *.php -File | Sort-Object FullName | ForEach-Object {
            $ms = @(Get-Methods $_.FullName)
            Add-Line ('  ' + $_.Name + '  ->  ' + (Short ($ms -join ', ') 300))
        }
    }
}

# ---------------------------------------------------------------- 6. vues
Add-Title '6. VUES BLADE  (dossier : fichiers)    [dark] = nouveau design, [old] = ancien design'
if (Test-Path "$root\resources\views") {
    $views = Get-ChildItem "$root\resources\views" -Recurse -Filter *.blade.php -File | Sort-Object FullName
    $views | Group-Object { Split-Path (Rel $_.FullName) -Parent } | ForEach-Object {
        Add-Line ''
        Add-Line ($_.Name + '\')
        foreach ($v in $_.Group) {
            $t = Get-Content $v.FullName -Raw
            $flag = ''
            if ($t -match 'dark-header') { $flag = ' [dark]' }
            elseif ($t -match '<x-app-layout') { $flag = ' [old]' }
            Add-Line ('    ' + $v.Name + $flag)
        }
    }
}

# ---------------------------------------------------------------- 7. base de données
Add-Title '7. BASE DE DONNÉES  (structure seulement, aucune donnée)'
try {
    $db = (php artisan db:show --json 2>$null | Out-String) | ConvertFrom-Json
    foreach ($t in $db.tables) {
        $name = $t.table
        if (-not $name) { $name = $t.name }
        if (-not $name) { continue }
        Add-Line ''
        Add-Line ('TABLE ' + $name)
        try {
            $tj = (php artisan db:table $name --json 2>$null | Out-String) | ConvertFrom-Json
            if ($tj.columns) {
                foreach ($col in $tj.columns) {
                    $attrs = @($col.attributes)
                    $line = '    {0} : {1}' -f $col.column, $col.type
                    if ($attrs -contains 'nullable') { $line += ' NULL' }
                    if ($attrs -contains 'autoincrement') { $line += ' PK' }
                    if ($null -ne $col.default -and "$($col.default)" -ne '') { $line += (' default=' + $col.default) }
                    Add-Line $line
                }
                foreach ($ix in @($tj.indexes)) {
                    if ($ix -and (@($ix.attributes) -contains 'unique') -and (@($ix.attributes) -notcontains 'primary')) {
                        Add-Line ('    UNIQUE (' + (@($ix.columns) -join ', ') + ')')
                    }
                }
                foreach ($fk in @($tj.foreign_keys)) {
                    if ($fk) {
                        Add-Line ('    FK ({0}) -> {1}({2})  delete:{3}' -f (@($fk.columns) -join ','), $fk.foreign_table, (@($fk.foreign_columns) -join ','), $fk.on_delete)
                    }
                }
            } else {
                Add-Line '    (format JSON inattendu, sortie brute)'
                php artisan db:table $name 2>$null | Select-Object -First 40 | ForEach-Object { Add-Line ('    ' + $_) }
            }
        } catch { Add-Line "    (db:table impossible : $_)" }
    }
} catch { Add-Line "(db:show impossible : $_)" }

# ---------------------------------------------------------------- 8. migrations, seeders, tests
Add-Title '8. MIGRATIONS, SEEDERS, TESTS'
Add-Line '[migrations]'; List-Files "$root\database\migrations"
Add-Line '[seeders]';    List-Files "$root\database\seeders"
Add-Line '[factories]';  List-Files "$root\database\factories"
Add-Line '[tests]';      List-Files "$root\tests"

# ---------------------------------------------------------------- 9. config & design
Add-Title '9. CONFIG ET DESIGN (fichiers non secrets)'
foreach ($f in 'config\commerce.php', 'tailwind.config.js', 'vite.config.js') {
    if (Test-Path "$root\$f") {
        Add-Line ''
        Add-Line ('----- ' + $f + ' -----')
        Get-Content "$root\$f" | Select-Object -First 200 | ForEach-Object { Add-Line $_ }
    }
}
foreach ($f in 'resources\css\app.css', 'resources\js\app.js') {
    if (Test-Path "$root\$f") {
        Add-Line ''
        Add-Line ('----- ' + $f + ' (400 premières lignes) -----')
        Get-Content "$root\$f" | Select-Object -First 400 | ForEach-Object { Add-Line $_ }
    }
}
Add-Line ''
Add-Line '[config/*.php présents]'; List-Files "$root\config"

# ---------------------------------------------------------------- écriture
[System.IO.File]::WriteAllText($out, $sb.ToString(), (New-Object System.Text.UTF8Encoding($false)))
$size = [math]::Round((Get-Item $out).Length / 1KB)
Write-Host ''
Write-Host "Terminé : $out  ($size Ko)" -ForegroundColor Green
Write-Host 'Envoie ce fichier à Claude : il en fera le fichier .md pour les autres IA.' -ForegroundColor Green