# HN BLOOM - Phase 3A : UltraMsg (envoi sortant)

Pré-requis : Phases 1B et 2A déjà installées. Aucune migration.

## Configuration locale
Ajouter manuellement dans la configuration locale (ne jamais partager les secrets) :

WHATSAPP_PROVIDER=ultramsg
NOTIFY_WHATSAPP=false
ULTRAMSG_INSTANCE_ID=instance12345
ULTRAMSG_TOKEN=VOTRE_TOKEN_PRIVE

NOTIFY_WHATSAPP reste false lors de l'installation et des tests. Pour activer l'envoi, seulement après validation du consentement et de l'instance, passer à true puis exécuter php artisan config:clear.

Le système garde les paramètres WABridges si WHATSAPP_PROVIDER=wabridges.
Les messages sont envoyés par DocumentSender vers SendDocumentJob, avec enregistrement dans message_logs.
Le champ do_not_contact de prospect_details bloque maintenant l'envoi au moment de la mise en file ET à l'exécution du Job.
Le contrôle de validation des devis sensibles installé en Phase 1B est conservé.

ATTENTION: UltraMsg n'est pas l'API officielle Meta demandée dans le cahier des charges. Validation de la direction nécessaire. L'envoi sortant n'implémente pas encore les réponses, webhooks, réconciliation des états ni une garantie d'idempotence côté fournisseur. Désactiver NOTIFY_AUTO_QUOTE et NOTIFY_AUTO_INVOICE au début. UltraMsg peut accepter en file d'attente sans livrer immédiatement.

## Vérifications
php -l app/Services/WhatsAppService.php
php -l app/Services/DocumentSender.php
php -l app/Jobs/SendDocumentJob.php
php artisan test --filter=UltraMsgIntegrationTest
php artisan optimize:clear

Tests mockés : aucune requête réseau. Attention, la suite Laravel complète peut avoir d'autres migrations incompatibles avec SQLite.
