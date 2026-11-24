<?php
// CLI only; narrowly injects two extra evidence lines into existing ATLAS details.
$path = __DIR__ . '/resources/views/prospects/_discovery_review.blade.php';
if (!is_file($path)) { fwrite(STDERR, "ATLAS review view missing\n"); exit(2); }
$s = file_get_contents($path);
if (str_contains($s, "ATLAS_SECTOR_6H")) { echo "ATLAS 6H UI already present\n"; exit(0); }
$needle = '<p><strong>Source :</strong>';
if (substr_count($s, $needle) !== 1) { fwrite(STDERR, "Expected UI marker missing/duplicated. No view changes.\n"); exit(3); }
$extra = '<!-- ATLAS_SECTOR_6H -->'
 . '<p><strong>Secteur suppose :</strong> {{ $rec[\'sector_label\'] }} <span class="text-stone-500">({{ $rec[\'sector_confidence\'] }})</span></p>'
 . '<p><strong>Qualite des donnees :</strong> {{ $rec[\'data_quality\'] }}</p>'
 . '<p class="text-stone-500">Le secteur est une hypothese fondee sur le nom, pas une verification. Le score commercial existant reste inchange.</p>';
if (file_put_contents($path, str_replace($needle, $extra . $needle, $s)) === false) { exit(4); }
echo "ATLAS sector indicators added to the current review UI\n";
