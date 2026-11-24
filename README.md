# HN BLOOM — ATLAS UI simplifiee

Patch local (Windows / Laravel): remplace uniquement deux vues Blade de la page Prospection.

- ATLAS Radar passe en une synthese compacte, sans doublon de la liste.
- Une seule liste de candidats avec "Voir l'analyse ATLAS" dans chaque ligne.
- Filtres, pagination, import CSV, validation et rejet restent en place.
- Aucune migration, aucun appel externe, aucune action de prospection executee.

## Installer

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_ATLAS_UI_Simple_6G.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_UI.ps1
```

Si la compilation Blade echoue, le script restaure les deux fichiers precedents. Un backup local est cree avant la modification.

Ouvrez http://127.0.0.1:8000/prospects puis Ctrl+F5.

Note: le patch ne modifie pas l'index Prospects, les routes, les services ou les donnees.
