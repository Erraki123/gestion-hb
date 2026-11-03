{{-- Modal de confirmation d'action sur un devis. Ouvrir avec :
     $dispatch('quote-action', { action:'/url', method:'PATCH', status:'refuse',
                                 title:'…', text:'…', button:'Confirmer', danger:true }) --}}
<div
    x-data="{ a: { action: '', method: 'POST', status: '', title: '', text: '', button: 'Confirmer', danger: false } }"
    x-on:quote-action.window="a = Object.assign({ action: '', method: 'POST', status: '', title: '', text: '', button: 'Confirmer', danger: false }, $event.detail); $dispatch('open-modal', 'quote-action')"
>
    <x-modal name="quote-action" maxWidth="md">
        <form method="POST" :action="a.action" class="p-6">
            @csrf
            <input type="hidden" name="_method" :value="a.method">
            <input type="hidden" name="status" :value="a.status" :disabled="!a.status">

            <h2 class="text-lg font-semibold text-gray-900" x-text="a.title"></h2>
            <p class="mt-2 text-sm text-gray-500" x-text="a.text"></p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="text-sm text-gray-500 hover:text-gray-900">Annuler</button>
                <button class="rounded-lg px-5 py-2.5 text-sm font-semibold"
                        :class="a.danger ? 'bg-red-600 text-white hover:bg-red-500' : 'bg-gold-500 text-gray-950 hover:bg-gold-400'"
                        x-text="a.button"></button>
            </div>
        </form>
    </x-modal>
</div>
