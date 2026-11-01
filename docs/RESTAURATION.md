# Procédure de restauration

## Avant de commencer
- Garder séparément : le fichier `.env` et la valeur de `APP_KEY` (non inclus dans les sauvegardes).
- L'archive est chiffrée : mot de passe = `BACKUP_ARCHIVE_PASSWORD`. Ouvrir avec 7-Zip.

## Étapes
1. Récupérer la dernière archive `.zip` dans le dossier de sauvegarde (`BACKUP_PATH/gestion-commerciale/`).
2. L'extraire dans un dossier temporaire (mot de passe requis).
3. Base de données : le fichier `db-dumps/mysql-<base>.sql` se trouve dans l'archive.
   - Créer une base vide : `CREATE DATABASE gestion_commerciale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   - Importer : `mysql -u root -p gestion_commerciale < mysql-gestion_commerciale.sql`
4. Fichiers : les dossiers sont conservés sous leur chemin d'origine dans l'archive. Copier :
   - `storage/app/private/clients`  → `storage/app/private/clients`
   - `storage/app/public`           → `storage/app/public`
   - `storage/app/private/activity-archives` → idem
5. Dans le projet : `php artisan storage:link`, `php artisan optimize:clear`, `php artisan migrate --status`.
6. Vérifier : connexion admin, un client avec ses photos, une facture PDF, le total des factures.

## Test de restauration (au moins une fois par mois)
- Restaurer dans une base séparée `gestion_commerciale_restore_test` (jamais sur la base de production).
- Comparer : nombre de clients, de factures et somme des paiements avec la production.
- Noter la date et le résultat du test.
