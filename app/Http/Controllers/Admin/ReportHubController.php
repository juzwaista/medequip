<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConversationMessage;
use App\Models\ConversationMessageReport;
use App\Models\CourierReport;
use App\Models\DeliveryReview;
use App\Models\Order;
use App\Models\ProductReport;
use App\Models\User;
use App\Models\UserReport;
use App\Services\AdminModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReportHubController extends Controller
{
    public function __construct(
        private AdminModerationService $moderation,
    ) {}

    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'messages');
        $status = $request->query('status', 'open');

        if (! in_array($tab, ['messages', 'shops', 'customers', 'users', 'couriers', 'products'], true)) {
            $tab = 'messages';
        }

        $statusFilter = $status === 'all' ? null : $status;
        if ($statusFilter && ! in_array($statusFilter, ['open', 'reviewing', 'resolved', 'dismissed'], true)) {
            $statusFilter = 'open';
        }

        $counts = [
            'open_messages' => ConversationMessageReport::query()->where('status', 'open')->count(),
            'open_users' => UserReport::query()->where('status', 'open')->count(),
            'open_couriers' => CourierReport::query()->where('status', 'open')->count(),
            'delivery_flags' => DeliveryReview::query()->where('stars', '<=', 2)->whereNull('admin_cleared_at')->count(),
            'open_products' => ProductReport::query()->where('status', 'open')->count(),
        ];

        $messageReports = null;
        $userReports = null;
        $courierReports = null;
        $deliveryFlags = null;
        $productReports = null;

        if (in_array($tab, ['messages', 'shops', 'customers'], true)) {
            $q = ConversationMessageReport::query()
                ->with([
                    'message.user:id,name,role',
                    'message.conversation.distributor:id,company_name,slug',
                    'reporter:id,name,email',
                ])
                ->orderByDesc('id');

            if ($statusFilter) {
                $q->where('status', $statusFilter);
            }

            if ($tab === 'shops') {
                $q->whereHas('message.user', fn ($uq) => $uq->whereIn('role', ['distributor', 'staff']));
            } elseif ($tab === 'customers') {
                $q->whereHas('message.user', fn ($uq) => $uq->where('role', 'customer'));
            }

            $messageReports = $q->paginate(20)->withQueryString()->through(fn (ConversationMessageReport $r) => $this->summarizeMessageReport($r));
        }

        if ($tab === 'users') {
            $q = UserReport::query()
                ->with(['reporter:id,name,email', 'subject:id,name,email,role,banned_at'])
                ->orderByDesc('id');
            if ($statusFilter) {
                $q->where('status', $statusFilter);
            }
            $userReports = $q->paginate(20)->withQueryString()->through(fn (UserReport $r) => $this->summarizeUserReport($r));
        }

        if ($tab === 'couriers') {
            $q = CourierReport::query()
                ->with([
                    'reporter:id,name,email',
                    'courier.user:id,name,email',
                    'order:id,order_number',
                ])
                ->orderByDesc('id');
            if ($statusFilter) {
                $q->where('status', $statusFilter);
            }
            $courierReports = $q->paginate(15)->withQueryString()->through(fn (CourierReport $r) => $this->summarizeCourierReport($r));

            $dq = DeliveryReview::query()
                ->with([
                    'user:id,name,email',
                    'order:id,order_number',
                    'courier.user:id,name,email',
                ])
                ->where('stars', '<=', 2)
                ->whereNull('admin_cleared_at')
                ->orderByDesc('id');
            $deliveryFlags = $dq->paginate(15, ['*'], 'delivery_page')->withQueryString()->through(fn (DeliveryReview $d) => $this->summarizeDeliveryFlag($d));
        }

        if ($tab === 'products') {
            $q = ProductReport::query()
                ->with(['reporter:id,name,email', 'product:id,name'])
                ->orderByDesc('id');
            if ($statusFilter) {
                $q->where('status', $statusFilter);
            }
            $productReports = $q->paginate(20)->withQueryString()->through(fn (ProductReport $r) => $this->summarizeProductReport($r));
        }

        return Inertia::render('Admin/Reports/Hub', [
            'tab' => $tab,
            'status' => $status,
            'counts' => $counts,
            'messageReports' => $messageReports,
            'userReports' => $userReports,
            'courierReports' => $courierReports,
            'deliveryFlags' => $deliveryFlags,
            'productReports' => $productReports,
            'statusOptions' => ['all', 'open', 'reviewing', 'resolved', 'dismissed'],
            'distributorWarnPresets' => AdminModerationService::DISTRIBUTOR_WARN_PRESETS,
        ]);
    }

    public function show(Request $request, string $bucket, int $id): Response
    {
        $bucket = strtolower($bucket);
        if ($bucket === 'message') {
            $report = ConversationMessageReport::query()
                ->with([
                    'message.user',
                    'message.conversation.distributor',
                    'message.conversation.customer:id,name,email',
                    'reporter:id,name,email,role',
                    'reviewer:id,name',
                ])
                ->findOrFail($id);

            $message = $report->message;
            $transcript = collect();
            $availableActions = [];
            if ($message) {
                $transcript = ConversationMessage::query()
                    ->where('conversation_id', $message->conversation_id)
                    ->where('id', '>=', max(1, $message->id - 10))
                    ->where('id', '<=', $message->id + 24)
                    ->with('user:id,name,role')
                    ->orderBy('id')
                    ->get()
                    ->map(fn (ConversationMessage $m) => $this->transcriptLine($m, $message->id));

                $role = $message->user?->role;
                if (in_array($role, ['distributor', 'staff'], true)) {
                    $d = $message->conversation?->distributor;
                    $availableActions = [
                        'dismiss',
                        'no_action_resolve',
                        'warn_distributor',
                        'suspend_distributor',
                        'ban_distributor',
                        'lift_distributor_suspension',
                    ];
                    if ($d && ($d->suspended_until === null || ! $d->suspended_until->isFuture())) {
                        $availableActions = array_values(array_diff($availableActions, ['lift_distributor_suspension']));
                    }
                } elseif ($role === 'customer') {
                    $availableActions = ['dismiss', 'no_action_resolve', 'ban_subject_user'];
                } else {
                    $availableActions = ['dismiss', 'no_action_resolve'];
                }
            }

            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                $availableActions = [];
            }

            return Inertia::render('Admin/Reports/Show', [
                'bucket' => 'message',
                'id' => $report->id,
                'caseData' => $this->detailMessageReport($report),
                'transcript' => $transcript,
                'availableActions' => $availableActions,
                'distributorWarnPresets' => AdminModerationService::DISTRIBUTOR_WARN_PRESETS,
            ]);
        }

        if ($bucket === 'user') {
            $report = UserReport::query()
                ->with(['reporter:id,name,email,role', 'subject', 'reviewer:id,name'])
                ->findOrFail($id);

            $subjectStats = $this->userStats($report->subject);

            $userActions = ['dismiss', 'no_action_resolve', 'ban_subject_user'];
            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                $userActions = [];
            }

            return Inertia::render('Admin/Reports/Show', [
                'bucket' => 'user',
                'id' => $report->id,
                'caseData' => $this->detailUserReport($report, $subjectStats),
                'transcript' => [],
                'availableActions' => $userActions,
                'distributorWarnPresets' => AdminModerationService::DISTRIBUTOR_WARN_PRESETS,
            ]);
        }

        if ($bucket === 'courier') {
            $report = CourierReport::query()
                ->with(['reporter:id,name,email', 'courier.user', 'order', 'reviewer:id,name'])
                ->findOrFail($id);

            $courierActions = ['dismiss', 'no_action_resolve', 'suspend_courier', 'activate_courier'];
            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                $courierActions = [];
            }

            return Inertia::render('Admin/Reports/Show', [
                'bucket' => 'courier',
                'id' => $report->id,
                'caseData' => $this->detailCourierReport($report),
                'transcript' => [],
                'availableActions' => $courierActions,
                'distributorWarnPresets' => AdminModerationService::DISTRIBUTOR_WARN_PRESETS,
            ]);
        }

        if ($bucket === 'delivery') {
            $review = DeliveryReview::query()
                ->with(['user:id,name,email', 'order.distributor', 'courier.user', 'delivery'])
                ->findOrFail($id);

            $deliveryActions = $review->admin_cleared_at
                ? []
                : ['clear_delivery_flag', 'suspend_courier', 'activate_courier'];

            return Inertia::render('Admin/Reports/Show', [
                'bucket' => 'delivery',
                'id' => $review->id,
                'caseData' => $this->detailDeliveryFlag($review),
                'transcript' => [],
                'availableActions' => $deliveryActions,
                'distributorWarnPresets' => AdminModerationService::DISTRIBUTOR_WARN_PRESETS,
            ]);
        }

        if ($bucket === 'product') {
            $report = ProductReport::query()
                ->with(['reporter:id,name,email,role', 'product:id,name', 'reviewer:id,name'])
                ->findOrFail($id);

            return Inertia::render('Admin/Reports/Show', [
                'bucket' => 'product',
                'id' => $report->id,
                'caseData' => $this->detailProductReport($report),
                'transcript' => [],
                'availableActions' => [],
                'distributorWarnPresets' => AdminModerationService::DISTRIBUTOR_WARN_PRESETS,
            ]);
        }

        abort(404);
    }

    public function updateCase(Request $request, string $bucket, int $id)
    {
        $bucket = strtolower($bucket);
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,reviewing,resolved,dismissed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $model = match ($bucket) {
            'message' => ConversationMessageReport::query()->findOrFail($id),
            'user' => UserReport::query()->findOrFail($id),
            'courier' => CourierReport::query()->findOrFail($id),
            'product' => ProductReport::query()->findOrFail($id),
            default => abort(404),
        };

        $model->update([
            'status' => $validated['status'],
            'admin_notes' => $request->has('admin_notes') ? $validated['admin_notes'] : $model->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Case updated.');
    }

    public function enforce(Request $request, string $bucket, int $id)
    {
        $bucket = strtolower($bucket);
        $this->assertCaseOpenForEnforcement($bucket, $id);

        $validated = $request->validate([
            'action' => ['required', 'string'],
            'accountability_notes' => ['required', 'string', 'min:15', 'max:5000'],
            'suspend_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'distributor_warn_preset' => ['nullable', 'string', 'max:100'],
            'distributor_warn_message' => ['nullable', 'string', 'max:1000'],
            'ban_or_suspension_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $action = $validated['action'];
        $notes = $validated['accountability_notes'];
        $admin = $request->user();

        DB::transaction(function () use ($bucket, $id, $action, $notes, $validated, $admin) {
            if ($bucket === 'message') {
                $this->enforceMessageReport($admin, ConversationMessageReport::query()->findOrFail($id), $action, $notes, $validated);

                return;
            }
            if ($bucket === 'user') {
                $this->enforceUserReport($admin, UserReport::query()->findOrFail($id), $action, $notes, $validated);

                return;
            }
            if ($bucket === 'courier') {
                $this->enforceCourierReport($admin, CourierReport::query()->findOrFail($id), $action, $notes, $validated);

                return;
            }
            if ($bucket === 'delivery') {
                $this->enforceDeliveryFlag($admin, DeliveryReview::query()->findOrFail($id), $action, $notes, $validated);

                return;
            }
            abort(404);
        });

        return back()->with('success', 'Enforcement applied and case recorded.');
    }

    private function assertCaseOpenForEnforcement(string $bucket, int $id): void
    {
        if ($bucket === 'message') {
            $report = ConversationMessageReport::query()->findOrFail($id);
            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                abort(422, 'This case is already closed.');
            }

            return;
        }
        if ($bucket === 'user') {
            $report = UserReport::query()->findOrFail($id);
            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                abort(422, 'This case is already closed.');
            }

            return;
        }
        if ($bucket === 'courier') {
            $report = CourierReport::query()->findOrFail($id);
            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                abort(422, 'This case is already closed.');
            }

            return;
        }
        if ($bucket === 'delivery') {
            $review = DeliveryReview::query()->findOrFail($id);
            if ($review->admin_cleared_at !== null) {
                abort(422, 'This case is already closed.');
            }

            return;
        }
        abort(404);
    }

    private function enforceMessageReport(User $admin, ConversationMessageReport $report, string $action, string $notes, array $validated): void
    {
        $report->load(['message.user', 'message.conversation.distributor']);
        $message = $report->message;
        if (! $message) {
            abort(422, 'Message missing.');
        }

        $distributor = $message->conversation?->distributor;
        $subjectUser = $message->user;

        if ($action === 'dismiss') {
            $report->update([
                'status' => 'dismissed',
                'resolution_action' => 'dismissed',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'no_action_resolve') {
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'resolved_no_action',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'ban_subject_user') {
            if (! $subjectUser) {
                abort(422, 'No subject user.');
            }
            $this->moderation->banPlatformUser($admin, $subjectUser, $notes);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'ban_subject_user',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'warn_distributor') {
            if (! $distributor) {
                abort(422, 'No distributor context.');
            }
            $preset = $validated['distributor_warn_preset'] ?? '';
            if ($preset === '') {
                abort(422, 'Warning preset required.');
            }
            $this->moderation->warnDistributor($admin, $distributor, $preset, $validated['distributor_warn_message'] ?? null);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'warn_distributor',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'suspend_distributor') {
            if (! $distributor) {
                abort(422, 'No distributor context.');
            }
            $days = (int) ($validated['suspend_days'] ?? 0);
            if ($days < 1) {
                abort(422, 'suspend_days required.');
            }
            $reason = $validated['ban_or_suspension_reason'] ?? $notes;
            $this->moderation->suspendDistributor($admin, $distributor, $days, $reason);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'suspend_distributor',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'ban_distributor') {
            if (! $distributor) {
                abort(422, 'No distributor context.');
            }
            $reason = $validated['ban_or_suspension_reason'] ?? $notes;
            $this->moderation->banDistributor($admin, $distributor, $reason);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'ban_distributor',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'lift_distributor_suspension') {
            if (! $distributor) {
                abort(422, 'No distributor context.');
            }
            $this->moderation->liftDistributorSuspension($admin, $distributor);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'lift_distributor_suspension',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        abort(422, 'Invalid action for this case.');
    }

    private function enforceUserReport(User $admin, UserReport $report, string $action, string $notes, array $validated): void
    {
        $subject = $report->subject;
        if (! $subject) {
            abort(422, 'Subject missing.');
        }

        if ($action === 'dismiss') {
            $report->update([
                'status' => 'dismissed',
                'resolution_action' => 'dismissed',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'no_action_resolve') {
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'resolved_no_action',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'ban_subject_user') {
            $this->moderation->banPlatformUser($admin, $subject, $notes);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'ban_subject_user',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        abort(422, 'Invalid action for user report.');
    }

    private function enforceCourierReport(User $admin, CourierReport $report, string $action, string $notes, array $validated): void
    {
        $report->load('courier');
        $courier = $report->courier;
        if (! $courier) {
            abort(422, 'Courier missing.');
        }

        if ($action === 'dismiss') {
            $report->update([
                'status' => 'dismissed',
                'resolution_action' => 'dismissed',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'no_action_resolve') {
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'resolved_no_action',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'suspend_courier') {
            $this->moderation->setCourierStatus($admin, $courier, 'suspended', $notes);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'suspend_courier',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        if ($action === 'activate_courier') {
            $this->moderation->setCourierStatus($admin, $courier, 'active', $notes);
            $report->update([
                'status' => 'resolved',
                'resolution_action' => 'activate_courier',
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            return;
        }

        abort(422, 'Invalid action for courier report.');
    }

    private function enforceDeliveryFlag(User $admin, DeliveryReview $review, string $action, string $notes, array $validated): void
    {
        $review->load('courier');
        $courier = $review->courier;

        if ($action === 'clear_delivery_flag') {
            $review->update([
                'admin_cleared_at' => now(),
            ]);

            return;
        }

        if (! $courier) {
            abort(422, 'No courier linked to this review.');
        }

        if ($action === 'suspend_courier') {
            $this->moderation->setCourierStatus($admin, $courier, 'suspended', $notes);
            $review->update(['admin_cleared_at' => now()]);

            return;
        }

        if ($action === 'activate_courier') {
            $this->moderation->setCourierStatus($admin, $courier, 'active', $notes);
            $review->update(['admin_cleared_at' => now()]);

            return;
        }

        abort(422, 'Invalid action for delivery review.');
    }

    private function summarizeMessageReport(ConversationMessageReport $r): array
    {
        $m = $r->message;
        $body = $m ? trim((string) $m->body) : '';

        return [
            'bucket' => 'message',
            'id' => $r->id,
            'status' => $r->status,
            'reason' => $r->reason,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? ['name' => $r->reporter->name, 'email' => $r->reporter->email] : null,
            'summary' => $m ? [
                'author_role' => $m->user?->role,
                'preview' => $body !== '' ? str($body)->limit(120)->toString() : ($m->image_path ? '[Image]' : '—'),
                'shop' => $m->conversation?->distributor?->company_name,
            ] : null,
        ];
    }

    private function summarizeUserReport(UserReport $r): array
    {
        return [
            'bucket' => 'user',
            'id' => $r->id,
            'status' => $r->status,
            'reason' => $r->reason,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? ['name' => $r->reporter->name, 'email' => $r->reporter->email] : null,
            'summary' => [
                'subject_name' => $r->subject?->name,
                'subject_email' => $r->subject?->email,
                'subject_role' => $r->subject?->role,
                'banned' => (bool) $r->subject?->banned_at,
            ],
        ];
    }

    private function summarizeCourierReport(CourierReport $r): array
    {
        return [
            'bucket' => 'courier',
            'id' => $r->id,
            'status' => $r->status,
            'reason' => $r->reason,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? ['name' => $r->reporter->name, 'email' => $r->reporter->email] : null,
            'summary' => [
                'courier_name' => $r->courier?->user?->name,
                'order_number' => $r->order?->order_number,
            ],
        ];
    }

    private function summarizeDeliveryFlag(DeliveryReview $d): array
    {
        return [
            'bucket' => 'delivery',
            'id' => $d->id,
            'status' => 'flag',
            'reason' => 'low_rating',
            'created_at' => $d->created_at->toIso8601String(),
            'reporter' => $d->user ? ['name' => $d->user->name, 'email' => $d->user->email] : null,
            'summary' => [
                'stars' => $d->stars,
                'courier_name' => $d->courier?->user?->name,
                'order_number' => $d->order?->order_number,
            ],
        ];
    }

    private function summarizeProductReport(ProductReport $r): array
    {
        return [
            'bucket' => 'product',
            'id' => $r->id,
            'status' => $r->status,
            'reason' => $r->reason,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? ['name' => $r->reporter->name, 'email' => $r->reporter->email] : null,
            'summary' => [
                'product_name' => $r->product?->name,
                'product_id' => $r->product?->id,
            ],
        ];
    }

    private function detailMessageReport(ConversationMessageReport $r): array
    {
        $m = $r->message;
        $body = $m ? trim((string) $m->body) : '';

        return [
            'type_label' => 'Chat / order message',
            'status' => $r->status,
            'reason' => $r->reason,
            'details' => $r->details,
            'admin_notes' => $r->admin_notes,
            'resolution_action' => $r->resolution_action,
            'resolution_notes' => $r->resolution_notes,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'reviewer' => $r->reviewer?->name,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? [
                'id' => $r->reporter->id,
                'name' => $r->reporter->name,
                'email' => $r->reporter->email,
                'role' => $r->reporter->role,
            ] : null,
            'message' => $m ? [
                'id' => $m->id,
                'body' => $body,
                'kind' => $m->kind,
                'image_url' => $m->image_path ? Storage::disk('public')->url($m->image_path) : null,
                'order_id' => $m->order_id,
                'conversation_id' => $m->conversation_id,
                'author' => $m->user ? [
                    'id' => $m->user->id,
                    'name' => $m->user->name,
                    'role' => $m->user->role,
                    'email' => $m->user->email,
                ] : null,
                'shop' => $m->conversation?->distributor?->company_name,
                'customer' => $m->conversation?->customer ? [
                    'name' => $m->conversation->customer->name,
                    'email' => $m->conversation->customer->email,
                ] : null,
            ] : null,
            'distributor' => $m?->conversation?->distributor ? [
                'id' => $m->conversation->distributor->id,
                'company_name' => $m->conversation->distributor->company_name,
                'slug' => $m->conversation->distributor->slug,
                'status' => $m->conversation->distributor->status,
                'suspended_until' => $m->conversation->distributor->suspended_until?->toIso8601String(),
            ] : null,
        ];
    }

    private function detailUserReport(UserReport $r, array $subjectStats): array
    {
        return [
            'type_label' => 'User report',
            'status' => $r->status,
            'reason' => $r->reason,
            'details' => $r->details,
            'admin_notes' => $r->admin_notes,
            'resolution_action' => $r->resolution_action,
            'resolution_notes' => $r->resolution_notes,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'reviewer' => $r->reviewer?->name,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? [
                'id' => $r->reporter->id,
                'name' => $r->reporter->name,
                'email' => $r->reporter->email,
            ] : null,
            'subject' => $r->subject ? [
                'id' => $r->subject->id,
                'name' => $r->subject->name,
                'email' => $r->subject->email,
                'role' => $r->subject->role,
                'banned_at' => $r->subject->banned_at?->toIso8601String(),
                'ban_reason' => $r->subject->ban_reason,
            ] : null,
            'subject_stats' => $subjectStats,
        ];
    }

    private function detailProductReport(ProductReport $r): array
    {
        return [
            'type_label' => 'Product listing report',
            'status' => $r->status,
            'reason' => $r->reason,
            'details' => $r->details,
            'admin_notes' => $r->admin_notes,
            'resolution_action' => null,
            'resolution_notes' => null,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'reviewer' => $r->reviewer?->name,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? [
                'id' => $r->reporter->id,
                'name' => $r->reporter->name,
                'email' => $r->reporter->email,
                'role' => $r->reporter->role,
            ] : null,
            'product' => $r->product ? [
                'id' => $r->product->id,
                'name' => $r->product->name,
                'public_url' => url('/products/'.$r->product->id),
            ] : null,
        ];
    }

    private function detailCourierReport(CourierReport $r): array
    {
        $c = $r->courier;

        return [
            'type_label' => 'Courier report',
            'status' => $r->status,
            'reason' => $r->reason,
            'details' => $r->details,
            'admin_notes' => $r->admin_notes,
            'resolution_action' => $r->resolution_action,
            'resolution_notes' => $r->resolution_notes,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'reviewer' => $r->reviewer?->name,
            'created_at' => $r->created_at->toIso8601String(),
            'reporter' => $r->reporter ? [
                'id' => $r->reporter->id,
                'name' => $r->reporter->name,
                'email' => $r->reporter->email,
            ] : null,
            'courier' => $c ? [
                'id' => $c->id,
                'status' => $c->status,
                'vehicle_type' => $c->vehicle_type,
                'plate_number' => $c->plate_number,
                'user' => $c->user ? [
                    'name' => $c->user->name,
                    'email' => $c->user->email,
                ] : null,
            ] : null,
            'order' => $r->order ? [
                'id' => $r->order->id,
                'order_number' => $r->order->order_number,
            ] : null,
        ];
    }

    private function detailDeliveryFlag(DeliveryReview $d): array
    {
        return [
            'type_label' => 'Low delivery rating (≤2★)',
            'status' => $d->admin_cleared_at ? 'cleared' : 'open',
            'reason' => 'customer_rating',
            'details' => $d->body,
            'admin_notes' => null,
            'resolution_action' => null,
            'resolution_notes' => null,
            'reviewed_at' => null,
            'reviewer' => null,
            'created_at' => $d->created_at->toIso8601String(),
            'reporter' => $d->user ? [
                'id' => $d->user->id,
                'name' => $d->user->name,
                'email' => $d->user->email,
            ] : null,
            'stars' => $d->stars,
            'order' => $d->order ? [
                'id' => $d->order->id,
                'order_number' => $d->order->order_number,
            ] : null,
            'courier' => $d->courier ? [
                'id' => $d->courier->id,
                'status' => $d->courier->status,
                'user' => $d->courier->user ? [
                    'name' => $d->courier->user->name,
                    'email' => $d->courier->user->email,
                ] : null,
            ] : null,
        ];
    }

    private function transcriptLine(ConversationMessage $m, ?int $highlightMessageId = null): array
    {
        $meta = is_array($m->meta) ? $m->meta : [];

        return [
            'id' => $m->id,
            'kind' => $m->kind,
            'body' => trim((string) $m->body),
            'image_url' => $m->image_path ? Storage::disk('public')->url($m->image_path) : null,
            'created_at' => $m->created_at->toIso8601String(),
            'user' => $m->user ? [
                'name' => $m->user->name,
                'role' => $m->user->role,
            ] : null,
            'highlight' => $highlightMessageId !== null && $m->id === $highlightMessageId,
            'rx_event' => $meta['rx_event'] ?? null,
        ];
    }

    private function userStats(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'orders_placed' => Order::query()->where('customer_id', $user->id)->count(),
        ];
    }
}
