# HN BLOOM — Phase 5D: Sources professionnelles CSV (Fallback Offline)

**Objectif :** poursuivre la prospection même quand les endpoints publics Overpass sont injoignables, sans multiplier les pages du CRM. L'import s'intègre au tableau existant `discovered_prospects`, puis à `prospects:qualify` et à la revue administrateur actuelle.

## Installation

Dans le dossier qui contient `artisan`, après extraction de ce ZIP :

```powershell
powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE5D.ps1
```

Nouveaux fichiers seulement : un service, une commande Artisan et un test. **Aucune migration, aucun remplacement des routes ni des vues, aucun appel réseau, aucun envoi de messages.**

## Préparer les sources

Créer/obtenir un export **CSV autorisé** de chaque annuaire, partenaire, fournisseur de données ou salon professionnel. Ne pas copier des données personnelles ni contourner les conditions d'un site. Sources externes en lecture seule / avec accord de licence ; la collecte de fiches d'entreprises n'autorise pas à les démarcher automatiquement.

Déposer par exemple `annuaire-securite.csv` dans `storage/app/prospect-imports/`.

En-tête CSV UTF-8 (`,` ou `;`) :

```csv
company,city,category,phone,email,website,source_url,id
```

- `company` obligatoire, `id` recommandé et stable à l'intérieur d'un même fichier/source.
- Catégories : `installateur`, `revendeur`, `client_final`, `concurrent`.
- `website` et `source_url` doivent être HTTPS publics. Pas d'URL de réseau interne.
- Maximum 300 lignes par appel par défaut (500 maximum) ; nom de fichier simple `.csv` sans dossiers.
- Utiliser `--source=` pour identifier chaque source (`annuaire2026`, `salon_casa`, etc.).

## Tester sans enregistrer

```powershell
php artisan prospects:import-csv annuaire-securite.csv --source=annuaire2026 --dry-run
```

## Importer après vérification de la source

```powershell
php artisan prospects:import-csv annuaire-securite.csv --source=annuaire2026
php artisan prospects:qualify --limit=20
```

Les candidats apparaissent dans la section **Suggestions de prospection** de la page existante `/prospects` pour vérification par l'Admin. Le Commercial reçoit les contacts seulement après validation et attribution.

## Ce que fait cette version

- Importe autant de fichiers de **sources distinctes** que nécessaire, un à un ; ne dépend pas de la disponibilité de l'API OSM.
- Détecte les doublons par identifiant source, Email, téléphone ou URL de site parmi les découvertes ; par Email/téléphone aussi dans les Clients du CRM.
- Ignore les emails incorrects, refuse les URLs non-HTTPS, vérifie le chemin du fichier et ne contacte personne.
- Ne supprime ni ne modifie les prospects existants.

## Ce qui reste à développer

Le **Scraping automatique de plusieurs sites**, l'ingestion automatique d'exports sous licence, le classement IA complet des concurrents, le suivi de consentement et la supervision des tâches restent des évolutions distinctes. Cette version est un **fallback opérationnel d'import**, pas un scraper web automatique ou du machine learning entraîné.

Après test, garder Overpass désactivé tant que la connexion ne marche pas. N'activer le Scheduler quotidien qu'après avoir connecté des sources de données effectivement autorisées et fiables.
