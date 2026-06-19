<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SpareCategory;
use Illuminate\Http\Request;

class SpareCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:inventory-access');
    }

    public function index()
    {
        $categories = SpareCategory::with('department')
            ->withCount('spareParts')
            ->orderBy('name')
            ->paginate(20);

        return view('spare-parts.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('spare-parts.categories.create', ['departments' => Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        SpareCategory::create($this->validateData($request));

        return redirect()->route('spare-categories.index')->with('success', 'تم إضافة التصنيف بنجاح');
    }

    public function edit(SpareCategory $spareCategory)
    {
        return view('spare-parts.categories.edit', [
            'category' => $spareCategory,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SpareCategory $spareCategory)
    {
        $spareCategory->update($this->validateData($request));

        return redirect()->route('spare-categories.index')->with('success', 'تم تحديث التصنيف بنجاح');
    }

    public function destroy(SpareCategory $spareCategory)
    {
        $spareCategory->delete();

        return redirect()->route('spare-categories.index')->with('success', 'تم حذف التصنيف');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);
    }
}
