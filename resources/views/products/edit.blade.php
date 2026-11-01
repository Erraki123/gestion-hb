<x-app-layout>
    <x-slot name="header"><h2>Modifier le produit</h2></x-slot>
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="panel p-6 sm:p-8">
            @method('PUT')
            @include('products._form')
        </form>
    </div>
</x-app-layout>
