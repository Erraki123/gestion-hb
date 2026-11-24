# HN BLOOM — Phase 6B : Prospection intelligente

## Objectif
Amélioration de la page Prospection existante (admin), sans ajout de page : filtres, pagination, score, provenance, import CSV via navigateur.

## Installation Windows
Dans la racine Laravel :

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Phase6B_Prospection_Intelligente.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE6B.ps1
```

## Mode d'emploi
Ouvrir `/prospects` avec un compte admin. Déplier « Recherche & suggestions intelligentes ». Les prospects déjà découverts se filtrent par mot, ville, type, source et score. Importer un CSV d'entreprises publiques/autorisees (moins de 1 Mo), entrer l'identifiant source puis choisir Simulation (cochée par défaut) ou import réel. Seule la colonne `company` est obligatoire; autres colonnes facultatives : city,category,phone,email,website,source_url,id. Les CSV sont supprimés du stockage temporaire après traitement.

## Restrictions
- Aucune découverte Internet supplémentaire : toujours pas de scraping multi-sites automatique.
- Aucun message, email, devis ou ajout de clients envoyé automatiquement.
- Acceptation et rejet réservés à l'admin; les commerciaux gardent leur page de suivi classique.
- Ne chargez que des listes provenant de sources licites/autorisees.
- Les scores représentent des estimations, pas une garantie de qualité.
- Installer Phase5B/5C/5D avant cette phase.

## Tests
Installer exécute le test des filtres, compilation des vues et vérification de la route. Tester manuellement l'import CSV en mode simulation, puis réel avec un jeu de données autorisé. Pas de migrations.

## Retour arrière
Le dossier `backup-phase6B-*` contient les fichiers originaux (dont routes/web.php). Pour annuler, restaurer le backup et supprimer les nouveaux fichiers Phase6B, puis `php artisan optimize:clear`.
