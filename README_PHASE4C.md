# HN BLOOM — Phase 4C | Assistant CRM unifié

Une page légère /assistant-commercial/rapide avec question en langage naturel. Détection des catégories produit/stock, clients, devis, commandes, factures et rédaction email/WhatsApp/relance. Réutilise les services de phases 4A et 4B : aucun accès direct de DeepSeek à SQL, aucune mutation ou envoi. Si une recherche est vague ou contient plusieurs catégories, l'assistant demande de préciser au lieu de retourner des données arbitraires. La rédaction nécessite une clé DeepSeek configurée. Le contexte CRM local reste lisible sans clé.

Pré-requis : phases 4A et 4B installées. Aucune migration. Installer le contenu dans le dossier Laravel (artisan) et ajouter une seule fois la ligne suivante en fin de routes/web.php :

require __DIR__ . '/crm_ai_phase4c.php';

Commandes :
php artisan optimize:clear
php artisan route:list --path=assistant-commercial/rapide
php artisan test --filter=CrmAiIntentServiceTest
php artisan view:cache

**Limites** : routage d'intentions déterministe (pas encore de recherche sémantique multi-tables), au maximum 12 lignes via service de Phase 4B, pas de scraping, pas d'historique de chat, ni de messages envoyés. Les données des résultats peuvent être transmises au fournisseur DeepSeek lors de son activation : obtenir un accord interne et vérifier les obligations de protection des données. Testez d'abord sur données fictives.
