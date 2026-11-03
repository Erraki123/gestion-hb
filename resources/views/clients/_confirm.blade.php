{{-- Modal de suppression, réutilisable. Ouvrir avec :
     $dispatch('confirm-delete', { action: '/url', title: 'Supprimer ce client ?', text: 'Détail…' }) --}}
<div
    x-data="{ action: '', title: '', text: '' }"
    x-on:confirm-delete.window="action = $event.detail.action; title = $event.detail.title; text = $event.detail.text || ''; $dispatch('open-modal', 'confirm-delete')"
>
    <x-modal name="confirm-delete" maxWidth="md">
        <form method="POST" :action="action" class="p-6">
            @csrf @method('DELETE')
            <h2 class="text-lg font-semibold text-gray-900" x-text="title"></h2>
            <p class="mt-2 text-sm text-gray-500" x-text="text"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                <button class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-500">Supprimer</button>
            </div>
        </form>
    </x-modal>
</div>