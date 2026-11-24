<?php
// ASCII-only installer to avoid Windows PowerShell mojibake.
$root = __DIR__;
$controller = $root . '/app/Http/Controllers/ProspectController.php';
$view = $root . '/resources/views/prospects/index.blade.php';
if (!is_file($controller) || !is_file($view)) {
    fwrite(STDERR, "Required Phase 2A/6B files absent. Nothing modified.\n");
    exit(1);
}
$original = file_get_contents($controller);
$start = '        // Ne pas r'; // reliable replace via code structure, not accented comment
$blockBegin = '        $duplicate = Client::withoutGlobalScopes()->where(function($q) use($data) {';
$blockEnd = '        DB::transaction(function() use($data) {';
$posStart = strpos($original, $blockBegin);
$posEnd = strpos($original, $blockEnd);
if ($posStart === false || $posEnd === false || $posStart >= $posEnd) {
    fwrite(STDERR, "ProspectController changed since Phase 2A. Installation stopped safely.\n");
    exit(2);
}
$replacement = <<<'CODE'
        $duplicate = app(\App\Services\ProspectDuplicateService::class)
            ->exists($data['email'] ?? null, $data['phone'] ?? null);
        if ($duplicate) {
            throw ValidationException::withMessages([
                'phone' => 'Contact deja present dans le CRM. Demandez une verification a l\'administrateur.',
            ]);
        }
CODE;
$replacement .= "\n";
$updated = substr($original, 0, $posStart) . $replacement . substr($original, $posEnd);

// Fix two remaining visible UTF-8 mojibake sequences, only in this view.
$viewContent = file_get_contents($view);
$viewFixed = str_replace(
    [hex2bin('c383e280b0'), hex2bin('c3a2c593e280a2')],
    [hex2bin('c389'), hex2bin('e29c95')],
    $viewContent,
    $fixedCount
);

// Safe backups plus atomic replacement.
$backup = $root . '/backup-phase6c-' . date('Ymd-His');
@mkdir($backup . '/app/Http/Controllers', 0775, true);
@mkdir($backup . '/resources/views/prospects', 0775, true);
if (!copy($controller, $backup . '/app/Http/Controllers/ProspectController.php') ||
    !copy($view, $backup . '/resources/views/prospects/index.blade.php')) {
    fwrite(STDERR, "Backup failed. Nothing modified.\n");
    exit(3);
}
if (file_put_contents($controller, $updated) === false) {
    fwrite(STDERR, "Could not update controller.\n");
    exit(4);
}
if ($fixedCount && file_put_contents($view, $viewFixed) === false) {
    copy($backup . '/app/Http/Controllers/ProspectController.php', $controller);
    fwrite(STDERR, "Could not update Blade. Controller restored.\n");
    exit(5);
}
echo "Phase 6C patch installed. Remaining encoding replacements: {$fixedCount}\n";
echo "Backup: {$backup}\n";
