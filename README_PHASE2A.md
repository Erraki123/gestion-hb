# HN BLOOM — Phase 2A : Prospection simple

## Fonctionnalités
- Une page Prospection /prospects (pas de menu supplémentaire complexe).
- Ajout rapide basé sur la table `clients` existante, sans doublonner le CRM.
- Catégorie et étapes commerciales, relances, protection « Ne plus contacter ».
- Badge de notifications dans la navigation Admin pour les validations et les relances en retard.
- Les commerciaux ne voient que leurs prospects via le scope existant Client et les contrôles Gate.
- Aucun message automatique n'est envoyé.

## Installation
1. Sauvegarde des fichiers et de la base avant installation. Installer APRÈS Phase 1A–1D.
2. Extraire le ZIP à la racine Laravel (dossier `artisan`).
3. `php artisan migrate`
4. `php artisan optimize:clear`
5. `php artisan route:list --path=prospects`
6. Tester en local avec un admin et un commercial.

## Limites à développer
- Notifications internes en temps réel / email et WhatsApp restent à faire.
- Prospects existants sans détails sont affichés avec valeurs par défaut.
- Recherche de doublons à renforcer : formats internationaux des numéros, concurrence.
- Prospects au statut « gagné » restent liés aux clients existants; conversion automatisée non ajoutée.
- Vérifier l'interface et les tests applicatifs sur votre machine avant production.
