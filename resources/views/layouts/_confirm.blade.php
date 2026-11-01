<div x-data="{ open: false, message: '', cb: null }" x-cloak
     @confirm-request.window="message = $event.detail.message; cb = $event.detail.onConfirm; open = true"
     @keydown.escape.window="open = false">
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="open = false"></div>

        <div class="relative w-full max-w-md rounded-xl border-t-4 border-gold-500 bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-semibold text-gray-900">Confirmation</h3>
            <p class="mt-2 text-sm text-gray-600" x-text="message"></p>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="open = false"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Annuler</button>
                <button type="button" @click="open = false; cb && cb()"
                        class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-gold-400 hover:bg-black">Confirmer</button>
            </div>
        </div>
    </div>
</div>