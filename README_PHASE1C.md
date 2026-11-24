HN BLOOM - Phase 1C | Centre de validation administrateur

Patch incrémental : nécessite la Phase 1A puis la Phase 1B.
Ne pas appliquer directement en production sans essais sur une copie et sauvegarde complète.

Contenu :
- Nouvelle page /admin/approvals (admin seulement) et lien "Validations" dans la navigation admin.
- Devis sensibles en attente et commandes non confirmées en attente de validation.
- Cartes de compteur, accès aux fiches, approbation/refus avec motif (obligatoire pour devis).
- Décisions réutilisent les contrôleurs existants de Phase1A/Phase1B et leurs vérifications serveur.
- Pagination indépendante des deux listes.
- Aucune migration supplémentaire; aucun changement de tables.

Limites :
- L'approbation d'une commande n'est pas sa confirmation finale ; effectuer la confirmation depuis la fiche commande.
- Les factures directes, changements de prix catalogue et notifications ne sont pas encore couverts.
- Pas de compteur en temps réel; rafraîchir la page pour les nouveautés.
- Les approbations sont protégées contre les doubles décisions via les contrôleurs précédents.

Installer dans le dossier Laravel (artisan) :
$zip = "$env:USERPROFILE\Downloads\HN_BLOOM_Phase1C_Centre_Validation.zip"
Expand-Archive $zip -DestinationPath . -Force
php artisan optimize:clear
php artisan route:list --path=admin/approvals

Tests : connexion admin voit Validations, commercial n'y accède pas (403), approuver/refuser devis puis commande, pages multiples, vérifier le statut dans les fiches.
