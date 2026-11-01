<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories,name']]);
        Category::create($data);

        return back()->with('success', 'Catégorie ajoutée.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}
