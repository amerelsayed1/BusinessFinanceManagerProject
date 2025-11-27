<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = auth()->user()->expenseCategories()->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = ExpenseCategory::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'is_default' => false,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = ExpenseCategory::where('user_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($id)
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }

    public function destroy($id)
    {
        $category = ExpenseCategory::where('user_id', auth()->id())->findOrFail($id);

        if ($category->is_default) {
            return response()->json(['error' => 'Default categories cannot be deleted'], 403);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
