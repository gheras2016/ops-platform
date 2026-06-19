<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransactionController extends Controller
{
    public function index()
    {
        $transactions = StockTransaction::with(['sparePart', 'user'])
            ->latest()
            ->paginate(20);

        return view('stock-transactions.index', compact('transactions'));
    }

    public function create()
    {
        return view('stock-transactions.create', ['spareParts' => SparePart::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'spare_part_id' => ['required', 'exists:spare_parts,id'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $part = SparePart::findOrFail($data['spare_part_id']);

            StockTransaction::create([
                'spare_part_id' => $part->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'created_by' => $request->user()->id,
            ]);

            $part->increment('quantity', $data['type'] === 'in' ? $data['quantity'] : -$data['quantity']);
        });

        return redirect()->route('stock-transactions.index')->with('success', 'تم تسجيل حركة المخزون بنجاح');
    }

    public function destroy(StockTransaction $stockTransaction)
    {
        $stockTransaction->delete();

        return redirect()->route('stock-transactions.index')->with('success', 'تم حذف الحركة');
    }
}
