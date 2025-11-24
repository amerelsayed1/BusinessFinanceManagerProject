<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = ProductCategory::firstOrCreate([
            'user_id' => auth()->id(),
            'name' => $data['name'],
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = ProductCategory::where('user_id', auth()->id())->findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
