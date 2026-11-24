{{--
    Fichiers d'un paiement. Variable : $offCheque (expression Alpine : quand le champ « chèque » est désactivé)
    Les fichiers restent dans l'application (disque privé) et s'ouvrent depuis la liste des paiements.
--}}
<div x-show="method === 'cheque'" x-cloak>
    <label class="mb-1 block text-xs font-medium text-gray-600">Scan ou photo du chèque (facultatif)</label>
    <input type="file" name="cheque_scan" accept="image/*,.pdf" :disabled="{{ $offCheque }}" class="block w-full text-sm">
    <p class="mt-1 text-xs text-gray-500">JPG, PNG ou PDF, 5 Mo maximum. Sur téléphone : le bouton ouvre l'appareil photo. Vous pouvez aussi saisir les informations à la main ci-dessus.</p>
</div>

<div>
    <label class="mb-1 block text-xs font-medium text-gray-600">Autre document (facultatif)</label>
    <input type="file" name="other_file" accept="image/*,.pdf" class="block w-full text-sm">
</div>
