<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Distributor;
use App\Models\User;
use App\Notifications\DistributorModerationNotification;

class AdminModerationService
{
    public const DISTRIBUTOR_WARN_PRESETS = [
        'High Cancellation Rate',
        'Fulfillment Delays (>48 hours)',
        'Zero Active Inventory',
        'Reported via moderation',
        'Other',
    ];

    public function assertStaff(User $actor): void
    {
        if (! in_array($actor->role, ['admin', 'super_admin'], true)) {
            abort(403, 'Not authorized.');
        }
    }

    public function banPlatformUser(User $actor, User $target, string $reason): void
    {
        $this->assertStaff($actor);
        if ($target->role === 'super_admin') {
            abort(422, 'Cannot ban a super admin.');
        }
        if (in_array($target->role, ['admin', 'super_admin'], true)) {
            abort(422, 'Cannot ban platform administrators.');
        }

        $target->forceFill([
            'banned_at' => now(),
            'ban_reason' => $reason,
        ])->save();
    }

    public function approveDistributor(User $actor, Distributor $distributor): void
    {
        $this->assertStaff($actor);
        $distributor->update([
            'status' => 'approved',
            'rejection_count' => 0,
        ]);
        $distributor->refresh();

        $this->notifyDistributorTeam($distributor, new DistributorModerationNotification('distributor_approved', $distributor));
    }

    public function rejectDistributor(User $actor, Distributor $distributor, ?string $reason): void
    {
        $this->assertStaff($actor);

        $distributor->increment('rejection_count');

        $updates = [
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ];

        // If rejected 3 times, auto-suspend
        if ($distributor->rejection_count >= 3) {
            $updates['suspended_until'] = now()->addYears(10); // Effectively permanent
            $updates['suspension_reason'] = 'Maximum identification/application retries exceeded (3 failed attempts). Account suspended for security review.';
        }

        $distributor->update($updates);
        $distributor->refresh();

        $this->notifyDistributorTeam($distributor, new DistributorModerationNotification('distributor_rejected', $distributor, [
            'reason' => $reason,
            'rejection_count' => $distributor->rejection_count,
            'is_auto_suspended' => $distributor->rejection_count >= 3,
        ]));
    }

    public function warnDistributor(User $actor, Distributor $distributor, string $preset, ?string $customMessage): void
    {
        $this->assertStaff($actor);
        if (! in_array($preset, self::DISTRIBUTOR_WARN_PRESETS, true)) {
            abort(422, 'Invalid warning preset.');
        }

        $distributor->update([
            'warning_reason' => $preset,
            'warning_message' => $customMessage,
        ]);
        $distributor->refresh();
        $this->notifyDistributorTeam($distributor, new DistributorModerationNotification('distributor_warned', $distributor, [
            'preset' => $preset,
            'custom_message' => $customMessage ?? '',
        ]));
    }

    public function suspendDistributor(User $actor, Distributor $distributor, int $days, string $reason): void
    {
        $this->assertStaff($actor);
        $distributor->update([
            'suspended_until' => now()->addDays($days),
            'suspension_reason' => $reason,
        ]);
        $distributor->refresh();
        $this->notifyDistributorTeam($distributor, new DistributorModerationNotification('distributor_suspended', $distributor, [
            'days' => $days,
            'reason' => $reason,
        ]));
    }

    public function liftDistributorSuspension(User $actor, Distributor $distributor): void
    {
        $this->assertStaff($actor);
        $distributor->update([
            'suspended_until' => null,
            'suspension_reason' => null,
        ]);
        $distributor->refresh();
        $this->notifyDistributorTeam($distributor, new DistributorModerationNotification('distributor_suspension_lifted', $distributor));
    }

    public function banDistributor(User $actor, Distributor $distributor, string $reason): void
    {
        $this->assertStaff($actor);
        $distributor->update([
            'status' => 'banned',
            'rejection_reason' => $reason,
        ]);
        $distributor->refresh();
        $this->notifyDistributorTeam($distributor, new DistributorModerationNotification('distributor_banned', $distributor, [
            'reason' => $reason,
        ]));
    }

    private function notifyDistributorTeam(Distributor $distributor, DistributorModerationNotification $notification): void
    {
        $ids = collect([$distributor->user_id])
            ->merge(
                User::query()
                    ->where('distributor_id', $distributor->id)
                    ->where('role', 'staff')
                    ->pluck('id')
            )
            ->unique()
            ->filter();

        User::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(fn (User $user) => $user->notify($notification));
    }

    public function setCourierStatus(User $actor, Courier $courier, string $status, ?string $note = null): void
    {
        $this->assertStaff($actor);
        if (! in_array($status, ['active', 'suspended'], true)) {
            abort(422, 'Invalid courier status.');
        }

        $courier->update([
            'status' => $status,
        ]);

        if ($note !== null && $note !== '') {
            \Illuminate\Support\Facades\Log::info('[AdminModeration] Courier status change', [
                'courier_id' => $courier->id,
                'status' => $status,
                'note' => $note,
                'admin_id' => $actor->id,
            ]);
        }
    }
}
