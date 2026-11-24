# HN BLOOM · ATLAS AI — Phase 6D

**Vrai résultat de cette phase :** Opportunity Radar dans `/prospects`, alignement de chaque entreprise découverte avec les produits actifs et disponibles. Action recommandée prudente et sourcée. Aucun faux lead, aucune prise de contact ni devis automatique.

## Installation

Dézipper dans le projet Laravel, puis `powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6D.ps1`.

## Tester

`php artisan atlas:radar --dry-run` : analyse en lecture seule.

`php artisan atlas:radar --discover --dry-run` : simulation des sources déjà configurées. Attention : avec `--discover`, le réseau sera appelé uniquement si la collecte est activée dans la configuration.

`php artisan atlas:radar --discover` : collecte depuis les sources *déjà autorisées* suivie du scoring existant et du rapprochement catalogue. Ne fonctionne que si une source réellement disponible est configurée.

## Limites importantes

Cette phase ne fournit pas encore de connecteur universel pour récupérer les sociétés sur Google et ne contourne pas les limites Overpass. L'auto-discovery repose sur des flux autorisés que vous devez configurer; elle peut donc rester vide sans source. Les recommandations sont des hypothèses sectorielles, pas des besoins démontrés. La disponibilité tient compte des quantités positives en stock, pas de leur réservation. Pas de données de contacts transmises à l'IA par le rapprochement de produits.

Ne relancez pas les anciens installateurs de phases précédentes après l'installation.
