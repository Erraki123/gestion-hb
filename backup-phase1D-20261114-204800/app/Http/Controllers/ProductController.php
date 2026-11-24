<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Pricing;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when(auth()->user()->isCommercial(), fn ($q) => $q->where('is_active', true))
            ->when($request->q, fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->stock === 'low', fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request, StockService $stock)
    {
        $data = $this->validated($request);
        $initial = (float) ($request->validate(['initial_stock' => ['nullable', 'numeric', 'min:0']])['initial_stock'] ?? 0);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['stock_quantity'] = 0;

        $product = DB::transaction(function () use ($data, $initial, $stock) {
            $product = Product::create($data);

            if ($initial > 0) {
                $stock->move($product, 'entree', $initial, auth()->id(), 'Stock initial');
            }

            return $product;
        });

        return redirect()->route('products.show', $product)->with('success', 'Produit créé.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'movements.user', 'priceHistories.user']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product'    => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($product, $data) {
            $priceChanged =
                abs((float) $product->sale_price - (float) $data['sale_price']) > 0.00005 ||
                abs((float) $product->purchase_price - (float) $data['purchase_price']) > 0.00005;

            if ($priceChanged) {
                PriceHistory::create([
                    'product_id'         => $product->id,
                    'user_id'            => auth()->id(),
                    'old_sale_price'     => $product->sale_price,
                    'new_sale_price'     => $data['sale_price'],
                    'old_purchase_price' => $product->purchase_price,
                    'new_purchase_price' => $data['purchase_price'],
                ]);
            }

            $product->update($data);
        });

        return redirect()->route('products.show', $product)->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product)
    {
        $product->delete(); // Soft delete

        return redirect()->route('products.index')->with('success', 'Produit supprimé (restaurable).');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'sku'              => ['required', 'string', 'max:60', 'unique:products,sku' . ($product ? ',' . $product->id : '')],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'unit'             => ['required', 'string', 'max:20'],
            'tax_rate'         => ['required', 'numeric', Rule::in(Setting::taxRates())],
            'sale_price_input' => ['required', 'numeric', 'min:0'],
            'purchase_price'   => ['required', 'numeric', 'min:0'],
            'min_stock'        => ['required', 'numeric', 'min:0'],
            'image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $data['sale_price'] = Pricing::toHt((float) $data['sale_price_input'], (float) $data['tax_rate']);
        $data['purchase_price'] = round((float) $data['purchase_price'], 4);
        unset($data['sale_price_input']);

        return $data;
    }
}
