<?php

namespace App\Http\Controllers\CarrierPortal;

use App\Enums\DeliveryAgentStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $supervisor = auth('shipping_supervisor')->user();
        $agents = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id)
            ->latest()
            ->paginate(20);

        return view('carrier.agents.index', compact('agents'));
    }

    public function create(): View
    {
        $this->requirePermission('manage_agents');

        return view('carrier.agents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('manage_agents');

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:delivery_agents,email'],
            'phone'        => ['required', 'string', 'max:30', 'unique:delivery_agents,phone'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'vehicle_type' => ['required', 'in:motorcycle,car,van,bicycle'],
            'national_id'  => ['nullable', 'string', 'max:50'],
        ]);

        $supervisor = auth('shipping_supervisor')->user();

        DeliveryAgent::create([
            ...$data,
            'country_id'             => $supervisor->company->country_id,
            'agent_type'             => 'third_party',
            'shipping_company_id'    => $supervisor->shipping_company_id,
            'added_by_supervisor_id' => $supervisor->id,
            'status'                 => DeliveryAgentStatus::Active,
        ]);

        return redirect()->route('carrier.agents.index')
            ->with('success', 'تم إضافة المندوب بنجاح.');
    }

    public function suspend(string $id): RedirectResponse
    {
        $this->requirePermission('manage_agents');

        $agent = $this->agentForCurrentCompany($id);
        $agent->update(['status' => DeliveryAgentStatus::Suspended]);

        return back()->with('success', 'تم إيقاف المندوب.');
    }

    public function activate(string $id): RedirectResponse
    {
        $this->requirePermission('manage_agents');

        $agent = $this->agentForCurrentCompany($id);
        $agent->update(['status' => DeliveryAgentStatus::Active]);

        return back()->with('success', 'تم تفعيل المندوب.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function requirePermission(string $perm): void
    {
        $supervisor = auth('shipping_supervisor')->user();
        abort_unless($supervisor->hasPermission($perm), 403, 'ليس لديك صلاحية لهذه العملية.');
    }

    private function agentForCurrentCompany(string $id): DeliveryAgent
    {
        $supervisor = auth('shipping_supervisor')->user();

        return DeliveryAgent::where('id', $id)
            ->where('shipping_company_id', $supervisor->shipping_company_id)
            ->firstOrFail();
    }
}
