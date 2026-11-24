<?php
/** Add contact suggestions to the existing ATLAS sheet, preserving all features. */
$root = __DIR__;
$view = $root.'/resources/views/prospects/_discovery_review.blade.php';
$serviceSrc = $root.'/PATCH_ATLAS_6N/app/Services/AtlasContactReviewService.php';
$serviceDest = $root.'/app/Services/AtlasContactReviewService.php';
if (!is_file($view) || !is_file($serviceSrc)) { fwrite(STDERR, "Required files missing.\n"); exit(2); }
$before = file_get_contents($view);
$marker = '<h4>Analyse ATLAS</h4>';
$anchor = "\$atlasWebsite = (is_string(\$candidate->website)";
if (!str_contains($before, 'atlas-sheet-{{ (int) $candidate->id }}') || substr_count($before, $marker) !== 1 || !str_contains($before, $anchor)) {
    fwrite(STDERR, "Expected Phase 6I company modal was not found. No changes.\n"); exit(3);
}
if (str_contains($before, 'atlas-6n-contact-suggestions')) { echo "6N already installed.\n"; exit(0); }
$fragment = file_get_contents($root.'/PATCH_ATLAS_6N/contact.fragment');
if (!$fragment) { fwrite(STDERR, "Missing fragment.\n"); exit(4); }
$after = str_replace($marker, $fragment."\n                    ".$marker, $before);
$backup = $root.'/backup-atlas-6n-'.date('Ymd-His');
if (!mkdir($backup, 0755, true)) { fwrite(STDERR, "Unable to create backup.\n"); exit(5); }
if (!copy($view, $backup.'/_discovery_review.blade.php')) { fwrite(STDERR, "Unable to back up view.\n"); exit(6); }
if (is_file($serviceDest) && !copy($serviceDest, $backup.'/AtlasContactReviewService.php')) { fwrite(STDERR, "Unable to back up service.\n"); exit(7); }
if (!copy($serviceSrc, $serviceDest) || file_put_contents($view, $after) === false) {
    copy($backup.'/_discovery_review.blade.php', $view);
    if (is_file($backup.'/AtlasContactReviewService.php')) copy($backup.'/AtlasContactReviewService.php', $serviceDest);
    else @unlink($serviceDest);
    fwrite(STDERR, "Patch failed; restored.\n"); exit(8);
}
file_put_contents($root.'/ATLAS_6N_BACKUP_PATH.txt', $backup);
echo "ATLAS 6N installed. Backup: $backup\n";
