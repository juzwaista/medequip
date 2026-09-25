<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/75 backdrop-blur-sm" @click.self="emit('close')">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
                <div class="px-6 py-5 border-b border-gray-100" :class="config.headerBg">
                    <h3 class="text-lg font-bold text-gray-900">{{ config.title }}</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Warn -->
                    <template v-if="action === 'warn'">
                        <p class="text-sm text-gray-600">Send a warning to <strong>{{ targetName }}</strong>.</p>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Reason</label>
                            <select v-model="presetReason" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm">
                                <option value="" disabled>Select a reason...</option>
                                <option value="High Cancellation Rate">High Cancellation Rate</option>
                                <option value="Fulfillment Delays (>48 hours)">Fulfillment Delays (>48 hours)</option>
                                <option value="Zero Active Inventory">Zero Active Inventory</option>
                                <option value="Reported via moderation">Reported via moderation</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <textarea v-model="reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Optional message..."></textarea>
                    </template>

                    <!-- Suspend -->
                    <template v-if="action === 'suspend'">
                        <p class="text-sm text-gray-600">Temporarily suspend <strong>{{ targetName }}</strong>. They will not be able to accept orders until it ends.</p>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Reason</label>
                            <select v-model="reason" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm">
                                <option value="" disabled>Select a reason...</option>
                                <option value="Sustained High Cancellation Rate">Repeated Cancellations</option>
                                <option value="Severe Fulfillment Delays">Fulfillment Delays</option>
                                <option value="Policy Violation">Policy Violation</option>
                                <option value="Customer Complaints">Customer Complaints</option>
                                <option value="Regulatory Action (FDA)">Regulatory Verification</option>
                                <option value="Other/Administrative">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Duration (days)</label>
                            <input type="number" min="1" max="365" v-model="days" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm" />
                        </div>
                    </template>

                    <!-- Ban distributor -->
                    <template v-if="action === 'ban'">
                        <p class="text-sm text-gray-600">Permanently ban <strong>{{ targetName }}</strong>? All products will be hidden.</p>
                        <textarea v-model="reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Reason for ban (required)..."></textarea>
                    </template>

                    <!-- Lift suspension -->
                    <p v-if="action === 'lift'" class="text-sm text-gray-600">
                        Lift the suspension for <strong>{{ targetName }}</strong>? They will be able to accept orders immediately.
                    </p>

                    <!-- Ban user -->
                    <template v-if="action === 'ban_user'">
                        <p class="text-sm text-gray-600">Ban user <strong>{{ targetName }}</strong>? They will be signed out and unable to log in.</p>
                        <textarea v-model="reason" rows="3" class="w-full border border-gray-200 rounded-xl text-sm p-3" placeholder="Reason for ban (required)..."></textarea>
                    </template>

                    <!-- Unban user -->
                    <p v-if="action === 'unban_user'" class="text-sm text-gray-600">
                        Unban <strong>{{ targetName }}</strong>? They will be able to log in again.
                    </p>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" @click="emit('close')" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="button" @click="submit" :disabled="!canSubmit || processing" :class="config.confirmClass" class="px-4 py-2 text-sm font-bold text-white rounded-lg transition disabled:opacity-50">
                        {{ config.confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Confirmation dialog for admin moderation actions. Used from the detail pages so an admin
 * reviews the account first and only then acts on it.
 *
 * action: 'warn' | 'suspend' | 'ban' | 'lift'  → distributor (targetId = distributor id)
 *         'ban_user' | 'unban_user'            → platform user (targetId = user id)
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    action: { type: String, default: '' },
    targetId: { type: [Number, String], default: null },
    targetName: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const reason = ref('');
const presetReason = ref('');
const days = ref(7);
const processing = ref(false);

// Start every dialog from a clean slate.
watch(() => [props.open, props.action], () => {
    reason.value = '';
    presetReason.value = '';
    days.value = 7;
});

const CONFIGS = {
    warn: { title: 'Issue Warning', headerBg: 'bg-blue-50', confirmClass: 'bg-blue-600 hover:bg-blue-700', confirmLabel: 'Send Warning' },
    suspend: { title: 'Suspend Distributor', headerBg: 'bg-orange-50', confirmClass: 'bg-orange-600 hover:bg-orange-700', confirmLabel: 'Suspend' },
    ban: { title: 'Permanently Ban Distributor', headerBg: 'bg-rose-50', confirmClass: 'bg-rose-700 hover:bg-rose-800', confirmLabel: 'Confirm Ban' },
    lift: { title: 'Lift Suspension', headerBg: 'bg-emerald-50', confirmClass: 'bg-emerald-600 hover:bg-emerald-700', confirmLabel: 'Lift Suspension' },
    ban_user: { title: 'Ban User', headerBg: 'bg-red-50', confirmClass: 'bg-red-600 hover:bg-red-700', confirmLabel: 'Confirm Ban' },
    unban_user: { title: 'Unban User', headerBg: 'bg-emerald-50', confirmClass: 'bg-emerald-600 hover:bg-emerald-700', confirmLabel: 'Unban' },
};

const config = computed(() => CONFIGS[props.action] || { title: '', headerBg: '', confirmClass: 'bg-blue-600', confirmLabel: 'Confirm' });

const canSubmit = computed(() => {
    if (props.action === 'ban' || props.action === 'ban_user') return reason.value.trim().length > 0;
    if (props.action === 'warn') return !!presetReason.value;
    if (props.action === 'suspend') return !!reason.value && days.value >= 1;
    return true;
});

const submit = () => {
    const id = props.targetId;
    const done = { preserveScroll: true, onStart: () => { processing.value = true; }, onFinish: () => { processing.value = false; }, onSuccess: () => emit('close') };

    switch (props.action) {
        case 'warn':
            router.post(`/admin/distributors/${id}/warn`, { preset_reason: presetReason.value, custom_message: reason.value }, done);
            break;
        case 'suspend':
            router.post(`/admin/distributors/${id}/suspend`, { reason: reason.value, days: days.value }, done);
            break;
        case 'ban':
            router.post(`/admin/distributors/${id}/ban`, { reason: reason.value }, done);
            break;
        case 'lift':
            router.post(`/admin/distributors/${id}/lift-suspension`, {}, done);
            break;
        case 'ban_user':
            router.post(`/admin/users/${id}/ban`, { reason: reason.value }, done);
            break;
        case 'unban_user':
            router.post(`/admin/users/${id}/unban`, {}, done);
            break;
    }
};
</script>
