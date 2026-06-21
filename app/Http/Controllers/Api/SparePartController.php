<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SparePartResource;
use App\Models\SparePart;
use Illuminate\Http\Request;

/**
 * Read-only spare-parts catalogue search for the in-ticket picker. Results are
 * company-scoped (BelongsToCompany). Technicians (and other non-inventory roles)
 * only see parts relevant to THEIR department (its categories + shared/global),
 * mirroring the web ticket picker; inventory managers/admins see everything.
 */
class SparePartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // Null = no department restriction (warehouse manager / admin see all).
        $department = $user->canManageInventory() ? null : $user->department_id;

        $parts = SparePart::with('category')
            ->when($department, fn ($q, $dept) => $q->forDepartment($dept))
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
