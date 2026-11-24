HN BLOOM - COULEURS PDF LOGO OR

Modifie uniquement resources/views/pdf/chrome/style.blade.php.
Ne modifie aucun layout, aucune donnees, aucun calcul, aucune permission, aucune route, aucun renderer.

Windows PowerShell (a la racine Laravel):
1. Copy-Item .\resources\views\pdf\chrome\style.blade.php .\resources\views\pdf\chrome\style.blade.php.backup -Force
2. Expand-Archive -Path "$env:USERPROFILE\Downloads\HNB_PDF_GOLD_LOGO_PATCH.zip" -DestinationPath "." -Force
3. php artisan view:clear
4. Tester les PDFs localement
5. .\deploy.ps1 (si tests OK)
