# HN BLOOM — Phase 5A: Fondations Multi-Agent CRM

Ce patch conserve **exactement** les pages et le widget flottant existants.

## Nouveautés
- Orchestrateur de **plusieurs outils CRM en lecture seule** : Clients, Produits/Stock, Devis, Commandes, Factures.
- Questions multi-domaines comme « Montre les clients et les factures » ou listes comme « Montre les produits en stock ».
- Réponses bornées (maximum 5 affichés par domaine, résultats provenant d'une sélection plafonnée à 12).
- Données filtrées par les modèles Laravel et leurs scopes de visibilité. Aucun accès SQL direct du modèle IA.
- Les demandes d'actions sensibles restent non exécutées.
- Les recherches web/scraping/concurrence ne sont **pas encore implémentées** : le système l'annonce sans inventer de résultats.
- Les questions mono-domaine et les brouillons continuent d'utiliser les services Phase 4A–4E.

## Prérequis
Phases 4A, 4B, 4C, 4D (Admin + Commercial), 4E et corrections déjà installées.
L'endpoint `admin/ai-floating/ask` doit être présent avec `role:admin,commercial`.

## Installation (Windows PowerShell)
1. ZIP dans Downloads.
2. Depuis la racine de Laravel :

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Phase5A_MultiAgent_Fondations.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE5A.ps1
```

Ce script **sauvegarde les fichiers existants avant de copier** le dossier `PATCH`.

## Vérification
```powershell
php artisan test --filter=CrmAgentPlannerTest
php artisan route:list --path=admin/ai-floating -v
php artisan view:cache
php artisan serve
```

Dans le chat flottant, essayer :
- `Montre les clients et les factures`
- `Montre les produits en stock`
- `Quel est le stock du produit "REF-REELLE" ?`
- `Rédige un WhatsApp court pour présenter HN BLOOM`
- `Recherche web concurrents à Casablanca` (retour explicite : fonction non reliée)

**Important** : `CAM-001` n'est qu'un exemple, utilisez un SKU réel. Les listes ne sont pas des rapports complets. Tester avec Admin et Commercial avant tout déploiement. Pas de migration nécessaire. Le connecteur DeepSeek et ses clés sont inchangés.
