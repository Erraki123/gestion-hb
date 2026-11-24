# HN BLOOM — Phase 5C — Qualification des prospects

## But
Étendre Phase 5B, sans nouvelle page : classer les entreprises découvertes par pertinence HN BLOOM, avec justification. Le classement local est toujours disponible. Si DeepSeek est activé, un classement IA est possible. Aucune recherche web supplémentaire, aucun email, aucun message WhatsApp, aucun client ajouté automatiquement.

## Installation Windows
1. Sauvegarder la base de données ; installer Phase 5B d'abord.
2. Extraire ce ZIP à la racine du projet Laravel.
3. Lancer `powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE5C.ps1`
4. Tester `php artisan prospects:qualify --dry-run` puis `php artisan prospects:qualify --limit=8`.
5. Ouvrir **Prospection > Suggestions de prospection** avec un compte Admin.

## Tester sans DeepSeek
Le scoring par règles marche gratuitement. La table `discovered_prospects` doit contenir des suggestions `pending` pour obtenir un résultat. Si `Candidates: 0`, c'est normal si la collecte est encore désactivée ou qu'aucun candidat n'a été découvert.

## Activer DeepSeek (optionnel)
Activer les deux options `COMMERCIAL_AI_ENABLED=true` et `PROSPECT_QUALIFICATION_AI_ENABLED=true` et renseigner `DEEPSEEK_API_KEY` **localement dans les secrets du serveur**, jamais dans le chat ni dans Git. Attention, les requêtes sont facturables. Après activation, exécuter `php artisan prospects:qualify --rescore --limit=3` pour tester. Le service n'envoie que nom d'entreprise, ville, catégorie et indicateurs booléens de contact (ni email, ni téléphone, ni données CRM).

## Important
- L'IA évalue la **pertinence** d'une société dont l'existence et l'activité restent à vérifier. Elle ne vérifie pas automatiquement une entreprise.
- Le tri n'est pas un modèle Machine Learning entraîné sur ventes internes.
- Le cron Laravel doit s'exécuter sur le serveur pour que les commandes quotidiennes tournent.
- Un jeu de sources autorisées doit être configuré séparément pour faire fonctionner la collecte réelle Phase 5B.
- Le score ne déclenche aucun contact ni acceptation automatique.
- Testé ici : PHP lint, structure ZIP. Tests Laravel à exécuter sur votre PC.
