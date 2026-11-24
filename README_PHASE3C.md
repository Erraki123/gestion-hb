# Phase 3C — Email & Notifications Direction

## Fonction
Résumé journalier unique : Devis sensibles en attente, Commandes en attente, Relances en retard. Aucune nouvelle page. Pas d'e-mail si zéro action. Destinataires : uniquement les comptes Admin actifs.

## Sécurité
Désactivé par défaut. Ne jamais tester une livraison réelle avec des identifiants de production non contrôlés. Les e-mails ne contiennent aucune donnée de client ni valeur financière.

## Installation
1. Backup projet + base de données (si nécessaire selon votre procédure).
2. Extraire le ZIP à la racine Laravel.
3. Ajouter une fois à la fin de `routes/console.php` :
   `require __DIR__ . '/direction_alerts_phase3c.php';`
4. `php artisan optimize:clear`
5. `php artisan direction:alerts --dry-run`
6. `php artisan test --filter=DirectionAlertsTest`
7. `php artisan schedule:list`

## Activer plus tard
Renseigner une configuration e-mail valide et vérifiée puis :
`DIRECTION_EMAIL_ALERTS=true`
`DIRECTION_ALERT_TIME=08:30`

Cette phase utilise le scheduler Laravel préexistant. Vérifier que `php artisan schedule:run` est bien exécuté par cron en production. Pour un test non-réel, conserver `DIRECTION_EMAIL_ALERTS=false`.

## Portée
L'e-mail client existant reste inchangé. Cette phase ne met PAS en place l'ingestion des réponses e-mail, les notifications push, ni les alertes instantanées après création d'une demande.
