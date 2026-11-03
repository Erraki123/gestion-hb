# Guide complet pour utiliser l’application de gestion commerciale

Ce document est conçu pour un débutant, surtout si tu es admin. Il explique comment l’application fonctionne, quelles sont les étapes métier, quels sont les menus, les écrans et les boutons principaux à utiliser.

## 1) Connexion et rôles

### Compte admin de démonstration
- Email : admin@demo.local
- Mot de passe : Demo123!

### Compte commercial de démonstration
- Email : commercial@demo.local
- Mot de passe : Demo123!

### Rôles dans le système
1. Administrateur
   - Gère tout le système.
   - Crée et active les commerciaux.
   - Gère les fournisseurs, achats, règles de commission, paramètres, exports comptables.
   - A accès à plusieurs écrans de gestion avancée.

2. Commercial
   - Gère les clients.
   - Crée les devis, commandes, paiements et suit sa performance.
   - Ne voit que ses propres clients et ses propres documents.

---

## 2) Menu principal de l’application

À gauche, tu as la navigation. Voici tout ce que l’on trouve dans l’interface :

### Ventes
- Clients
- Devis
- Commandes
- Livraisons
- Factures
- Retours

### Finance
- Paiements
- Créances
- Commissions

### Catalogue
- Produits

### Analyse
- Rapports

### Achats (admin uniquement)
- Fournisseurs
- Commandes fournisseur
- Factures fournisseur

### Administration (admin uniquement)
- Commerciaux
- Règles commission
- Paramètres
- Corbeille
- Journal d’activité
- Export comptable
- Système

---

## 3) Vue d’ensemble du fonctionnement métier

L’application suit un flux commercial classique :

1. Créer un client
2. Créer un devis
3. Convertir le devis en commande
4. Livrer la commande
5. Émettre la facture
6. Enregistrer le paiement
7. Suivre les créances, retours et commissions

Côté achats (admin) :

1. Créer un fournisseur
2. Créer une commande fournisseur
3. Valider la réception des produits
4. Enregistrer la facture fournisseur
5. Enregistrer le paiement fournisseur

---

## 4) Le tableau de bord

Le tableau de bord est la page d’accueil principale.

Tu y trouves :
- Chiffre d’affaires HT et TTC
- Montant encaissé
- Reste à encaisser
- Factures en retard
- Nombre de clients
- Nombre de devis et de commandes
- Commissions calculées, à verser, versées
- Détail par commercial
- Alertes stock bas

### Boutons / actions courantes sur le dashboard
- Cliquer sur une carte du dashboard pour ouvrir la liste correspondante
- Exemple : cliquer sur Chiffre d’affaires ou Factures pour aller vers les listes détaillées
- Cliquer sur un commercial pour voir son détail

---

## 5) Gestion des clients

### Menu : Clients

Côté client, l’admin et le commercial peuvent :
- Ajouter un client
- Modifier un client
- Voir le dossier client
- Suivre les factures et paiements du client
- Générer le relevé PDF
- Ajouter des notes, activités et pièces jointes

### Actions typiques sur la page clients
- Bouton Nouveau client
- Bouton Enregistrer
- Bouton Modifier
- Bouton Supprimer
- Bouton Détail / Voir
- Bouton Relevé PDF

### Ce qu’il faut renseigner pour un client
- Nom
- Société
- Téléphone
- Email
- Adresse
- Ville
- ICE
- Statut (prospect/actif/inactif)
- Commentaire

### Rôle de l’admin sur les clients
- L’admin peut affecter le commercial responsable du client.
- Le commercial ne voit que ses propres clients.

---

## 6) Gestion des produits

### Menu : Produits

La gestion de catalogue permet de :
- Créer un produit
- Modifier le prix et le stock
- Gérer le taux de TVA
- Suivre le stock minimum
- Voir les mouvements de stock

### Champs importants dans un produit
- Nom
- SKU
- Catégorie
- Unité
- Description
- Prix d’achat
- Prix de vente
- Taux TVA
- Stock actuel
- Stock minimum
- Actif / inactif

### Actions typiques
- Nouveau produit
- Enregistrer
- Modifier
- Voir détail
- Ajouter mouvement de stock
- Vérifier stock bas

### À retenir
- Les produits sont utilisés dans les devis, commandes, factures et livraisons.
- Le stock est important pour les commandes et les livraisons.

---

## 7) Gestion des devis

### Menu : Devis

Les devis sont la première étape commerciale.

### Workflow
1. Choisir un client
2. Ajouter des lignes de produits
3. Définir quantité, prix, remise, TVA
4. Sauvegarder le devis
5. Le devis peut ensuite être envoyé / validé / refusé / accepté

### États d’un devis
- Brouillon
- Envoyé
- Accepté
- Refusé
- Expiré

### Boutons typiques
- Nouveau devis
- Enregistrer
- Modifier
- Dupliquer
- Convertir en commande
- PDF
- Changer le statut

### Important
- Un devis accepté peut être converti en commande.
- Un devis en brouillon peut être modifié ou supprimé.

---

## 8) Gestion des commandes

### Menu : Commandes

La commande est la suite logique du devis quand le client valide.

### Workflow
- Créer depuis un devis accepté ou directement via le système
- Vérifier les lignes
- Confirmer la commande
- Le stock peut être déduit selon la configuration
- Préparer la livraison
- Créer la facture si nécessaire

### États des commandes
- Nouvelle
- Confirmée
- Terminée
- Annulée

### Boutons / actions
- Voir
- Modifier si nécessaire
- Changer le statut
- Créer livraison
- Créer facture
- Annuler

### Importante
- Une commande confirmée peut déclencher la sortie de stock.
- Une commande annulée peut réintégrer le stock.

---

## 9) Gestion des livraisons

### Menu : Livraisons

Les livraisons permettent d’enregistrer le réel départ des marchandises au client.

### Workflow
1. Ouvrir une commande
2. Créer un bon de livraison
3. Valider la livraison
4. Suivre les quantités livrées

### Boutons typiques
- Créer livraison
- Enregistrer
- Valider
- Annuler
- Voir PDF

### À retenir
- La livraison suit la quantité livrée par produit.
- Une livraison validée modifie l’état du suivi de commande.

---

## 10) Gestion des factures

### Menu : Factures

La facture est l’étape de facturation commerciale.

### Processus
- Une facture peut être créée depuis une commande
- Elle contient les lignes de produits et les montants
- Tu peux voir l’état : émise, payée, partiellement payée, annulée

### États courants
- Émise
- Partiellement payée
- Payée
- Annulée

### Boutons typiques
- Voir facture
- Émettre
- Créer un avoir
- PDF
- Modifier / supprimer si le statut le permet
- Valider un paiement

### Important
- La facture est liée aux paiements et aux avoirs.
- Le système peut calculer le solde restant à payer.

---

## 11) Gestion des paiements

### Menu : Paiements

Les paiements enregistrent les encaissements client.

### Modes de paiement
- Espèces
- Virement
- Chèque

### Actions typiques
- Enregistrer un paiement
- Valider
- Annuler un paiement
- Gérer un chèque
- Voir le PDF de paiement

### Ce que tu peux suivre
- Montant encaissé
- Date
- Méthode de paiement
- Référence
- Statut du chèque

---

## 12) Gestion des créances

### Menu : Créances

Cette partie sert à suivre les montants dus par les clients.

Tu peux voir :
- Montant restant dû
- Factures impayées
- Factures en retard
- État des paiements

### À retenir
- La créance est le solde restant à encaisser.
- C’est un outil de suivi financier important pour l’admin et le commercial.

---

## 13) Gestion des retours

### Menu : Retours

Les retours permettent de gérer les retours produits client.

### Cas d’usage
- Un client retourne un produit
- Le système crée un avoir partiel ou total selon les quantités
- La facture concernée est impactée

### Actions typiques
- Créer un retour sur une facture
- Modifier le retour
- Valider le retour
- Annuler le retour
- Voir le document

### Important
- Le système prend en compte les quantités retournées et les montants d’avoirs.
- Cela évite de perdre le suivi d’une facture déjà émise.

---

## 14) Gestion des commissions

### Menu : Commissions

Les commissions servent à payer les commerciaux selon leur performance.

### Deux grandes parties
- Règles de commission
- Calcul et paiement des commissions

### Ce que l’admin peut faire
- Créer des règles de commission
- Choisir la méthode : pourcentage, commission fixe, etc.
- Voir l’historique des règles
- Valider les commissions à verser

### Ce que le commercial voit
- Sa performance
- Le montant calculé
- Les commissions à verser
- Le détail des paiements

---

## 15) Gestion des fournisseurs et achats (admin uniquement)

### Menu : Achats

L’admin gère le cycle achat.

### 1. Fournisseurs
- Ajouter un fournisseur
- Modifier
- Voir détails
- Suivre ses factures et paiements

### 2. Commandes fournisseur
- Créer une commande fournisseur
- Ajouter des produits
- Définir quantité, prix d’achat, TVA
- Confirmer la commande

### 3. Réceptions
- Valider la réception des produits
- Vérifier les quantités reçues
- Suivre les différences entre commande et réception

### 4. Factures fournisseur
- Enregistrer la facture fournisseur
- Définir les lignes, bases HT et TVA
- Suivre le montant total

### 5. Paiements fournisseur
- Enregistrer le paiement fournisseur
- Suivre les dettes fournisseurs

### À retenir
- L’admin a ici tout le cycle achat complet : fournisseur → commande → réception → facture → paiement.

---

## 16) Administration (admin uniquement)

### Commerciaux
- Créer un compte commercial
- Modifier son profil, email, téléphone, mot de passe
- Activer ou désactiver un commercial
- Supprimer si nécessaire

### Règles commission
- Définir la règle qui s’applique selon le commercial
- L’historique est conservé

### Paramètres
- Ajuster les paramètres globaux du système
- Gérer le mode de saisie des prix HT/TTC
- Gérer les taux de TVA
- Définir les règles d’usage du stock

### Corbeille
- Voir les éléments supprimés
- Restaurer si besoin

### Journal d’activité
- Voir les actions réalisées sur les éléments du système
- Identifier qui a modifié quoi

### Export comptable
- Exporter les données comptables en Excel
- Utiliser les périodes (mois, trimestre, année, personnalisé)

### Système
- Gérer le système et les opérations administratives globales

---

## 17) Rapports

### Menu : Rapports

La section rapports permet d’analyser les performances.

Tu peux générer des rapports sur :
- Ventes
- Paiements
- Créances
- Commercials
- Produits
- État du stock
- Activités générale

### Actions courantes
- Ouvrir le rapport
- Exporter PDF ou Excel
- Choisir une période

---

## 18) Boutons importants à connaître

Voici les boutons que tu rencontreras souvent :

- Nouveau
- Enregistrer
- Modifier
- Voir / Détail
- Supprimer
- Valider
- Annuler
- Dupliquer
- Convertir en commande
- Imprimer / PDF
- Relevé PDF
- Exporter
- Changer le statut
- Ajouter une ligne
- Ajouter un produit
- Ajouter un client
- Ajouter un fournisseur

---

## 19) Workflow conseillé pour un admin débutant

### Cas simple : vendre un produit
1. Ouvrir Clients
2. Créer un client
3. Ouvrir Produits
4. Vérifier ou créer un produit
5. Ouvrir Devis
6. Créer un devis pour le client
7. Valider le devis
8. Convertir en commande
9. Créer la livraison
10. Créer la facture
11. Enregistrer le paiement
12. Suivre les créances

### Cas achat simple
1. Ouvrir Fournisseurs
2. Créer un fournisseur
3. Ouvrir Commandes fournisseur
4. Créer une commande
5. Valider la réception
6. Enregistrer la facture fournisseur
7. Enregistrer le paiement fournisseur

---

## 20) Conseils pratiques

1. Commence toujours par gérer les clients et les produits.
2. Vérifie le stock avant de confirmer une commande.
3. Les devis doivent être bien validés avant la conversion en commande.
4. Fais attention aux montants HT/TTC et aux taux de TVA.
5. Les factures et paiements doivent être suivis quotidiennement.
6. Vérifie les commissions et les créances régulièrement.
7. Utilise les rapports pour suivre la performance.

---

## 21) En résumé

Cette application sert à gérer toute la chaîne commerciale :
- Clients
- Produits
- Devis
- Commandes
- Livraisons
- Factures
- Paiements
- Retours
- Commissions
- Achats fournisseurs
- Comptabilité / exports
- Reporting / activité / paramètres

Tu n’as pas besoin de tout maîtriser d’un coup. Il faut commencer par le cycle principal :

Client → Devis → Commande → Livraison → Facture → Paiement

Puis, si tu es admin, tu peux étendre vers :

Fournisseur → Achat → Réception → Facture fournisseur → Paiement fournisseur

Et ensuite tu gères :

Commissions, rapports, journal d’activité, paramètres, export comptable.

---

## 22) Petit résumé très court

Si tu veux travailler rapidement dans l’application :
- Va dans Clients pour créer le client
- Va dans Produits pour vérifier les articles
- Va dans Devis pour – créer le devis
- Convertis en Commande
- Crée la Livraison
- Crée la Facture
- Enregistre le Paiement
- Suis les Créances et les Rapports

C’est le cœur de l’application.

---

## 23) Astuce finale

Quand tu es perdu :
- Commence par le Tableau de bord
- Ensuite va dans les modules dans l’ordre : Clients → Produits → Devis → Commandes → Factures → Paiements
- Pour l’admin, ajoute ensuite les modules Achats et Administration

Tu peux aussi relire ce guide à chaque fois que tu veux comprendre la logique de l’application.
