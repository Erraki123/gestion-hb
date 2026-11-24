<?php
/** Patch existing Phase 6N company modal, admin-only route, and isolated review ledger. */
$root = __DIR__;
$view = $root.'/resources/views/prospects/_discovery_review.blade.php';
$routes = $root.'/routes/web.php';
$fragmentFile = $root.'/PATCH_ATLAS_6O/contact_review.fragment';
$marker = '<h4>Analyse ATLAS</h4>';
$require = "require __DIR__ . '/atlas_contact_phase6o.php';";
$items = [
  'app/Services/AtlasContactDecisionService.php',
  'app/Http/Controllers/Admin/AtlasContactDecisionController.php',
  'database/migrations/2026_11_15_140000_create_atlas_contact_decisions_table.php',
  'routes/atlas_contact_phase6o.php',
  'tests/Unit/AtlasContactDecisionServiceTest.php',
];
if (!is_file($view) || !is_file($routes) || !is_file($fragmentFile)) { fwrite(STDERR, "Missing Phase 6N files or patch.\n"); exit(2); }
$beforeView = file_get_contents($view);
$beforeRoutes = file_get_contents($routes);
if (!str_contains($beforeView, 'atlas-6n-contact-suggestions') || substr_count($beforeView, $marker) !== 1 || !str_contains($beforeView, 'atlas-sheet-{{ (int) $candidate->id }}')) {
  fwrite(STDERR, "Expected Phase 6N modal not found. No files changed.\n"); exit(3);
}
if (str_contains($beforeView, 'atlas-6o-contact-decisions') || str_contains($beforeRoutes, $require)) {
  fwrite(STDERR, "Phase 6O already installed. Nothing changed.\n"); exit(4);
}
if (!str_contains($beforeRoutes, "require __DIR__ . '/auth.php';")) { fwrite(STDERR, "Cannot locate routes anchor.\n"); exit(5); }
$fragment = file_get_contents($fragmentFile);
if (!$fragment || !str_contains($fragment, 'atlas-6o-contact-decisions')) { exit(6); }
foreach($items as $relative) {
  if (!is_file($root.'/PATCH_ATLAS_6O/'.$relative)) { fwrite(STDERR, "Missing patch file $relative\n"); exit(7); }
}
$backup = $root.'/backup-atlas-phase6o-'.date('Ymd-His');
if (!mkdir($backup, 0755, true)) { exit(8); }
$targets = array_merge(['resources/views/prospects/_discovery_review.blade.php','routes/web.php'], $items);
try {
  foreach($targets as $relative) {
    $destination = $root.'/'.$relative;
    if (is_file($destination)) {
      $backupFile = $backup.'/'.$relative;
      if (!is_dir(dirname($backupFile))) mkdir(dirname($backupFile), 0755, true);
      if (!copy($destination, $backupFile)) throw new RuntimeException("Backup failed: $relative");
    }
  }
  $newView = str_replace($marker, $fragment."\n                    ".$marker, $beforeView);
  $newRoutes = str_replace("require __DIR__ . '/auth.php';", "require __DIR__ . '/auth.php';\n\n// ATLAS Phase 6O: manually reviewed contact evidence, admin-only.\n".$require, $beforeRoutes);
  foreach($items as $relative) {
    $destination = $root.'/'.$relative;
    if (!is_dir(dirname($destination))) mkdir(dirname($destination), 0755, true);
    if (!copy($root.'/PATCH_ATLAS_6O/'.$relative, $destination)) throw new RuntimeException("Copy failed: $relative");
  }
  if (file_put_contents($view, $newView) === false || file_put_contents($routes, $newRoutes) === false) throw new RuntimeException('Could not write project files');
} catch(Throwable $e) {
  foreach($targets as $relative) {
    $destination = $root.'/'.$relative;
    $saved = $backup.'/'.$relative;
    if (is_file($saved)) copy($saved, $destination);
    else @unlink($destination);
  }
  fwrite(STDERR, "Patch failed and restored: ".$e->getMessage()."\n"); exit(9);
}
file_put_contents($root.'/ATLAS_PHASE6O_BACKUP_PATH.txt', $backup);
echo "ATLAS 6O files patched. Backup: $backup\n";
