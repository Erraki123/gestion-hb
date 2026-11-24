<x-app-layout dark-header>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="!text-lg">WhatsApp · Réponses</h2>
                <p class="text-xs text-gray-400">Messages entrants · HN BLOOM</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-amber-400">← Dashboard</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4">
            <div>
                <h3 class="font-semibold text-gray-900">Boîte de réception</h3>
                <p class="text-sm text-gray-500">Consultation uniquement — aucune réponse automatique.</p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">{{ $unreadCount }} non lus</span>
        </div>
        <a class="inline-block rounded-lg bg-gray-950 px-4 py-2 text-sm text-amber-300" href="{{ route('whatsapp.assistant.pending') }}">Réponses à valider ({{ \App\Models\HnbWhatsAppOutboundMessage::where('status','pending')->count() }})</a>
        @if(session('success')) <p class="rounded-xl bg-green-100 p-3 text-sm text-green-800">{{ session('success') }}</p> @endif
        @forelse($messages as $message)
            <article class="rounded-xl border {{ $message->read_at ? 'border-gray-200' : 'border-amber-400' }} bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-sm font-semibold text-gray-900">
                        @if($message->client)
                            <a class="text-amber-800 underline" href="{{ route('clients.show', $message->client) }}">{{ $message->client->company ?: $message->client->name }}</a>
                        @else
                            <span>Contact non identifié</span>
                        @endif
                        <span class="ml-2 font-normal text-gray-500">{{ $message->sender }}</span>
                    </div>
                    <time class="text-xs text-gray-500">{{ $message->received_at?->format('d/m/Y H:i') }}</time>
                </div>
                <p class="mt-3 whitespace-pre-line break-words text-sm text-gray-800">{{ $message->body }}</p>
                <a class="mt-3 inline-block rounded-lg bg-gray-950 px-4 py-2 text-xs font-semibold text-amber-300" href="{{ route('whatsapp.assistant.compose', $message->id) }}">Préparer une réponse</a>
                @if(!$message->read_at)
                    <form class="mt-3" method="POST" action="{{ route('admin.whatsapp.read', $message) }}">
                        @csrf @method('PATCH')
                        <button class="rounded-lg bg-gray-950 px-4 py-2 text-xs font-semibold text-amber-300">Marquer comme lu</button>
                    </form>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">Aucun message reçu pour le moment.</div>
        @endforelse
        {{ $messages->links() }}
    </div>
</x-app-layout>
