# HN BLOOM — Phase 6A : suivi conversationnel client

## But
Dans la meme fenetre IA flottante, permettre des questions de suivi :
- « Cherche le client Abdo »
- « Et ses devis ? »
- « Et ses factures impayees ? »

## Securite
- Pas de migration ni de nouvelle page.
- Le contexte de derniere fiche client est conserve dans la session du compte pendant 30 minutes. Le client est revalide en base a chaque question, en respectant les scopes Eloquent (Admin / Commercial).
- Pas d'acces SQL direct par le modele IA, pas d'action d'ecriture ni d'envoi.
- Si plusieurs clients correspondent au meme terme, l'assistant demande de preciser plutot que de choisir arbitrairement.
- Montants de factures restants indicatifs : verifier avoirs/cheques via l'ecran comptable.

## Installation
1. Telecharger ZIP dans Downloads.
2. Ouvrir PowerShell dans le dossier Laravel (artisan).
3. `Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Phase6A_Contexte_Client.zip" -DestinationPath . -Force`
4. `powershell -ExecutionPolicy Bypass -File .\INSTALL_PHASE6A.ps1`
5. `php artisan serve`

## Tests manuels
Dans un dossier ou le client existe et est accessible :
- Cherche le client Abdo
- Et ses devis ?
- Et ses factures impayees ?
- Nouvel onglet/log in commercial : verifier absence d'acces aux clients des autres commerciaux.

## Limites
C'est une premiere memo de conversation pour un client selectionne, pas encore une memoire generale autonome ni ML. Les autres questions suivent le moteur existant de Phase 5A. Le test unitaire controle extraction et detection de suivi. Les scenarios DB/permission doivent etre testes localement en situation.
