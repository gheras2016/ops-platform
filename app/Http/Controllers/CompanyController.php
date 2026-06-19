<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:platform-access');
    }

    public function index()
    {
        $companies = Company::withCount(['users', 'departments', 'tickets'])
            ->orderBy('name')
            ->paginate(15);

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $admin = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($data, $admin) {
            $company = Company::create($data);

            $user = new User([
                'company_id' => $company->id,
                'name' => $admin['admin_name'],
                'email' => $admin['admin_email'],
                'is_active' => true,
            ]);
            $user->password = Hash::make($admin['admin_password']);
            $user->save();
            $user->assignRole(User::ROLE_COMPANY_ADMIN);
        });

        return redirect()->route('companies.index')->with('success', 'تم إنشاء الشركة ومدير النظام بنجاح');
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $this->validateData($request, $company->id);
        $data['is_active'] = $request->boolean('is_active');
        $company->update($data);

        return redirect()->route('companies.index')->with('success', 'تم تحديث الشركة بنجاح');
    }

    public function export(Company $company, \App\Support\TenantExporter $exporter)
    {
        $path = $exporter->export($company);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function toggle(Company $company)
    {
        $company->update(['is_active' => ! $company->is_active]);

        return back()->with('success', $company->is_active ? 'تم تفعيل الشركة' : 'تم إيقاف الشركة');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'تم حذف الشركة');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('companies', 'code')->ignore($id)],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
