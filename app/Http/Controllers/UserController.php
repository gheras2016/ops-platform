<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin-access');
    }

    public function index(Request $request)
    {
        $users = User::with(['department', 'location', 'roles'])
            ->tenantScoped($request->user())
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role, fn ($q, $r) => $q->role($r))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function create(Request $request)
    {
        return view('users.create', [
            'departments' => Department::orderBy('name')->get(),
            'locations' => Location::orderBy('full_path')->get(),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $user = new User($data);
        $user->password = Hash::make($data['password']);
        $user->company_id = $request->user()->company_id ?? $request->input('company_id');
        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    public function show(User $user)
    {
        $user->load(['department', 'roles']);

        return view('users.show', compact('user'));
    }

    public function edit(Request $request, User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'departments' => Department::orderBy('name')->get(),
            'locations' => Location::orderBy('full_path')->get(),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateData($request, $user->id);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم بنجاح');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'لا يمكنك حذف حسابك.');
        $user->delete();

        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'role' => ['required', 'string', Rule::in($this->assignableRoles($request->user())->pluck('name'))],
            'password' => [$id ? 'nullable' : 'required', 'nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /** Company admins cannot create super admins. */
    protected function assignableRoles(User $actor)
    {
        $query = Role::query()->orderBy('name');

        if (! $actor->isSuperAdmin()) {
            $query->where('name', '!=', User::ROLE_SUPER_ADMIN);
        }

        return $query->get();
    }
}
