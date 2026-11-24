<?php
// Safe, idempotent Blade-only change. No DB migration or network call.
$path = __DIR__ . '/resources/views/prospects/_discovery_review.blade.php';
if (!is_file($path)) { fwrite(STDERR, "View not found: $path\n"); exit(2); }
$original = file_get_contents($path);
if (str_contains($original, 'atlas-sheet-{{ (int) $candidate->id }}')) {
    echo "ATLAS Modal already installed. No changes.\n";
    exit(0);
}
$regex = '~<details class="mt-2 max-w-sm">.*?</details>~s';
if (preg_match_all($regex, $original) !== 1) {
    fwrite(STDERR, "Expected exactly one existing ATLAS details block. No changes made.\n"); exit(3);
}
$modal = file_get_contents(__DIR__ . '/PATCH_ATLAS/modal.blade.fragment');
$styles = file_get_contents(__DIR__ . '/PATCH_ATLAS/styles.blade.fragment');
if (!$modal || !$styles) { fwrite(STDERR, "Missing patch fragments\n"); exit(4); }
$updated = preg_replace_callback($regex, static fn () => $modal, $original, 1, $replaced);
if ($replaced !== 1 || !str_contains($updated, '@forelse($candidates as $candidate)')) {
    fwrite(STDERR, "Could not apply safe replacement\n"); exit(5);
}
$anchor = '<section class="mx-auto mb-5 max-w-7xl px-4 sm:px-6 lg:px-8"';
if (substr_count($updated, $anchor) !== 1) { fwrite(STDERR, "Unexpected layout marker\n"); exit(6); }
$updated = str_replace($anchor, $styles . "\n" . $anchor, $updated);
$backupDir = __DIR__ . '/backup-atlas-modal-' . date('Ymd-His');
if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) { fwrite(STDERR, "Backup folder failed\n"); exit(7); }
if (!copy($path, $backupDir . '/_discovery_review.blade.php')) { fwrite(STDERR, "Backup failed\n"); exit(8); }
if (file_put_contents($path, $updated) === false) { fwrite(STDERR, "Write failed\n"); exit(9); }
file_put_contents(__DIR__ . '/ATLAS_MODAL_BACKUP_PATH.txt', $backupDir);
echo "ATLAS company modal installed. Backup: $backupDir\n";
