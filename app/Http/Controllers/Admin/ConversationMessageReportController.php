<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConversationMessageReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationMessageReportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');

        $query = ConversationMessageReport::query()
            ->with([
                'message.user:id,name,role',
                'message.conversation.distributor:id,company_name,slug',
                'reporter:id,name,email',
                'reviewer:id,name',
            ])
            ->orderByDesc('id');

        if ($status !== 'all' && in_array($status, ['open', 'reviewing', 'resolved', 'dismissed'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(20)->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(function (ConversationMessageReport $r) {
                $m = $r->message;
                $body = $m ? trim((string) $m->body) : '';

                return [
                    'id' => $r->id,
                    'status' => $r->status,
                    'reason' => $r->reason,
                    'details' => $r->details,
                    'admin_notes' => $r->admin_notes,
                    'reviewed_at' => $r->reviewed_at?->toIso8601String(),
                    'reviewer' => $r->reviewer ? ['name' => $r->reviewer->name] : null,
                    'created_at' => $r->created_at->toIso8601String(),
                    'reporter' => $r->reporter ? [
                        'name' => $r->reporter->name,
                        'email' => $r->reporter->email,
                    ] : null,
                    'message' => $m ? [
                        'id' => $m->id,
                        'body_preview' => $body !== '' ? str($body)->limit(160)->toString() : ($m->image_path ? '[Image]' : '—'),
                        'kind' => $m->kind,
                        'order_id' => $m->order_id,
                        'author' => $m->user ? $m->user->name : '—',
                        'conversation_id' => $m->conversation_id,
                        'shop' => $m->conversation?->distributor?->company_name,
                    ] : null,
                ];
            })
        );

        return Inertia::render('Admin/MessageReports/Index', [
            'reports' => $paginator,
            'filters' => [
                'status' => $status,
            ],
            'statusOptions' => ['all', 'open', 'reviewing', 'resolved', 'dismissed'],
        ]);
    }

    public function update(Request $request, ConversationMessageReport $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,reviewing,resolved,dismissed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'admin_notes' => $request->has('admin_notes')
                ? $validated['admin_notes']
                : $report->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back();
    }
}
