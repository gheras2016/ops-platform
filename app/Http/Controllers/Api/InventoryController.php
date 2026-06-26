<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryPartResource;
use App\Http\Resources\StockMovementResource;
use App\Models\SpareCategory;
use App\Models\SparePart;
use App\Models\StockTransaction;
use Illuminate\Http\Request;

/**
 * Read-only spare-parts inventory for the mobile field app: browse, search,
 * part detail, movement history and low-stock alerts. No mutations — creating,
 * editing, adjusting stock and stock-taking stay on the web admin.
 *
 * All routes are gated by the `view-inventory` ability; data is company-scoped
 * by the SparePart global scope.
 */
class InventoryController extends Controller
{
    /** Paginated, searchable, filterable stock list. */
    public function index(Request $request)
    {
        $parts = SparePart::with('category')
            ->when($request->filled('department'), fn ($q) => $q->forDepartment((int) $request->department))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', (int) $request->category))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('quantity', '<=', 'min_stock'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('part_number', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        SparePart::attachReserved($parts->getCollection());

        return InventoryPartResource::collection($parts);
    }

    /** Headline counts for the inventory overview. */
    public function summary()
    {
        return response()->json([
            'total_parts' => SparePart::count(),
            'low_stock_count' => SparePart::whereColumn('quantity', '<=', 'min_stock')->count(),
            'out_of_stock_count' => SparePart::where('quantity', '<=', 0)->count(),
        ]);
    }

    /** Parts at or below their minimum stock level. */
    public function lowStock()
    {
        $parts = SparePart::with('category')
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity')
            ->paginate(20);

        SparePart::attachReserved($parts->getCollection());

        return InventoryPartResource::collection($parts);
    }

    /** Spare categories for the filter (department-scoped + shared). */
    public function categories(Request $request)
    {
        $categories = SpareCategory::when(
            $request->filled('department'),
            fn ($q) => $q->forDepartment((int) $request->department)
        )->orderBy('name')->get(['id', 'name', 'department_id']);

        return response()->json([
            'data' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'is_global' => $c->department_id === null,
            ]),
        ]);
    }

    /** Part detail with its most recent movements. */
    public function show(SparePart $sparePart)
    {
        $sparePart->load([
            'category',
            'stockTransactions' => fn ($q) => $q->with(['user', 'ticket'])->latest()->limit(10),
        ]);

        return new InventoryPartResource($sparePart);
    }

    /** Full, paginated movement history for one part. */
    public function movements(SparePart $sparePart)
    {
        $movements = StockTransaction::with(['user', 'ticket'])
            ->where('spare_part_id', $sparePart->id)
            ->latest()
            ->paginate(20);

        return StockMovementResource::collection($movements);
    }
}
