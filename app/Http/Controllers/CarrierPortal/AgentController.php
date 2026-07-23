<?php

namespace App\Http\Controllers\CarrierPortal;

use App\Enums\DeliveryAgentStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use App\Traits\HasDataTable;
use App\Traits\HasExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentController extends Controller
{
    use HasDataTable;
    use HasExport;

    public function index(Request $request): View|StreamedResponse
    {
        $supervisor = auth('shipping_supervisor')->user();

        if ($request->filled('export')) {
            return $this->exportAgents($request, $supervisor);
        }

        $agents = $this->buildAgentsQuery($request, $supervisor)
            ->withCount('assignments')
            ->latest()
            ->paginate(20);

        return view('carrier.agents.index', compact('agents'));
    }

    /** Shared query for the index view and the export, scoped to the supervisor's company. */
    private function buildAgentsQuery(Request $request, $supervisor): Builder
    {
        $query = DeliveryAgent::where('shipping_company_id', $supervisor->shipping_company_id);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        return $this->applyFilters($query, $request, [
            'status' => fn ($q, $v) => $q->where('status', $v),
        ]);
    }

    private function exportAgents(Request $request, $supervisor): StreamedResponse
    {
        $agents = $this->buildAgentsQuery($request, $supervisor)
            ->withCount('assignments')
            ->latest()
            ->get();

        $headers = ['Name', 'Email', 'Status', 'Assignments', 'Joined'];

        $rows = $agents->map(fn (DeliveryAgent $agent) => [
            $agent->name,
            $agent->email,
            $agent->status?->value,
            (int) $agent->assignments_count,
            $agent->created_at?->format('Y-m-d') ?? '—',
        ]);

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('carrier-agents', $headers, $rows),
            'csv' => $this->exportCsv('carrier-agents', $headers, $rows),
            'word' => $this->exportWord('carrier-agents', 'Carrier Agents', $rows),
            default => abort(400, 'Invalid export format.'),
        };
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
