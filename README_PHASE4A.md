# HN BLOOM — Phase 4A: Assistant Commercial DeepSeek (brouillons seulement)

## Contenu
Nouveaux fichiers seulement : Controller, Service, config, route séparée, vue Blade, 2 tests. Aucun modèle ou migration existant remplacé. Aucun envoi de SMS/WhatsApp/Email, aucune modification de prix, remise, devis ou commande.

## Pré-requis
Laravel et les phases précédentes installés, rôle `admin` ou `commercial`, accès réseau uniquement quand le service est activé. Utiliser une copie de test avant production.

## Installation PowerShell (depuis la racine Laravel)
1. Télécharger le ZIP dans Downloads et ouvrir PowerShell à la racine Laravel.
2. Sauvegarder `routes/web.php` et `resources/views/layouts/navigation.blade.php` et vos données avant déploiement.
3. `Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Phase4A_DeepSeek_Brouillons.zip" -DestinationPath . -Force`
4. Ajouter UNE seule fois en bas de `routes/web.php` : `require __DIR__ . '/commercial_ai_phase4a.php';`
5. Pour ajouter le menu, avant `@endphp` dans `resources/views/layouts/navigation.blade.php` :
   `$groups[] = ['title' => 'Assistant', 'items' => [['Assistant IA', route('commercial-ai.index'), 'message-circle', request()->routeIs('commercial-ai.*')]]];`
   Vérifier que l'icône 'message-circle' existe dans le composant d'icônes du projet, sinon remplacer par une icône déjà utilisée dans le menu (par ex. 'users').
6. `php artisan optimize:clear`
7. `php artisan route:list --path=assistant-commercial`
8. `php artisan test --filter=CommercialAiServiceTest`
9. `php artisan view:cache`
10. Ouvrir `http://127.0.0.1:8000/assistant-commercial` en étant connecté.

## Activation (à faire uniquement après validation technique et politique de confidentialité)
Paramètres du serveur, gérés comme secrets, jamais commités :
- COMMERCIAL_AI_ENABLED=true
- DEEPSEEK_API_KEY=clé privée (ne jamais la partager)
- DEEPSEEK_MODEL=deepseek-flash

Puis `php artisan config:clear`. Désactivé par défaut tant que non configuré.

## Tests et garde-fous
- Les tests sont simulés : aucun appel réseau réel.
- Les entrées sont validées, les requêtes limitées à 6/minute par utilisateur via throttle.
- L'IA ne reçoit **que** le contexte saisi volontairement dans le formulaire, sans requête directe à la base des clients.
- Vérifier les données entrées : ne pas soumettre de données personnelles ou confidentielles sans cadre légal et accord de HN BLOOM.
- Aucun historique de contenu IA stocké dans cette phase (il faudra ajouter un audit minimal avant usage professionnel complet).
- Aucune génération de devis/commande, aucun prix modifié, aucun envoi automatique.
- Il faut une vérification humaine des brouillons. Ne pas utiliser tel quel en production sans vérifications, limites budgétaires et politique de protection des données.
