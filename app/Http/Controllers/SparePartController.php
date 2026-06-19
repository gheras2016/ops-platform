<?php

namespace App\Http\Controllers;

use App\Imports\SparePartsImport;
use App\Models\SparePart;
use App\Models\SpareCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SparePartController extends Controller
{
    public function index(Request $request)
    {
        $spareParts = SparePart::with('category.department')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('part_number', 'like', "%{$s}%"))
            ->when($request->category, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->low_stock, fn ($q) => $q->whereColumn('quantity', '<=', 'min_stock'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('spare-parts.index', [
            'spareParts' => $spareParts,
            'categories' => SpareCategory::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('spare-parts.create', ['categories' => SpareCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        SparePart::create($this->validateData($request));

        return redirect()->route('spare-parts.index')->with('success', 'تم إضافة قطعة الغيار بنجاح');
    }

    public function show(SparePart $sparePart)
    {
        $sparePart->load('category', 'stockTransactions.user');

        return view('spare-parts.show', compact('sparePart'));
    }

    public function edit(SparePart $sparePart)
    {
        return view('spare-parts.edit', [
            'sparePart' => $sparePart,
            'categories' => SpareCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SparePart $sparePart)
    {
        $sparePart->update($this->validateData($request));

        return redirect()->route('spare-parts.index')->with('success', 'تم تحديث قطعة الغيار بنجاح');
    }

    public function destroy(SparePart $sparePart)
    {
        $sparePart->delete();

        return redirect()->route('spare-parts.index')->with('success', 'تم حذف قطعة الغيار');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt']]);

        $import = new SparePartsImport($request->user()->company_id);
        Excel::import($import, $request->file('file'));

        return back()->with('success', "تم استيراد {$import->imported} قطعة غيار بنجاح");
    }

    public function template()
    {
        $t = \App\Support\ImportTemplates::SPARE_PARTS;

        return Excel::download(
            new \App\Exports\TemplateExport($t['headings'], $t['rows'], $t['title']),
            'spare_parts_template.xlsx'
        );
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'part_number' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:spare_categories,id'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
