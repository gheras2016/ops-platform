<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin-access');
    }

    public function index()
    {
        $departments = Department::with('head')
            ->withCount(['members', 'tickets'])
            ->orderBy('name')
            ->paginate(15);

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        return view('departments.create', [
            'types' => Department::TYPES,
            'heads' => $this->headCandidates(),
            'parents' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Department::create($this->validateData($request));

        return redirect()->route('departments.index')->with('success', 'تم إنشاء القسم بنجاح');
    }

    public function show(Department $department)
    {
        $department->load(['head', 'members.roles', 'tickets' => fn ($q) => $q->latest()->limit(10)]);

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        return view('departments.edit', [
            'department' => $department,
            'types' => Department::TYPES,
            'heads' => $this->headCandidates(),
            'parents' => Department::where('id', '!=', $department->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validateData($request));

        return redirect()->route('departments.index')->with('success', 'تم تحديث القسم بنجاح');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'تم حذف القسم');
    }

    protected function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(Department::TYPES))],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'head_id' => ['nullable', 'exists:users,id'],
            'color' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Checkboxes: absent = false.
        $data['is_active'] = $request->boolean('is_active');
        $data['accepts_tickets'] = $request->boolean('accepts_tickets');

        return $data;
    }

    protected function headCandidates()
    {
        return User::role([User::ROLE_DEPARTMENT_HEAD, User::ROLE_COMPANY_ADMIN])
            ->orderBy('name')
            ->get();
    }
}
