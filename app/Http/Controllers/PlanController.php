<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Super-admin management of subscription plans (the catalogue customers pick
 * from). Plans are deactivated rather than deleted so historical payments keep
 * their reference.
 */
class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:platform-access');
    }

    public function index()
    {
        return view('plans.index', ['plans' => Plan::orderBy('sort')->orderBy('price')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        Plan::create($data);

        return back()->with('success', 'تمت إضافة الباقة.');
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validateData($request));

        return back()->with('success', 'تم تحديث الباقة.');
    }

    public function toggle(Plan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', $plan->is_active ? 'تم تفعيل الباقة.' : 'تم تعطيل الباقة.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_period' => ['required', 'in:monthly,yearly'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['currency'] = $data['currency'] ?? 'SAR';
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['features'] = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('features')))
            ->map(fn ($f) => trim($f))->filter()->values()->all();

        return $data;
    }
}
