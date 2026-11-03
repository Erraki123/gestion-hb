<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold tracking-wide text-white shadow-sm shadow-gold-500/30 transition duration-150 ease-in-out hover:bg-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>