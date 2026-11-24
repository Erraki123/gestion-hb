# HN BLOOM — Audit local avant déploiement

Exécuter dans PowerShell, à la racine Laravel :

```powershell
powershell -ExecutionPolicy Bypass -File .\AUDIT_AVANT_DEPLOIEMENT.ps1
```

Ce script ne lance ni migrations, ni collecte externe, ni envoi Email/WhatsApp, ni requête DeepSeek. Il lit la liste des migrations, les routes et le Scheduler, puis lance une sélection de tests. Les fichiers `storage/app/audit-local/audit-*.txt` contiennent le rapport sans paramètres secrets intentionnellement. Vérifier quand même son contenu avant partage.

## Étapes à confirmer avant mise en production
- App installée et navigation testée avec Admin et Commercial.
- Tests des rôles, des approbations, du stock, des remises et des documents PDF.
- Vérification de l'absence de secrets dans le code et l'archive/depot.
- Configuration production sûre (HTTPS, APP_DEBUG=false, APP_KEY stable, session, queue, scheduler).
- Sauvegarde MySQL, restauration réellement testée, politique de rétention.
- Vérification des conditions d'usage des sources externes, anti-doublons, désabonnement.
- Test réel à petite échelle de chaque service activé : UltraMsg, Email, DeepSeek, collecte.
- Journalisation, alertes, surveillance et procédure de retour arrière.

Ce rapport ne suffit pas pour déclarer l'application prête à 100 %.
