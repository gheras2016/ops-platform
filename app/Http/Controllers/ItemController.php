<?php

namespace App\Http\Controllers;

use App\Imports\ItemsImport;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::with('category')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('inventory.items.index', compact('items'));
    }

    public function create()
    {
        return view('inventory.items.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Item::create($this->validateData($request));

        return redirect()->route('inventory.items.index')->with('success', 'تم إضافة الصنف بنجاح');
    }

    public function show(Item $item)
    {
        return view('inventory.items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('inventory.items.edit', [
            'item' => $item,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $item->update($this->validateData($request));

        return redirect()->route('inventory.items.index')->with('success', 'تم تحديث الصنف بنجاح');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return redirect()->route('inventory.items.index')->with('success', 'تم حذف الصنف');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt']]);

        $import = new ItemsImport($request->user()->company_id);
        Excel::import($import, $request->file('file'));

        return back()->with('success', "تم استيراد {$import->imported} صنفًا بنجاح");
    }

    public function template()
    {
        $t = \App\Support\ImportTemplates::ITEMS;

        return Excel::download(
            new \App\Exports\TemplateExport($t['headings'], $t['rows'], $t['title']),
            'items_template.xlsx'
        );
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
