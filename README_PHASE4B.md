# HN BLOOM — Phase 4B · Questions sur les données CRM

## Ce que fait cette version
- Consultation en lecture seule de **produits/stock**, **clients**, **devis**, **commandes**, **factures**.
- Les résultats sont limités à 12 entrées; les commerciaux restent soumis aux scopes `owner` des modèles.
- Les prix d'achat, marges, coordonnées personnelles, clés et mots de passe ne sont jamais sélectionnés.
- Si DeepSeek est désactivé : résultats réels affichés en JSON lisible. Si activé : synthèse courte des mêmes résultats par DeepSeek.
- Aucun accès SQL direct pour l'IA, aucune modification de stock, aucune création ou envoi automatique.

## Installation
1. Backup des fichiers / base de données avant installation.
2. Extraire ce ZIP **dans la racine Laravel** (celle du fichier `artisan`).
3. Ajouter **une seule fois** à la fin de `routes/web.php` :
   `require __DIR__ . '/crm_ai_phase4b.php';`
4. `php artisan optimize:clear`
5. `php artisan route:list --path=assistant-commercial/donnees`
6. `php artisan test --filter=CrmAiAccessTest`
7. `php artisan view:cache`
8. Ouvrir `/assistant-commercial/donnees`

## À ne pas confondre avec la suite
- Cette étape ne fait PAS encore de prospection par scraping, ni de Tool Calling multi-tables à volonté, ni de recherche sémantique.
- Le filtrage est volontairement simple et explicite, afin d'éviter les réponses issues d'une mauvaise sélection de données.
- La synthèse par fournisseur IA transmet un extrait limité de vos données commerciales : valider le traitement de données avec la direction avant l'activation.
- Les tests doivent être exécutés sur la base de test dédiée. Pas de `migrate:fresh` en production.
