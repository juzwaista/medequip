<template>
    <AdminLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8">
            <div>
                <Link
                    :href="hubHref"
                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                >
                    ← Back to reports hub
                </Link>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mt-3">
                    {{ caseData_.type_label }} <span class="text-gray-400 font-mono text-lg">#{{ id }}</span>
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    Review the details and take action if needed.
                </p>
            </div>

            <!-- Summary card -->
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Status</span>
                    <span class="px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-800">{{ caseData_.status }}</span>
                    <span v-if="caseData_.reviewed_at" class="text-xs text-gray-500">Reviewed {{ formatWhen(caseData_.reviewed_at) }}</span>
                    <span v-if="caseData_.reviewer" class="text-xs text-gray-500">by {{ caseData_.reviewer }}</span>
                </div>
                <div v-if="caseData_.reason" class="grid gap-1">
                    <span class="text-xs font-bold uppercase text-gray-500">Reason</span>
                    <p class="text-sm text-gray-900">{{ caseData_.reason }}</p>
                </div>
                <div v-if="caseData_.details" class="grid gap-1">
                    <span class="text-xs font-bold uppercase text-gray-500">Reporter details</span>
                    <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ caseData_.details }}</p>
                </div>
                <div v-if="caseData_.resolution_action" class="grid gap-1 border-t border-gray-100 pt-4">
                    <span class="text-xs font-bold uppercase text-gray-500">Last resolution</span>
                    <p class="text-sm text-gray-900">{{ caseData_.resolution_action }}</p>
                    <p v-if="caseData_.resolution_notes" class="text-sm text-gray-600 whitespace-pre-wrap">{{ caseData_.resolution_notes }}</p>
                </div>
            </section>

            <!-- Message body + media -->
            <section v-if="bucket === 'message' && caseData_.message" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900">Reported message</h2>
                <div class="text-sm text-gray-600 flex flex-wrap gap-3">
                    <span v-if="caseData_.message.author">From {{ caseData_.message.author.name }} ({{ caseData_.message.author.role }})</span>
                    <span v-if="caseData_.message.shop">Shop: {{ caseData_.message.shop }}</span>
                    <span v-if="caseData_.message.order_id">Order #{{ caseData_.message.order_id }}</span>
                </div>
                <p v-if="caseData_.message.body" class="text-sm text-gray-900 whitespace-pre-wrap border border-gray-100 rounded-lg p-4 bg-gray-50">{{ caseData_.message.body }}</p>
                <div v-if="caseData_.message.image_url" class="rounded-lg overflow-hidden border border-gray-200 max-w-md">
                    <img :src="caseData_.message.image_url" alt="Attachment" class="w-full h-auto object-contain max-h-96 bg-gray-100" />
                </div>
            </section>

            <!-- Transcript -->
            <section v-if="bucket === 'message' && transcript?.length" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Conversation excerpt</h2>
                <p class="text-xs text-gray-500 mb-4">The reported message is highlighted. Review surrounding context before acting.</p>
                <ul class="space-y-3">
                    <li
                        v-for="line in transcript"
                        :key="line.id"
                        class="rounded-lg border p-3 text-sm"
                        :class="line.highlight ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-gray-100 bg-gray-50/80'"
                    >
                        <div class="flex justify-between gap-2 text-xs text-gray-500 mb-1">
                            <span>{{ line.user?.name }} · {{ line.user?.role }}</span>
                            <span>{{ formatWhen(line.created_at) }}</span>
                        </div>
                        <p v-if="line.body" class="text-gray-900 whitespace-pre-wrap">{{ line.body }}</p>
                        <img v-if="line.image_url" :src="line.image_url" alt="" class="mt-2 max-h-48 rounded border border-gray-200" />
                    </li>
                </ul>
            </section>

            <!-- User report subject -->
            <section v-if="bucket === 'user' && caseData_.subject" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900">Reported user</h2>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">Name</dt><dd class="font-medium">{{ caseData_.subject.name }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="break-all">{{ caseData_.subject.email }}</dd></div>
                    <div><dt class="text-gray-500">Role</dt><dd>{{ caseData_.subject.role }}</dd></div>
                    <div><dt class="text-gray-500">Banned</dt><dd>{{ caseData_.subject.banned_at ? 'Yes — ' + formatWhen(caseData_.subject.banned_at) : 'No' }}</dd></div>
                    <div v-if="caseData_.subject.ban_reason" class="sm:col-span-2">
                        <dt class="text-gray-500">Ban reason on file</dt>
                        <dd class="whitespace-pre-wrap">{{ caseData_.subject.ban_reason }}</dd>
                    </div>
                </dl>
                <p v-if="caseData_.subject_stats?.orders_placed != null" class="text-xs text-gray-500">
                    Orders placed (as customer): {{ caseData_.subject_stats.orders_placed }}
                </p>
            </section>

            <!-- Courier report -->
            <section v-if="bucket === 'courier' && caseData_.courier" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900">Courier</h2>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ caseData_.courier.status }}</dd></div>
                    <div><dt class="text-gray-500">Vehicle</dt><dd>{{ caseData_.courier.vehicle_type || '—' }}</dd></div>
                    <div><dt class="text-gray-500">Plate</dt><dd>{{ caseData_.courier.plate_number || '—' }}</dd></div>
                    <div v-if="caseData_.courier.user">
                        <dt class="text-gray-500">Account</dt>
                        <dd>{{ caseData_.courier.user.name }} · {{ caseData_.courier.user.email }}</dd>
                    </div>
                    <div v-if="caseData_.order"><dt class="text-gray-500">Order</dt><dd>#{{ caseData_.order.order_number }}</dd></div>
                </dl>
            </section>

            <!-- Delivery flag -->
            <section v-if="bucket === 'delivery'" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900">Delivery review</h2>
                <p class="flex items-center gap-1.5 text-2xl font-bold text-amber-700">
                    <svg class="w-7 h-7 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ caseData_.stars }}
                </p>
                <p v-if="caseData_.details" class="text-sm text-gray-800 whitespace-pre-wrap">{{ caseData_.details }}</p>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div v-if="caseData_.courier"><dt class="text-gray-500">Courier</dt><dd>{{ caseData_.courier.user?.name }} ({{ caseData_.courier.status }})</dd></div>
                    <div v-if="caseData_.order"><dt class="text-gray-500">Order</dt><dd>#{{ caseData_.order.order_number }}</dd></div>
                </dl>
            </section>

            <!-- Product report -->
            <section v-if="bucket === 'product' && caseData_.product" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900">Reported listing</h2>
                <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Product</dt>
                        <dd class="font-medium text-gray-900">{{ caseData_.product.name }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <a
                            :href="caseData_.product.public_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-blue-600 hover:text-blue-800 text-sm font-semibold"
                        >
                            Open product page (new tab)
                        </a>
                    </div>
                </dl>
            </section>

            <!-- Reporter -->
            <section v-if="caseData_.reporter" class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-sm">
                <span class="text-xs font-bold uppercase text-slate-500">Reporter</span>
                <p class="font-medium text-slate-900">{{ caseData_.reporter.name }}</p>
                <p class="text-slate-600 break-all">{{ caseData_.reporter.email }}</p>
            </section>

            <!-- Triage (not for delivery flags) -->
            <section v-if="canTriage" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Case triage</h2>
                <p class="text-sm text-gray-600">Update workflow status and internal admin notes (does not apply warnings or bans by itself).</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="text-gray-700 font-medium">Status</span>
                        <select v-model="manageForm.status" class="mt-1 w-full rounded-lg border-gray-300 text-sm min-h-[44px]">
                            <option v-for="s in triageStatuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </label>
                </div>
                <label class="block text-sm">
                    <span class="text-gray-700 font-medium">Admin notes (internal)</span>
                    <textarea
                        v-model="manageForm.admin_notes"
                        rows="3"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Visible to admins only…"
                    />
                </label>
                <button
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold hover:bg-slate-900 disabled:opacity-50"
                    :disabled="manageForm.processing"
                    @click="submitTriage"
                >
                    Save triage
                </button>
                <p v-if="manageForm.errors.status" class="text-sm text-red-600">{{ manageForm.errors.status }}</p>
                <p v-if="manageForm.errors.admin_notes" class="text-sm text-red-600">{{ manageForm.errors.admin_notes }}</p>
            </section>

            <!-- Closed case — no further enforcement -->
            <section
                v-if="showDecisionRecordedPanel"
                class="bg-slate-50 rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6"
            >
                <h2 class="text-lg font-semibold text-gray-900">Decision recorded</h2>
                <p class="text-sm text-gray-600 mt-2">
                    No further enforcement actions are available for this case.
                </p>
            </section>

            <!-- Enforcement -->
            <section v-if="availableActions.length" class="bg-rose-50/60 rounded-xl border border-rose-200 shadow-sm p-5 sm:p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Enforcement</h2>
                <p class="text-sm text-gray-700">
                    Choose an action and document why you are taking it. This text is stored as the official resolution record for this case.
                </p>

                <label class="block text-sm">
                    <span class="text-gray-700 font-medium">Action</span>
                    <select v-model="enforceForm.action" class="mt-1 w-full rounded-lg border-gray-300 text-sm min-h-[44px]">
                        <option value="" disabled>Select…</option>
                        <option v-for="a in availableActions" :key="a" :value="a">{{ actionLabel(a) }}</option>
                    </select>
                </label>

                <template v-if="needsWarnPreset">
                    <label class="block text-sm">
                        <span class="text-gray-700 font-medium">Warning preset</span>
                        <select v-model="enforceForm.distributor_warn_preset" class="mt-1 w-full rounded-lg border-gray-300 text-sm min-h-[44px]">
                            <option value="" disabled>Select…</option>
                            <option v-for="p in distributorWarnPresets" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="text-gray-700 font-medium">Custom message (optional)</span>
                        <textarea v-model="enforceForm.distributor_warn_message" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </label>
                </template>

                <template v-if="needsSuspendDays">
                    <label class="block text-sm">
                        <span class="text-gray-700 font-medium">Suspend for (days)</span>
                        <input
                            v-model.number="enforceForm.suspend_days"
                            type="number"
                            min="1"
                            max="365"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm min-h-[44px]"
                        />
                    </label>
                </template>

                <template v-if="needsBanReason">
                    <label class="block text-sm">
                        <span class="text-gray-700 font-medium">Reason (also logged on the shop record)</span>
                        <input v-model="enforceForm.ban_or_suspension_reason" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </label>
                </template>

                <label class="block text-sm">
                    <span class="text-gray-700 font-medium">Accountability notes (min. 15 characters)</span>
                    <textarea
                        v-model="enforceForm.accountability_notes"
                        rows="4"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                        placeholder="What did you verify, and why is this action proportionate?"
                    />
                </label>

                <button
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700 disabled:opacity-50"
                    :disabled="enforceForm.processing || !enforceForm.action"
                    @click="submitEnforce"
                >
                    Apply enforcement
                </button>
                <p v-if="enforceForm.errors.action" class="text-sm text-red-600">{{ enforceForm.errors.action }}</p>
                <p v-if="enforceForm.errors.accountability_notes" class="text-sm text-red-600">{{ enforceForm.errors.accountability_notes }}</p>
                <p v-if="enforceForm.errors.suspend_days" class="text-sm text-red-600">{{ enforceForm.errors.suspend_days }}</p>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    bucket: String,
    id: Number,
    caseData: Object,
    transcript: Array,
    availableActions: Array,
    distributorWarnPresets: Array,
});

const caseData_ = computed(() => props.caseData || {});

const showDecisionRecordedPanel = computed(() => {
    if ((props.availableActions || []).length > 0) {
        return false;
    }
    const c = caseData_.value;
    if (!c || Object.keys(c).length === 0) {
        return false;
    }
    if (c.resolution_action) {
        return true;
    }
    if (['resolved', 'dismissed'].includes(c.status)) {
        return true;
    }
    if (props.bucket === 'delivery' && c.status === 'cleared') {
        return true;
    }
    return false;
});

const hubHref = computed(() => {
    const tab = { message: 'messages', user: 'users', courier: 'couriers', delivery: 'couriers', product: 'products' }[props.bucket] || 'messages';
    return route('admin.reports.index', { tab, status: 'open' });
});

const canTriage = computed(() => ['message', 'user', 'courier', 'product'].includes(props.bucket));

const triageStatuses = ['open', 'reviewing', 'resolved', 'dismissed'];

const manageForm = useForm({
    status: props.caseData?.status || 'open',
    admin_notes: props.caseData?.admin_notes || '',
});

watch(
    () => props.caseData,
    (c) => {
        if (!c) return;
        manageForm.status = c.status || 'open';
        manageForm.admin_notes = c.admin_notes || '';
    },
    { deep: true }
);

const enforceForm = useForm({
    action: '',
    accountability_notes: '',
    suspend_days: 7,
    distributor_warn_preset: '',
    distributor_warn_message: '',
    ban_or_suspension_reason: '',
});

const needsWarnPreset = computed(() => enforceForm.action === 'warn_distributor');
const needsSuspendDays = computed(() => enforceForm.action === 'suspend_distributor');
const needsBanReason = computed(() => ['ban_distributor', 'suspend_distributor'].includes(enforceForm.action));

function actionLabel(a) {
    const map = {
        dismiss: 'Dismiss report',
        no_action_resolve: 'Resolve — no platform enforcement',
        ban_subject_user: 'Ban reported user (platform)',
        warn_distributor: 'Issue distributor warning',
        suspend_distributor: 'Suspend shop',
        ban_distributor: 'Ban shop',
        lift_distributor_suspension: 'Lift shop suspension',
        suspend_courier: 'Suspend courier',
        activate_courier: 'Set courier active',
        clear_delivery_flag: 'Clear rating flag (no penalty)',
    };
    return map[a] || a;
}

function formatWhen(iso) {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '';
    }
}

function submitTriage() {
    manageForm.patch(route('admin.reports.update', { bucket: props.bucket, id: props.id }), { preserveScroll: true });
}

function submitEnforce() {
    const ok = window.confirm(
        'Apply this enforcement? This will update the case and may affect user or shop accounts. This cannot be undone from this screen.'
    );
    if (!ok) {
        return;
    }
    enforceForm.post(route('admin.reports.enforce', { bucket: props.bucket, id: props.id }), {
        preserveScroll: true,
        onSuccess: () => {
            enforceForm.reset();
            enforceForm.clearErrors();
        },
    });
}
</script>
