<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('items')->latest()->paginate(15);

        return view('inventory.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('inventory.categories.create');
    }

    public function store(Request $request)
    {
        Category::create($this->validateData($request));

        return redirect()->route('inventory.categories.index')->with('success', 'تم إضافة الفئة بنجاح');
    }

    public function show(Category $category)
    {
        $category->load('items');

        return view('inventory.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        return view('inventory.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validateData($request));

        return redirect()->route('inventory.categories.index')->with('success', 'تم تحديث الفئة بنجاح');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('inventory.categories.index')->with('success', 'تم حذف الفئة');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
