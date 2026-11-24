# HN BLOOM — Phase 2B : Dashboard Direction / Alertes (minimal)

## Prérequis
Phases 1A, 1B, 1C et 2A installées. Les migrations des validations et de prospect_details doivent avoir été exécutées.

## Contenu (4 fichiers à remplacer)
- app/Http/Controllers/DashboardController.php
- resources/views/admin/dashboard.blade.php
- app/Http/Controllers/ProspectController.php
- resources/views/prospects/index.blade.php

## Fonctionnement
- Tableau de bord admin : 2 cartes « Validations en attente » et « Relances en retard » avec liens rapides.
- La carte Validations reprend les mêmes conditions que le centre de validation de Phase 1C.
- « Relances en retard » ignore les prospects gagnés, perdus ou désinscrits.
- Sur /prospects : filtre « En retard seulement ».
- Pas de notification push, pas d'e-mail, pas de migration ni de tâche planifiée dans cette phase.
- Les compteurs se mettent à jour lors du chargement de la page.
- Les droits existants ne changent pas : Dashboard Admin reste réservé à l'admin, et ProspectController garde le scope de propriété des commerciaux.

## Installation
Voir les instructions PowerShell dans la réponse ChatGPT. Avant installation : backup des 4 fichiers. Aucun php artisan migrate requis.

## Vérifications
php -l app/Http/Controllers/DashboardController.php
php -l app/Http/Controllers/ProspectController.php
php artisan optimize:clear
php artisan view:cache
php artisan route:list --name=admin.dashboard
php artisan route:list --path=prospects
php artisan test

## Limites
Patch préparé par inspection des fichiers et vérification syntaxique PHP. Il n'est pas encore validé par tests end-to-end sur la base réelle. Ne pas déployer en production sans test local et sauvegarde.
