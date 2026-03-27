<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-gray-600 mt-2">{{ order.order_number }}</p>
                </div>
                <Link href="/owner/orders" class="text-blue-600 hover:text-blue-700 font-medium">
                    ← Back to Orders
                </Link>
            </div>

            <!-- Error Messages -->
            <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-1">Unable to update order status</h3>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Info Card -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Order Information</h2>
                                <p class="text-sm text-gray-600 mt-1">Placed on {{ formatDate(order.created_at) }}</p>
                            </div>
                            <span 
                                :class="{
                                    'bg-yellow-100 text-yellow-800': order.status === 'pending',
                                    'bg-blue-100 text-blue-800': order.status === 'approved',
                                    'bg-purple-100 text-purple-800': order.status === 'packed',
                                    'bg-indigo-100 text-indigo-800': order.status === 'shipped',
                                    'bg-green-100 text-green-800': order.status === 'delivered',
                                    'bg-red-100 text-red-800': order.status === 'rejected' || order.status === 'cancelled',
                                }"
                                class="px-4 py-2 rounded-full text-sm font-semibold capitalize"
                            >
                                {{ order.status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Customer</p>
                                <p class="font-semibold text-gray-900">{{ order.customer.name }}</p>
                                <p class="text-sm text-gray-600">{{ order.customer.email }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Contact Number</p>
                                <p class="font-semibold text-gray-900">{{ order.contact_number }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-600">Delivery Address</p>
                                <p class="font-semibold text-gray-900">{{ order.delivery_address }}</p>
                            </div>
                            <div v-if="order.notes" class="col-span-2">
                                <p class="text-sm text-gray-600">Customer Notes</p>
                                <p class="text-sm text-gray-700">{{ order.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription (Rx) review -->
                    <div
                        v-if="order.prescription_status === 'awaiting_upload'"
                        class="bg-amber-50 border border-amber-200 rounded-xl p-6"
                    >
                        <h2 class="text-lg font-bold text-amber-900 mb-1">Prescription pending</h2>
                        <p class="text-sm text-amber-800">The customer has not uploaded a prescription photo yet.</p>
                    </div>
                    <div
                        v-else-if="order.prescription_status === 'pending_review' && order.prescription_image_path"
                        class="bg-white rounded-xl shadow-md p-6 border border-indigo-200"
                    >
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Prescription review</h2>
                        <p class="text-sm text-gray-600 mb-4">
                            Approve if the prescription is valid. Rejecting cancels the order and releases reserved stock.
                        </p>
                        <a
                            :href="`/storage/${order.prescription_image_path}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-block mb-4"
                        >
                            <img
                                :src="`/storage/${order.prescription_image_path}`"
                                alt="Prescription upload"
                                class="max-w-full sm:max-w-md rounded-xl border border-gray-200 shadow-sm"
                            />
                            <span class="text-sm text-blue-600 font-medium mt-2 block">Open full size</span>
                        </a>
                        <div class="flex flex-col lg:flex-row gap-4 lg:items-start">
                            <button
                                type="button"
                                @click="approvePrescription"
                                :disabled="rxProcessing"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-50"
                            >
                                Approve prescription
                            </button>
                            <form class="flex-1 space-y-2 max-w-lg" @submit.prevent="rejectPrescription">
                                <label class="block text-sm font-semibold text-gray-700">Reject with reason</label>
                                <textarea
                                    v-model="rejectReason"
                                    required
                                    rows="2"
                                    placeholder="e.g. Illegible, expired, wrong patient name…"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                />
                                <button
                                    type="submit"
                                    :disabled="rxProcessing"
                                    class="px-5 py-2.5 rounded-xl border-2 border-red-300 text-red-700 font-semibold hover:bg-red-50 disabled:opacity-50"
                                >
                                    Reject prescription
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="item in order.items" 
                                :key="item.id"
                                class="flex gap-4 pb-4 border-b last:border-0"
                            >
                                <div class="w-20 h-20 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ item.product.name }}</h3>
                                    <p v-if="item.product_variation" class="text-sm text-blue-700 font-medium mt-0.5">
                                        {{ item.product_variation.display_label || `${item.product_variation.option_name}: ${item.product_variation.option_value}` }}
                                    </p>
                                    <p class="text-sm text-gray-600">{{ item.product.brand || 'Generic' }}</p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Branch: {{ item.inventory?.branch?.location ?? 'Main Warehouse' }}
                                    </p>
                                    <div class="flex items-center gap-4 mt-2">
                                        <span class="text-sm text-gray-600">Qty: {{ item.quantity }}</span>
                                        <span class="text-sm text-gray-600">₱{{ Number(item.unit_price).toLocaleString() }} each</span>
                                        <span v-if="item.is_wholesale" class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">
                                            Wholesale
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900">₱{{ Number(item.total_price).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Items Subtotal</span>
                                    <span class="font-semibold text-gray-900">₱{{ Number(orderSubtotal).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Shipping Fee</span>
                                    <span class="font-semibold text-gray-900">₱{{ Number(orderShippingFee).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t">
                                    <span class="text-lg font-semibold text-gray-900">Total Amount</span>
                                    <span class="text-2xl font-bold text-blue-600">₱{{ Number(orderGrandTotal).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Info -->
                    <div v-if="order.invoice" class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Invoice</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Invoice Number</p>
                                <p class="font-semibold text-gray-900">{{ order.invoice.invoice_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Payment Status</p>
                                <span
                                    :class="{
                                        'bg-amber-100 text-amber-800': paymentSettlement.state === 'pending_release' || paymentSettlement.state === 'pending_verification',
                                        'bg-green-100 text-green-800': paymentSettlement.state === 'released',
                                        'bg-red-100 text-red-800': paymentSettlement.state === 'refunded',
                                        'bg-gray-100 text-gray-800': paymentSettlement.state === 'unpaid',
                                    }"
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                >
                                    {{ paymentSettlement.label }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Invoice Record</p>
                                <span
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': order.invoice.status === 'unpaid',
                                        'bg-orange-100 text-orange-800': order.invoice.status === 'partial',
                                        'bg-green-100 text-green-800': order.invoice.status === 'paid',
                                        'bg-red-100 text-red-800': order.invoice.status === 'overdue',
                                    }"
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                >
                                    {{ order.invoice.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Update Status -->
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h3 class="font-bold text-gray-900 mb-4">Update Status</h3>
                        <form @submit.prevent="updateStatus">
                            <select 
                                v-model="statusForm.status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-3"
                            >
                                <option :value="order.status" disabled>{{ order.status }} (current)</option>
                                <option
                                    v-for="next in validNextStatuses"
                                    :key="next"
                                    :value="next"
                                >
                                    {{ next.charAt(0).toUpperCase() + next.slice(1) }}
                                </option>
                            </select>
                            <button 
                                type="submit"
                                :disabled="statusForm.status === order.status || updating"
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ updating ? 'Updating...' : 'Update Status' }}
                            </button>
                        </form>
                    </div>

                    <!-- Delivery Info -->
                    <div v-if="order.delivery" class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h3 class="font-bold text-gray-900 mb-4">Delivery</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Tracking Number</p>
                                <p class="font-semibold text-gray-900">{{ order.delivery.tracking_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Carrier</p>
                                <p class="font-semibold text-gray-900">{{ order.delivery.carrier ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <span class="inline-block bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-semibold capitalize">
                                    {{ order.delivery.status.replace('_', ' ') }}
                                </span>
                            </div>
                            <div v-if="order.delivery.proof_of_delivery_path" class="pt-3 border-t mt-2">
                                <p class="text-sm text-gray-600 mb-2">Proof of Delivery</p>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-2 inline-block">
                                    <img 
                                        :src="`/storage/${order.delivery.proof_of_delivery_path}`" 
                                        alt="Proof of Delivery Photo" 
                                        class="max-w-[200px] rounded shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-md p-6 text-white">
                        <h3 class="font-bold mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <button @click="showWaybill = true" class="w-full bg-white/20 hover:bg-white/30 transition px-4 py-2 rounded-lg font-medium text-left flex justify-between items-center">
                                Print Waybill
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </button>
                            <button class="w-full bg-white/20 hover:bg-white/30 transition px-4 py-2 rounded-lg font-medium text-left">
                                Contact Customer
                            </button>
                            <Link 
                                href="/owner/inventory"
                                class="block w-full bg-white/20 hover:bg-white/30 transition px-4 py-2 rounded-lg font-medium text-left"
                            >
                                Check Inventory
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waybill Modal -->
        <Teleport to="body">
            <div v-if="showWaybill" class="fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
                    <div class="px-4 py-3 border-b flex justify-between items-center bg-gray-50 print:hidden">
                        <h3 class="font-bold text-gray-900">Delivery Waybill</h3>
                        <div class="flex gap-2">
                            <button @click="printWaybill" class="text-blue-600 hover:text-blue-800 font-bold px-3 py-1 rounded-lg bg-blue-50">
                                Print
                            </button>
                            <button @click="showWaybill = false" class="text-gray-400 hover:text-gray-600 p-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div id="waybill-content" class="p-8 bg-white print:p-0">
                        <div class="text-center mb-6 border-b-2 border-dashed border-gray-300 pb-6">
                            <h2 class="text-2xl font-black tracking-tighter uppercase mb-1">MedEquip Express</h2>
                            <p class="font-mono text-sm text-gray-500">{{ order.order_number }}</p>
                        </div>
                        
                        <div class="flex justify-center mb-8">
                            <div class="p-2 border-4 border-black rounded-xl">
                                <qrcode-vue :value="order.order_number" :size="200" level="H" />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Recipient</p>
                                <p class="text-lg font-bold text-gray-900 leading-tight">{{ order.customer.name }}</p>
                                <p class="text-gray-900">{{ order.contact_number }}</p>
                            </div>
                            
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Delivery Address</p>
                                <p class="text-gray-900 font-medium leading-relaxed">{{ order.delivery_address }}</p>
                            </div>

                            <div v-if="order.notes" class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Customer Notes</p>
                                <p class="text-sm font-bold text-gray-900 italic">"{{ order.notes }}"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </OwnerLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    order: Object,
    paymentSettlement: {
        type: Object,
        default: () => ({ state: 'unpaid', label: 'Unpaid' }),
    },
});

const page = usePage();

// Valid status transitions (mirrors server-side state machine)
const transitionMap = {
    pending:   ['approved', 'rejected', 'cancelled'],
    approved:  ['packed', 'cancelled'],
    packed:    [],
    shipped:   [],
    delivered: [],
    cancelled: [],
    rejected:  [],
};

const validNextStatuses = computed(() => transitionMap[props.order.status] ?? []);

const statusForm = reactive({
    status: validNextStatuses.value[0] ?? props.order.status,
});

const updating = ref(false);
const showWaybill = ref(false);
const rxProcessing = ref(false);
const rejectReason = ref('');

const approvePrescription = () => {
    if (!confirm('Approve this prescription and allow the customer to pay for this order?')) return;
    rxProcessing.value = true;
    router.post(`/owner/orders/${props.order.id}/prescription/approve`, {}, {
        preserveScroll: true,
        onFinish: () => { rxProcessing.value = false; },
    });
};

const rejectPrescription = () => {
    if (!confirm('Reject this prescription? The order will be cancelled and stock reservations released.')) return;
    rxProcessing.value = true;
    router.post(`/owner/orders/${props.order.id}/prescription/reject`, { reason: rejectReason.value }, {
        preserveScroll: true,
        onFinish: () => { rxProcessing.value = false; },
    });
};
const orderSubtotal = computed(() => Number(props.order?.subtotal || 0));
const orderShippingFee = computed(() => Number(props.order?.shipping_fee || 0));
const orderGrandTotal = computed(() => {
    const total = Number(props.order?.total_amount || 0);
    if (total > 0) return total;
    return orderSubtotal.value + orderShippingFee.value;
});

const printWaybill = () => {
    window.print();
};

onMounted(() => {
    console.log('[OwnerOrderShow] Component mounted', {
        order_id: props.order.id,
        order_number: props.order.order_number,
        current_status: props.order.status,
        items_count: props.order.items?.length || 0
    });

    // Check for errors from previous request
    if (page.props.errors && Object.keys(page.props.errors).length > 0) {
        console.error('[OwnerOrderShow] Errors detected on page load', page.props.errors);
    }
});

const updateStatus = () => {
    const oldStatus = statusForm.status;
    const newStatus = statusForm.status;

    console.log('[OwnerOrderShow] Status update initiated', {
        order_id: props.order.id,
        old_status: props.order.status,
        new_status: newStatus
    });

    // Confirm destructive actions
    if (['rejected', 'cancelled'].includes(newStatus)) {
        if (!confirm(`Are you sure you want to ${newStatus} this order? This will release reserved inventory.`)) {
            console.log('[OwnerOrderShow] Status update cancelled by user');
            return;
        }
    }

    updating.value = true;
    
    router.patch(`/owner/orders/${props.order.id}/status`, statusForm, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('[OwnerOrderShow] Status update successful', {
                order_id: props.order.id,
                new_status: newStatus
            });
        },
        onError: (errors) => {
            console.error('[OwnerOrderShow] Status update failed', {
                order_id: props.order.id,
                errors: errors
            });
        },
        onFinish: () => {
            updating.value = false;
            console.log('[OwnerOrderShow] Status update request completed');
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
