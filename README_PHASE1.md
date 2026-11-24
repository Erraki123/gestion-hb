# HN BLOOM — Phase 1A : Validation de commande

Ce correctif est une première tranche, **pas** toute la phase Permissions & Validation.

## Modifications
- Un devis converti crée désormais une commande `nouvelle`, sans retrait de stock, sans livraison automatique.
- Le commercial peut demander la validation de la commande.
- Seul un administrateur peut approuver ou refuser.
- Seul un administrateur peut confirmer une commande approuvée; la confirmation conserve les opérations de stock et de livraison existantes.
- Historique des demandes et décisions sous forme de colonnes `orders` (demandeur, décideur, dates, motif) + journal de modification existant.
- Interface de validation dans la fiche commande.

## Installation (APRÈS backup et test sur une copie)
1. Copier les 7 fichiers du patch dans les mêmes chemins du projet, sans effacer les autres fichiers.
2. Exécuter `php artisan migrate`.
3. Exécuter `php artisan optimize:clear`.
4. Tester avec 1 compte Commercial et 1 compte Admin, sur base de test.

## Limites critiques avant mise en production
- Le contrôle des remises/prix exceptionnels n'est **pas encore implémenté** : ne pas considérer ce patch comme validation complète du cahier des charges.
- La création de facture indépendante, l'émission/envoi de documents, et les autres chemins métiers doivent être audités et sécurisés lors des prochaines étapes.
- Les commandes existantes `confirmee` et `terminee` restent telles quelles; il n'y a pas de rétro-validation.
- Les notifications de validation, la liste admin centralisée, la gestion du rôle support, UltraMsg et DeepSeek ne sont pas encore inclus.
- Ne pas activer en production tant que tous les chemins de validation n'ont pas été testés.
