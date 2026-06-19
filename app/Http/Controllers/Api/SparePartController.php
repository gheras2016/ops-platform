<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SparePartResource;
use App\Models\SparePart;
use Illuminate\Http\Request;

/**
 * Read-only spare-parts catalogue search for the in-ticket picker. Results are
 * company-scoped (BelongsToCompany) and may be narrowed to a department.
 */
class SparePartController extends Controller
{
    public function index(Request $request)
    {
        $parts = SparePart::with('category')
            ->when($request->filled('department'), fn ($q) => $q->forDepartment((int) $request->department))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('part_number', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return SparePartResource::collection($parts);
    }
}
