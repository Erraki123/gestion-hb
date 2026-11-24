# HN BLOOM — Phase 4E: Chat premium + recherche en langage naturel

## Modifications
- Remplace `resources/views/layouts/_ai_floating.blade.php` par une interface de conversation (Admin + Commercial) dans les tons noir / or, plus spacieuse et responsive.
- Remplace `app/Services/CrmAiIntentService.php` : une question comme « le client qui abdo » recherche désormais `abdo` sans deuxième champ.
- Remplace `app/Services/CrmAiAnswerService.php` : réponses locales en texte lisible au lieu du JSON brut lorsque DeepSeek est désactivé.
- Ajoute `tests/Unit/CrmAiNaturalLanguageTest.php`.

Aucune migration ni nouvelle route. Utilise toujours la route existante `admin.ai-floating.ask` pour Admin et Commercial.

**Limites** : ce n'est pas encore un agent universel. Les recherches passent par les services CRM Phase 4B, limités à cinq domaines (clients, produits, devis, commandes, factures) et 12 lignes. La conversation est visuelle, conservée dans la page courante; les questions sont traitées indépendamment et ne disposent pas encore de mémoire contextuelle côté serveur. Aucune modification ni envoi automatique. Les droits Laravel et le filtrage des données restent appliqués.

## Tests
```
php artisan optimize:clear
php artisan test --filter=CrmAiIntentServiceTest
php artisan test --filter=CrmAiNaturalLanguageTest
php artisan view:cache
php artisan serve
```

Vérifier sous un compte Admin et un compte Commercial, dans le navigateur. L'interface ne doit pas inclure de champ secondaire de référence. Tester « le client qui abdo ». Si plusieurs clients correspondent, le service affiche un extrait limité au lieu d'inventer un choix.

Pour une migration de production, protéger par sauvegarde, revue de code et tests de permissions plus complets. Aucun code de sécurité ne remplace la nécessité d'un contrôle de la confidentialité des données envoyées au fournisseur IA.
