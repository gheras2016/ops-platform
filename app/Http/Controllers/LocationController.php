<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin-access');
    }

    public function index()
    {
        // Ordered by path so the hierarchy reads naturally.
        $locations = Location::with('parent')
            ->withCount(['children', 'users', 'tickets'])
            ->orderBy('full_path')
            ->paginate(20);

        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        return view('locations.create', [
            'types' => Location::TYPES,
            'parents' => Location::orderBy('full_path')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $location = new Location($this->validateData($request));
        $location->full_path = $location->computeFullPath();
        $location->save();

        return redirect()->route('locations.index')->with('success', 'تم إنشاء الموقع بنجاح');
    }

    /** Quick-create a location from any form (AJAX); returns JSON for the dropdown. */
    public function quickStore(Request $request)
    {
        $data = $this->validateData($request);

        $location = new Location($data);
        $location->full_path = $location->computeFullPath();
        $location->save();

        return response()->json([
            'id' => $location->id,
            'name' => $location->name,
            'full_path' => $location->full_path,
        ]);
    }

    public function show(Location $location)
    {
        $location->load(['parent', 'children', 'users.roles']);

        return view('locations.show', compact('location'));
    }

    public function edit(Location $location)
    {
        return view('locations.edit', [
            'location' => $location,
            'types' => Location::TYPES,
            // A location cannot be its own parent (avoid cycles).
            'parents' => Location::where('id', '!=', $location->id)->orderBy('full_path')->get(),
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $location->fill($this->validateData($request, $location->id));
        $location->full_path = $location->computeFullPath();
        $location->save();

        // Refresh descendant paths if this node moved/renamed.
        $this->refreshDescendantPaths($location);

        return redirect()->route('locations.index')->with('success', 'تم تحديث الموقع بنجاح');
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'تم حذف الموقع');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Location::TYPES))],
            'parent_id' => ['nullable', 'exists:locations,id', Rule::notIn([$id])],
        ], [
            'parent_id.not_in' => 'لا يمكن أن يكون الموقع تابعًا لنفسه.',
        ]);
    }

    protected function refreshDescendantPaths(Location $location): void
    {
        foreach ($location->children as $child) {
            $child->full_path = $child->computeFullPath();
            $child->save();
            $this->refreshDescendantPaths($child);
        }
    }
}
