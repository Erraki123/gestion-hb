# HN BLOOM — Phase 4D : IA flottante Admin

## Objectif
Une icône IA fixée en bas à droite de **toutes les pages utilisant le layout authentifié `layouts.app`**, visible uniquement aux administrateurs. Ouverture d'un panneau simple sans quitter la page.

- Question sur données CRM via **le même contrôleur/service que Phase 4C**.
- Emails, WhatsApp, relances : brouillons, **aucun envoi automatique**.
- Réponses affichées comme texte simple (`x-text`), pas de HTML interprété.
- Contrôle `auth + active + role:admin`, CSRF, rate-limit (8/min).
- Pas d'accès SQL libre ni de modification de données.
- Pas de migration, ni modification des anciens contrôleurs.

## Installation
1. Copier ce ZIP dans `Downloads`.
2. Ouvrir PowerShell à la racine Laravel contenant `artisan`.
3. Extraire uniquement le script, si nécessaire :

   `Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Phase4D_IA_Flottante_Admin.zip" -DestinationPath . -Force`

4. Exécuter : `powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE4D.ps1`

Le script vérifie Phase 4C, crée un backup de `routes/web.php` et `layouts/app.blade.php`, installe les nouveaux fichiers et insère une seule fois la route et l'include. Vous pouvez le relancer sans duplication.

## Tests manuels
- Admin : voir l'icône ✦ sur Dashboard, Clients et Produits ; ouvrir, fermer, poser une question et recevoir une réponse.
- Commercial : **aucune icône** ; accès POST /admin/ai-floating/ask refusé.
- Question produit : essayer `Quel est le stock du produit "CAM-001" ?` avec une vraie référence de la base.
- Vérifier absence d'envoi externe. DeepSeek peut être désactivé et le résultat local reste possible suivant Phase 4C.

## Limites
- Le panneau est rendu dans les pages avec `<x-app-layout>`; pages Login et autres layouts indépendants ne sont pas concernées.
- Le panneau **ne mémorise pas les conversations** entre navigations.
- Pour la recherche, une référence exacte peut être nécessaire car Phase 4C utilise la détection d'intention basique.
- Les tests ne remplacent pas les vérifications des autorisations sur les données et les validations commerciales.
