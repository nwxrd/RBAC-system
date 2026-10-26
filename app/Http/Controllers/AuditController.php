<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $logs = AuditLog::query()
            ->when($search !== '', function ($query) use ($search) {
                $actions = collect(AuditLog::LABELS)
                    ->filter(fn (string $label): bool => str_contains(mb_strtolower($label), mb_strtolower($search)))
                    ->keys()
                    ->all();

                $query->where(function ($query) use ($search, $actions) {
                    $query->where('actor_name', 'like', "%{$search}%")
                        ->orWhere('subject_label', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%");

                    if ($actions !== []) {
                        $query->orWhereIn('action', $actions);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $logs->getCollection()->transform(fn (AuditLog $log): array => [
            'id' => $log->id,
            'actor_name' => $log->actor_name,
            'action_label' => $log->actionLabel(),
            'subject_label' => $log->subject_label,
            'changes' => $log->changeLines(),
            'created_at' => $log->created_at?->format('Y-m-d H:i'),
        ]);

        return Inertia::render('audit/index', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
        ]);
    }
}
