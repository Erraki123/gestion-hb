HN BLOOM | PACK DE MODÈLES PDF
================================
3 thèmes au choix, 1 vue dynamique pour devis, facture, reçu, bon de commande,
avoir et bon de livraison. Palette ivoire, champagne et noir typographique.
QR code fourni par vous inclus en pied de page, avec l'adresse www.hnbloom.ma.

CONTENU
- resources/views/pdf/document.blade.php : modèle complet dynamique
- resources/views/pdf/templates/_styles.blade.php : styles des 3 thèmes
- public/images/hn-bloom-logo.png : logo HN transparent
- public/images/hn-bloom-website-qr.png : votre QR code original
- config/commerce.php : informations entreprise et thème par défaut
- EXEMPLE-CONTROLEUR.php.txt : exemple de données à transmettre

LES 3 THÈMES
signature : le plus proche de votre exemple, fond ivoire et accents champagne.
prestige  : en-tête plus affirmé, noir limité aux textes et au tableau.
editorial : mise en page structurée, lignes fines et titres éditoriaux.

INSTALLATION
1. Sauvegardez votre projet Laravel.
2. Extrayez le ZIP à la racine du projet, en fusionnant les dossiers.
3. Si config/commerce.php existe déjà, fusionnez les valeurs, ne l'écrasez pas.
4. Dans votre contrôleur PDF, utilisez Pdf::loadView('pdf.document', [...])
   avec le tableau 'doc' illustré dans EXEMPLE-CONTROLEUR.php.txt.
5. Copiez les valeurs réelles de votre application dans ce tableau.
6. Videz les caches :
   php artisan view:clear
   php artisan config:clear
   php artisan optimize:clear

CHOISIR UN THÈME
- Dans .env : HN_BLOOM_PDF_TEMPLATE=signature
- Pour changer : signature, prestige ou editorial
- Ou passer 'template' => 'prestige' à loadView().

TYPES
'devis', 'facture', 'recu', 'commande', 'avoir', 'bon_livraison'.
Le titre est sélectionné automatiquement. Les dates, client, lignes, totaux,
statut, notes, conditions et paiement sont alimentés par les données transmises.

IMPORTANT : INTÉGRATION
Votre ancien ZIP ne contenait que des partials partagés, pas les contrôleurs
ni toutes les vues qui produisent les PDF. Ce nouveau pack fournit une vue
complète générique et un exemple de branchement, mais ne peut pas deviner
les noms exacts de vos variables, modèles ou routes existants. Il faut adapter
l'appel dans chaque contrôleur PDF à votre structure de données.
Le QR fourni est intégré tel quel. Son contenu n'a pas pu être vérifié ici :
scannez-le avec un téléphone pour confirmer qu'il ouvre bien https://hnbloom.com.
Si ce n'est pas le cas, fournissez un QR exporté depuis votre site.
