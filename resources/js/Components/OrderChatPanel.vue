<template>
    <div
        id="order-chat"
        class="overflow-hidden flex flex-col"
        :class="[panelRootClass, fillViewport ? 'bg-transparent' : 'bg-white']"
    >
        <div
            v-if="showHeader"
            class="shrink-0 px-3 sm:px-4 py-3 border-b"
            :class="fillViewport ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gradient-to-r from-slate-50 to-gray-50'"
        >
            <div class="flex items-start gap-2 min-w-0">
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="shrink-0 mt-0.5 p-1.5 -ml-1 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-gray-100 transition"
                    :aria-label="backLabel"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="min-w-0 flex-1">
                    <component
                        :is="titleHref ? Link : 'h2'"
                        v-bind="titleHref ? { href: titleHref } : {}"
                        class="text-lg font-bold text-gray-900 truncate block"
                        :class="titleHref ? 'hover:text-blue-600 transition' : ''"
                    >
                        {{ displayTitle }}
                    </component>
                    <p v-if="showSubtitle" class="text-xs mt-0.5 text-gray-600">{{ displaySubtitle }}</p>
                    <p
                        v-if="presenceLine"
                        class="text-xs mt-1 font-medium"
                        :class="presenceLine === 'Online' ? 'text-emerald-600' : 'text-gray-500'"
                    >
                        {{ presenceLine }}
                    </p>
                </div>
            </div>
        </div>

        <div
            ref="scrollRef"
            class="flex-1 overflow-y-auto space-y-3 min-h-[12rem]"
            :class="scrollAreaClass"
        >
            <p v-if="!loading && messages.length === 0" class="text-sm text-gray-500 text-center py-8">
                No messages yet. Say hello or ask about delivery.
            </p>
            <template v-for="seg in timelineSegments" :key="seg.key">
                <div v-if="seg.type === 'divider'" class="flex justify-center py-1">
                    <span class="text-[11px] font-semibold text-gray-500 bg-white/90 border border-gray-200/80 shadow-sm px-3 py-1 rounded-full">
                        {{ seg.label }}
                    </span>
                </div>
                <div
                    v-else
                    class="flex gap-2 w-full"
                    :class="seg.message.is_mine ? 'justify-end pl-8 sm:pl-12' : 'justify-start pr-8 sm:pr-12'"
                >
                    <span
                        v-if="!seg.message.is_mine"
                        class="relative shrink-0 mt-1 self-start"
                    >
                        <component
                            :is="avatarLinkTag(seg.message)"
                            v-bind="avatarLinkProps(seg.message)"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold border-2 shadow-sm overflow-hidden"
                            :class="avatarToneClass(seg.message)"
                        >
                            <span v-if="seg.message.is_automated || prescriptionAvatarIcon(seg.message)" class="text-lg leading-none" aria-hidden="true">{{ prescriptionAvatarIcon(seg.message) || '✉' }}</span>
                            <span v-else>{{ avatarInitial(seg.message) }}</span>
                        </component>
                        <span
                            v-if="presenceLine === 'Online'"
                            class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"
                            aria-hidden="true"
                        />
                    </span>
                    <div class="flex flex-col min-w-0 max-w-full lg:max-w-xl" :class="seg.message.is_mine ? 'items-end' : ''">
                        <div
                            class="px-3 py-2 text-sm shadow-sm"
                            :class="[bubbleClass(seg.message), fillViewport ? 'rounded-[20px]' : 'rounded-2xl']"
                        >
                            <div v-if="!seg.message.is_mine" class="flex items-center justify-between gap-2 mb-1">
                                <p class="text-[11px] font-semibold text-gray-500 truncate">
                                    {{ headerLabel(seg.message) }}
                                    <span v-if="seg.message.is_automated" class="text-violet-600 font-bold"> · Auto</span>
                                    <span v-else-if="seg.message.is_prescription && seg.message.rx?.event !== 'uploaded'" class="text-amber-700 font-bold"> · Rx</span>
                                </p>
                                <button
                                    v-if="canReport(seg.message)"
                                    type="button"
                                    class="text-[10px] font-semibold text-rose-600 hover:text-rose-800 shrink-0"
                                    @click="openReport(seg.message)"
                                >
                                    Report
                                </button>
                            </div>
                            <p
                                v-if="isPrescriptionUploadedBubble(seg.message)"
                                class="whitespace-pre-wrap break-words font-medium text-amber-950"
                            >
                                {{ prescriptionUploadedPrimaryLine(seg.message) }}
                            </p>
                            <p v-else-if="seg.message.body" class="whitespace-pre-wrap break-words">{{ seg.message.body }}</p>
                            <div
                                v-if="seg.message.image_url"
                                class="block mt-2 rounded-lg overflow-hidden border border-black/10 max-w-xs cursor-pointer group relative"
                                @click="imageModalUrl = seg.message.image_url"
                            >
                                <img :src="seg.message.image_url" alt="Prescription or attachment" class="w-full h-auto max-h-48 object-cover group-hover:opacity-90 transition" loading="lazy" />
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition pointer-events-none">
                                    <span class="bg-black/50 text-white p-2 rounded-full backdrop-blur-sm shadow">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                    </span>
                                </div>
                            </div>
                            <div
                                v-if="seg.message.rx?.can_review && isShopViewer"
                                class="mt-3 flex flex-col sm:flex-row gap-2"
                            >
                                <button
                                    type="button"
                                    class="flex-1 min-h-[44px] rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 px-3 py-2"
                                    :disabled="rxActionLoading"
                                    @click="approvePrescriptionChat(seg.message)"
                                >
                                    Approve prescription
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 min-h-[44px] rounded-lg border border-rose-300 text-rose-700 text-xs font-bold hover:bg-rose-50 px-3 py-2"
                                    :disabled="rxActionLoading"
                                    @click="openRxReject(seg.message)"
                                >
                                    Reject
                                </button>
                            </div>
                            <p v-if="seg.message.is_prescription && seg.message.rx?.prescription_status === 'rejected' && seg.message.rx?.review_note" class="mt-2 text-xs text-rose-800 bg-rose-50/80 rounded-lg px-2 py-1">
                                {{ seg.message.rx.review_note }}
                            </p>
                        </div>
                        <div
                            class="mt-1 px-0.5 min-w-0"
                            :class="seg.message.is_mine ? 'text-right' : 'text-left'"
                        >
                            <span class="text-[11px] tabular-nums text-gray-400">{{ formatClock(seg.message.created_at) }}</span>
                            <span
                                v-if="outgoingDeliveryStatus(seg.message)"
                                class="text-[11px] text-gray-400"
                            >
                                · {{ outgoingDeliveryStatus(seg.message) }}
                            </span>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <p v-if="profanityHint" class="px-4 py-2 text-xs font-semibold text-rose-800 bg-rose-50 border-t border-rose-100">
            This message includes banned words. Please remove them before sending.
        </p>

        <form
            class="shrink-0 p-2 sm:p-3 border-t flex flex-row flex-nowrap items-end gap-1.5 sm:gap-2"
            :class="fillViewport ? 'border-gray-200 bg-white' : 'border-gray-100 bg-white'"
            @submit.prevent="send"
        >
            <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFile" />
            <button
                type="button"
                class="shrink-0 w-10 h-10 sm:w-11 sm:h-11 inline-flex items-center justify-center rounded-xl border-2 border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-400 disabled:opacity-50 transition"
                :disabled="sending"
                title="Attach photo"
                @click="fileInput?.click()"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
            </button>
            <textarea
                v-model="draft"
                rows="1"
                maxlength="5000"
                placeholder="Message…"
                class="flex-1 min-w-0 resize-y max-h-32 rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent min-h-[44px] leading-snug"
                :disabled="sending"
                @keydown.enter.exact.prevent="send"
                @input="onDraftInput"
            />
            <button
                type="submit"
                :disabled="sending || !canSend || profanityHint"
                class="shrink-0 h-10 sm:h-11 min-w-[3.5rem] sm:min-w-[4.5rem] px-3 sm:px-4 inline-flex items-center justify-center rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ sending ? '…' : 'Send' }}
            </button>
        </form>

        <Teleport to="body">
            <div
                v-if="reportTarget"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
                @click.self="reportTarget = null"
            >
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-5" @click.stop>
                    <h3 class="font-bold text-gray-900">Report message</h3>
                    <p class="text-xs text-gray-600 mt-1">Our team reviews reports about harassment, scams, or inappropriate content. <strong>To ensure community safety, reported chats may be reviewed by MedEquip administrators.</strong></p>
                    <div class="mt-4 space-y-3">
                        <label class="block text-xs font-semibold text-gray-700">Reason</label>
                        <select v-model="reportReason" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="spam">Spam</option>
                            <option value="harassment">Harassment</option>
                            <option value="inappropriate">Inappropriate</option>
                            <option value="scam">Scam / fraud</option>
                            <option value="other">Other</option>
                        </select>
                        <textarea
                            v-model="reportDetails"
                            rows="2"
                            maxlength="2000"
                            placeholder="Optional details"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        />
                    </div>
                    <div class="flex gap-2 mt-5">
                        <button type="button" class="flex-1 py-2 rounded-lg border border-gray-300 text-sm font-semibold" @click="reportTarget = null">
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="reporting"
                            class="flex-1 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold disabled:opacity-50"
                            @click="submitReport"
                        >
                            {{ reporting ? '…' : 'Submit' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="rxRejectTarget"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
                @click.self="rxRejectTarget = null"
            >
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-5" @click.stop>
                    <h3 class="font-bold text-gray-900">Reject prescription</h3>
                    <p class="text-xs text-gray-600 mt-1">This will also cancel the associated order.</p>
                    <textarea
                        v-model="rxRejectReason"
                        rows="3"
                        maxlength="500"
                        class="mt-3 w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Reason for the customer…"
                    />
                    <div class="flex gap-2 mt-4">
                        <button type="button" class="flex-1 py-2 rounded-lg border border-gray-300 text-sm font-semibold" @click="rxRejectTarget = null">
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="flex-1 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold disabled:opacity-50"
                            :disabled="rxActionLoading || !rxRejectReason.trim()"
                            @click="submitRxReject"
                        >
                            Reject prescription
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <!-- Fullscreen Image Viewer Modal -->
            <transition
                enter-active-class="transition-opacity ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="imageModalUrl"
                    class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
                    @click="imageModalUrl = null"
                >
                    <button
                        type="button"
                        class="absolute top-4 right-4 text-white hover:text-gray-300 p-2 z-[160]"
                        @click="imageModalUrl = null"
                        aria-label="Close"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <!-- Main Image container -->
                    <div class="relative max-w-full max-h-[90dvh] flex items-center justify-center" @click.stop>
                        <img 
                            :src="imageModalUrl"
                            alt="Attachment Detail"
                            class="max-w-full max-h-full object-contain rounded drop-shadow-xl" 
                        />
                        <!-- Option to open in new tab explicitly if they want to download/zoom natively -->
                        <a 
                            :href="imageModalUrl"
                            target="_blank"
                            class="absolute -bottom-10 inline-flex items-center gap-2 text-white/70 hover:text-white text-sm transition-colors"
                        >
                            Open original
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import { chatProfanityHint } from '@/utils/chatProfanityHint.js';

const emit = defineEmits(['update:presence']);

const props = defineProps({
    orderNumber: { type: String, default: '' },
    fetchUrl: { type: String, required: true },
    postUrl: { type: String, required: true },
    viewerRole: { type: String, default: 'customer' },
    headerTitle: { type: String, default: null },
    showHeader: { type: Boolean, default: false },
    enableReport: { type: Boolean, default: true },
    markReadUrl: { type: String, default: null },
    fillViewport: { type: Boolean, default: false },
    counterpartPresence: { type: String, default: '' },
    backHref: { type: String, default: null },
    backLabel: { type: String, default: 'Back to messages' },
    titleHref: { type: String, default: null },
    /** Pass `null` to hide subtitle; omit for default order-thread copy. */
    headerSubtitle: { type: [String, null], default: undefined },
});

const displayTitle = computed(() => props.headerTitle ?? 'Messages');
const displaySubtitle = computed(() => {
    if (props.headerSubtitle === null) {
        return '';
    }
    if (typeof props.headerSubtitle === 'string' && props.headerSubtitle !== '') {
        return props.headerSubtitle;
    }
    return `Chat with ${props.viewerRole === 'customer' ? 'the seller' : 'the customer'} about this order.`;
});

const showSubtitle = computed(() => displaySubtitle.value.trim() !== '');

const counterpartPresenceLive = ref(props.counterpartPresence || '');

watch(
    () => props.counterpartPresence,
    (v) => {
        if (v != null && v !== '') {
            counterpartPresenceLive.value = v;
        }
    }
);

const presenceLine = computed(() => counterpartPresenceLive.value || '');

const panelRootClass = computed(() =>
    props.fillViewport
        ? 'flex-1 min-h-0 max-h-none h-full rounded-none shadow-none border-0'
        : 'rounded-xl shadow-md border border-gray-100 max-h-[min(36rem,calc(100dvh-12rem))] sm:max-h-[36rem] lg:max-h-[min(44rem,calc(100dvh-6rem))]'
);

const scrollAreaClass = computed(() =>
    props.fillViewport
        ? 'flex-1 min-h-0 max-h-none px-2 sm:px-4 py-2 bg-gradient-to-br from-slate-50 to-gray-100'
        : 'px-3 sm:px-4 py-3 max-h-[min(20rem,calc(100dvh-20rem))] sm:max-h-[20rem] lg:min-h-[16rem] lg:max-h-[min(30rem,calc(100dvh-12rem))] bg-gray-50/50'
);

function mergePresenceFromPayload(data) {
    if (data?.counterpart_presence != null && data.counterpart_presence !== '') {
        counterpartPresenceLive.value = data.counterpart_presence;
        emit('update:presence', data.counterpart_presence);
    }
}

const messages = ref([]);
const draft = ref('');
const pendingFile = ref(null);
const loading = ref(true);
const sending = ref(false);
const scrollRef = ref(null);
const fileInput = ref(null);
const profanityHint = ref(false);
const reportTarget = ref(null);
const reportReason = ref('inappropriate');
const reportDetails = ref('');
const reporting = ref(false);
const rxActionLoading = ref(false);
const rxRejectTarget = ref(null);
const rxRejectReason = ref('');
const imageModalUrl = ref(null);

let pollTimer = null;
let lastId = 0;

const canSend = computed(() => {
    const t = draft.value.trim();
    return t.length > 0 || pendingFile.value !== null;
});

const isShopViewer = computed(() => ['shop', 'distributor', 'staff'].includes(props.viewerRole));

const timelineSegments = computed(() => {
    const list = messages.value;
    const out = [];
    let lastDayKey = null;
    list.forEach((m) => {
        const dayKey = localDateKey(m.created_at);
        if (dayKey && dayKey !== lastDayKey) {
            out.push({
                type: 'divider',
                key: `div-${dayKey}-${m.id}`,
                label: formatDayLabel(m.created_at),
            });
            lastDayKey = dayKey;
        }
        out.push({ type: 'message', key: `m-${m.id}`, message: m });
    });
    return out;
});

function reportUrlFor(messageId) {
    const base = props.postUrl.replace(/\/messages\/?$/, '');
    return `${base}/messages/${messageId}/report`;
}

function avatarInitial(m) {
    const n = m.user?.name || m.shop_label || '?';
    return n.trim().charAt(0).toUpperCase() || '?';
}

function avatarToneClass(m) {
    if (m.is_prescription && m.rx?.event !== 'uploaded' && !m.is_mine) {
        return 'bg-amber-100 text-amber-900 border-amber-200';
    }
    if (m.is_automated) {
        return 'bg-violet-100 text-violet-800 border-violet-200';
    }
    return 'bg-white text-gray-700 border-gray-200';
}

function prescriptionAvatarIcon(m) {
    if (!m.is_prescription || m.is_mine) {
        return '';
    }
    if (m.rx?.event === 'uploaded') {
        return '📋';
    }
    if (m.rx?.event === 'approved') {
        return '✓';
    }
    if (m.rx?.event === 'rejected') {
        return '✕';
    }
    return 'Rx';
}

function headerLabel(m) {
    if (m.is_prescription && m.rx?.event !== 'uploaded' && !m.is_mine) {
        return m.shop_label || 'Shop';
    }
    if (m.is_automated) {
        return m.shop_label || 'Shop';
    }
    if (m.user?.role === 'customer') {
        return m.user.name || 'Customer';
    }
    if (m.user?.role === 'staff') {
        return `${m.user.name} (staff)`;
    }
    return m.user?.name || m.shop_label || 'Shop';
}

function bubbleClass(m) {
    if (m.is_mine) {
        return 'bg-blue-600 text-white rounded-br-md';
    }
    if (m.is_prescription) {
        return 'bg-amber-50 border border-amber-200 text-amber-950 rounded-bl-md';
    }
    if (m.is_automated) {
        return 'bg-violet-50 border border-violet-200 text-violet-950 rounded-bl-md';
    }
    return 'bg-white border border-gray-200 text-gray-900 rounded-bl-md';
}

function avatarLinkTag(m) {
    return m.profile_href ? Link : 'div';
}

function avatarLinkProps(m) {
    if (m.profile_href) {
        return { href: m.profile_href, title: 'View shop profile' };
    }
    return {};
}

function isPrescriptionUploadedBubble(m) {
    return !!(m.is_prescription && m.rx?.event === 'uploaded');
}

function prescriptionUploadedPrimaryLine(m) {
    const s = m.rx?.prescription_status;
    const ord = m.rx?.order_number;
    const suffix = ord ? ` · ${ord}` : '';
    if (s === 'rejected') {
        return `Prescription was not accepted${suffix}`;
    }
    if (s === 'approved') {
        return `Prescription approved${suffix}`;
    }
    if (s === 'pending_review') {
        return `Prescription submitted — awaiting distributor review${suffix}`;
    }
    if (s === 'awaiting_upload') {
        return `Prescription photo uploaded${suffix}`;
    }
    if (s === 'not_required') {
        return `Prescription on file${suffix}`;
    }
    return `Prescription${suffix}`;
}

function canReport(m) {
    return props.enableReport && !m.is_mine && !m.is_automated && !m.is_prescription && m.kind === 'user';
}

async function approvePrescriptionChat(m) {
    const oid = m.rx?.order_id;
    if (!oid) {
        return;
    }
    rxActionLoading.value = true;
    try {
        await window.axios.post(`/owner/orders/${oid}/prescription/approve`, {}, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        await loadInitial();
    } catch (e) {
        alert(e.response?.data?.message || 'Could not approve.');
    } finally {
        rxActionLoading.value = false;
    }
}

function openRxReject(m) {
    rxRejectTarget.value = m;
    rxRejectReason.value = '';
}

async function submitRxReject() {
    const m = rxRejectTarget.value;
    const oid = m?.rx?.order_id;
    if (!oid || !rxRejectReason.value.trim()) {
        return;
    }
    rxActionLoading.value = true;
    try {
        await window.axios.post(`/owner/orders/${oid}/prescription/reject`, {
            reason: rxRejectReason.value.trim(),
        }, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        rxRejectTarget.value = null;
        await loadInitial();
    } catch (e) {
        alert(e.response?.data?.message || (e.response?.data?.errors?.reason?.[0]) || 'Could not reject.');
    } finally {
        rxActionLoading.value = false;
    }
}

function openReport(m) {
    reportTarget.value = m;
    reportReason.value = 'inappropriate';
    reportDetails.value = '';
}

async function submitReport() {
    if (!reportTarget.value) {
        return;
    }
    reporting.value = true;
    try {
        await window.axios.post(reportUrlFor(reportTarget.value.id), {
            reason: reportReason.value,
            details: reportDetails.value || null,
        });
        alert('Thanks — we received your report.');
        reportTarget.value = null;
    } catch (e) {
        alert(e.response?.data?.message || 'Could not submit report.');
    } finally {
        reporting.value = false;
    }
}

function localDateKey(iso) {
    try {
        const d = new Date(iso);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    } catch {
        return '';
    }
}

function formatDayLabel(iso) {
    try {
        const d = new Date(iso);
        const now = new Date();
        const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const msgStart = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const yestStart = new Date(todayStart);
        yestStart.setDate(yestStart.getDate() - 1);
        if (msgStart.getTime() === todayStart.getTime()) {
            return 'Today';
        }
        if (msgStart.getTime() === yestStart.getTime()) {
            return 'Yesterday';
        }
        const opts = { weekday: 'short', month: 'short', day: 'numeric' };
        if (d.getFullYear() !== now.getFullYear()) {
            opts.year = 'numeric';
        }
        return d.toLocaleDateString(undefined, opts);
    } catch {
        return '';
    }
}

function formatClock(iso) {
    try {
        return new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    } catch {
        return '';
    }
}

function outgoingDeliveryStatus(m) {
    if (!m.is_mine || m.kind !== 'user') {
        return '';
    }
    if (m.read_at) {
        return 'Read';
    }
    if (m.delivered) {
        return 'Delivered';
    }
    return 'Sent';
}

function mergeReadState(readState) {
    if (!readState || typeof readState !== 'object') {
        return;
    }
    for (const [idStr, iso] of Object.entries(readState)) {
        const id = Number(idStr);
        const idx = messages.value.findIndex((m) => m.id === id);
        if (idx !== -1) {
            messages.value[idx] = { ...messages.value[idx], read_at: iso, delivered: true };
        }
    }
}

async function postMarkRead() {
    if (!props.markReadUrl) {
        return;
    }
    try {
        await window.axios.post(props.markReadUrl);
    } catch {
        /* silent */
    }
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
        postMarkRead();
    }
}

function onDraftInput() {
    profanityHint.value = chatProfanityHint(draft.value);
}

function onFile(e) {
    const f = e.target.files?.[0];
    pendingFile.value = f || null;
    e.target.value = '';
}

function scrollToBottom() {
    nextTick(() => {
        const el = scrollRef.value;
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    });
}

async function loadInitial() {
    loading.value = true;
    try {
        const { data } = await window.axios.get(props.fetchUrl, { params: { after_id: 0 } });
        messages.value = data.messages || [];
        mergeReadState(data.read_state);
        mergePresenceFromPayload(data);
        lastId = messages.value.length ? messages.value[messages.value.length - 1].id : 0;
        scrollToBottom();
        await postMarkRead();
    } catch (e) {
        console.error('[OrderChatPanel] load failed', e);
    } finally {
        loading.value = false;
    }
}

async function pollNew() {
    if (document.visibilityState !== 'visible') {
        return;
    }
    try {
        const { data } = await window.axios.get(props.fetchUrl, {
            params: { after_id: lastId },
        });
        mergeReadState(data.read_state);
        mergePresenceFromPayload(data);
        const incoming = data.messages || [];
        if (incoming.length) {
            messages.value = [...messages.value, ...incoming];
            lastId = incoming[incoming.length - 1].id;
            scrollToBottom();
            postMarkRead();
        }
    } catch {
        /* silent */
    }
}

async function send() {
    if (!canSend.value || sending.value || profanityHint.value) {
        return;
    }
    sending.value = true;
    try {
        const fd = new FormData();
        const t = draft.value.trim();
        if (t) {
            fd.append('body', t);
        }
        if (pendingFile.value) {
            fd.append('image', pendingFile.value);
        }
        const { data } = await window.axios.post(props.postUrl, fd);
        if (data.message) {
            messages.value = [...messages.value, data.message];
            lastId = data.message.id;
            draft.value = '';
            pendingFile.value = null;
            profanityHint.value = false;
            scrollToBottom();
            if (data.content_censored) {
                 messages.value = [...messages.value, {
                      id: 'warning-' + Date.now(),
                      kind: 'automated',
                      body: 'System Notification: Your last message was moderated. Repeated use of profane language is against our terms and may result in an account suspension or permanent ban.',
                      user_id: null,
                      created_at: new Date().toISOString(),
                      is_automated: true,
                 }];
                 scrollToBottom();
            }
        }
    } catch (e) {
        const msg = e.response?.data?.message
            || (e.response?.data?.errors?.body && e.response.data.errors.body[0])
            || 'Could not send message.';
        alert(msg);
    } finally {
        sending.value = false;
    }
}

onMounted(() => {
    loadInitial();
    pollTimer = setInterval(pollNew, 12000);
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
    document.removeEventListener('visibilitychange', onVisibilityChange);
});

watch(
    () => props.fetchUrl,
    () => {
        messages.value = [];
        lastId = 0;
        loadInitial();
    }
);
</script>
