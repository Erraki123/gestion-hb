# ATLAS 6H - Reconnaissance sectorielle locale (sans API)

Installer dans la racine Laravel avec `INSTALL_ATLAS_PHASE6H.ps1`.

- Ajoute une estimation de secteur explicable, a partir du **nom uniquement**, dans la fiche d'analyse ATLAS.
- Distingue qualite des donnees et score commercial existant.
- Ne suggere que des produits actifs/en stock et de familles compatibles avec le secteur suppose.
- Evite notamment de classifier `Hôtel kennedy Hospitality Resort` comme sante et `Le Laboratoire Conception et Systèmes` comme laboratoire medical.
- Conserve les permissions Admin dans `_discovery_review.blade.php`.
- Aucune connexion DeepSeek, aucune migration, aucun message ni import.
- Ne met pas a jour automatiquement les anciens scores 31/100: ils restent issus de la qualification existante.

Test: `php artisan test --filter=AtlasSectorRecognitionTest`; `php artisan atlas:radar --dry-run --limit=5`; http://127.0.0.1:8000/prospects .

Les activites et besoins ne sont pas verifies; les suggestions sont des hypotheses a confirmer.
