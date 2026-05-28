<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogController extends Controller
{
    use HasDataTable;

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('activity-log.view'), 403);

        $logNames = Activity::distinct()->orderBy('log_name')->pluck('log_name');
        $events = Activity::distinct()->orderBy('event')->pluck('event');
        $causerTypes = ['Admin', 'Vendor', 'Customer'];

        return view('admin.activity-log.index', compact('logNames', 'events', 'causerTypes'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('activity-log.view'), 403);

        $query = Activity::query()->orderBy('activity_log.created_at', 'desc');

        $query = $this->applyFilters($query, $request, [
            'log_name' => fn($q, $v) => $q->where('log_name', $v),
            'event' => fn($q, $v) => $q->where('event', $v),
            'causer_type' => fn($q, $v) => $q->where('causer_type', 'like', "%{$v}%"),
            'subject_type' => fn($q, $v) => $q->where('subject_type', 'like', "%{$v}%"),
            'date_from' => fn($q, $v) => $q->whereDate('activity_log.created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('activity_log.created_at', '<=', $v),
        ]);

        $columns = [
            0 => ['orderable_column' => 'activity_log.created_at'],
            1 => ['searchable_columns' => ['activity_log.causer_type', 'activity_log.causer_id']],
            2 => ['orderable_column' => 'activity_log.event'],
            3 => ['searchable_columns' => ['activity_log.subject_type']],
            4 => ['searchable_columns' => ['activity_log.description']],
            5 => ['orderable_column' => 'activity_log.log_name'],
            6 => [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($entry) {
            $eventBadge = match ($entry->event) {
                'created' => 'bg-green-100 text-green-700',
                'updated' => 'bg-blue-100 text-blue-700',
                'deleted' => 'bg-red-100 text-red-700',
                default => 'bg-gray-100 text-gray-600',
            };

            $causerShortType = class_basename($entry->causer_type ?? 'System');
            $causerBadge = match ($causerShortType) {
                'Admin' => 'bg-indigo-100 text-indigo-700',
                'Vendor' => 'bg-purple-100 text-purple-700',
                'Customer' => 'bg-teal-100 text-teal-700',
                default => 'bg-gray-100 text-gray-600',
            };

            $showUrl = route('admin.activity-log.show', $entry->id);

            return [
                'DT_RowId' => 'al-' . $entry->id,
                'created_at' => $entry->created_at->format('M d, Y H:i'),
                'causer' => '<div class="flex items-center gap-1.5">'
                    . '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ' . $causerBadge . '">' . e($causerShortType) . '</span>'
                    . '<span class="text-xs font-mono text-gray-500">' . e(substr($entry->causer_id ?? 'system', 0, 8)) . '</span>'
                    . '</div>',
                'event' => $entry->event
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $eventBadge . '">' . e($entry->event) . '</span>'
                    : '<span class="text-gray-300">—</span>',
                'subject' => '<span class="text-xs font-mono text-gray-600">' . e(class_basename($entry->subject_type ?? '')) . '</span>',
                'description' => '<span class="text-sm text-gray-700">' . e(Str::limit($entry->description, 80)) . '</span>',
                'log_name' => $entry->log_name
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">' . e($entry->log_name) . '</span>'
                    : '<span class="text-gray-300">—</span>',
                'ip_address' => '<span class="font-mono text-xs text-gray-500">' . e($entry->ip_address ?? '—') . '</span>',
                'actions' => '<a href="' . $showUrl . '" class="btn btn-xs btn-secondary">View</a>',
            ];
        });
    }

    public function show(string $id): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('activity-log.view'), 403);

        $entry = Activity::findOrFail($id);

        return view('admin.activity-log.show', compact('entry'));
    }
}
