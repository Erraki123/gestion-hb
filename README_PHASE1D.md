# HN BLOOM — Phase 1D (securite des comptes et prix)

Prerequis: Phase 1A, 1B, 1C installees. Travailler sur une copie de test.

Contenu:
- routes/auth.php : desactivation inscription publique (GET/POST /register) ; les commerciaux restent crees via /admin/commerciaux.
- app/Http/Controllers/ProductController.php : defense en profondeur sur les actions d'ecriture admin et non-chargement du prix d'achat/historique prix pour les commerciaux.
- tests/Feature/Auth/RegistrationTest.php : tests adaptes a la fermeture des inscriptions.

Aucune migration. Ne remplace PAS routes/web.php ni les phases 1A-1C.

PowerShell depuis la racine Laravel (fichier artisan):
  Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Phase1D_Securite.zip" -DestinationPath . -Force
  php artisan optimize:clear
  php artisan route:list --path=register
  php artisan test --filter=RegistrationTest

Attendu: aucune route /register ; deux tests verts.

Tests manuels:
- Verifier que /login fonctionne et que /register renvoie 404.
- Admin peut creer un commercial sur /admin/commerciaux.
- Commercial ne peut pas modifier des produits (403) ; ne voit pas prix d'achat ni historique des prix.
- Admin conserve prix d'achat, marge, edition du catalogue.

A poursuivre: audit des autres exports/rapports, documents publics, mouvements stock, factures et permissions Support technique. Cette phase ne certifie pas la securite totale.
