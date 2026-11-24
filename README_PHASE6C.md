# HN BLOOM CRM — Phase 6C / Qualite des prospects

## Contenu

- Bloque les doublons a la creation manuelle d'un prospect lorsque les emails sont equivalents (casse/espaces) ou lorsque les numeros marocains sont equivalents (`06...`, `+2126...`, `002126...`).
- Ne divulgue pas les fiches privees d'autres commerciaux.
- Corrige les dernieres sequences mojibake (`Etape`, `Fermer`) detectees dans la page Prospection.
- Aucun enregistrement supprime ou modifie; aucune migration; aucune API externe; aucun message sortant.

## Installation

Extraire a la racine Laravel, puis :

```powershell
powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE6C.ps1
```

## Important

Une base volumineuse beneficiera d'une colonne `phone_normalized` indexee : la detection de telephone actuelle parcourt les seules paires ID/telephone. Cette phase ne constitue pas un moteur de scraping automatique : les sources de donnees doivent etre configurees et autorisees separement. Le niveau de protection contre des inscriptions concurrentes n'est pas transactionnel/unique au niveau SQL.

Avant les modifications, le script fait une sauvegarde des deux fichiers concernes dans `backup-phase6c-*`. L'installation s'arrete si la structure du controleur differe de celle attendue.
