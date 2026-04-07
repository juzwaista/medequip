<template>
    <!-- Terms & Conditions Modal -->
    <div v-if="show" class="fixed inset-0 z-[100] bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Terms & Conditions</h2>
                    <p class="text-xs text-gray-500 mt-0.5">MedEquip Platform · Effective March 29, 2026</p>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="overflow-y-auto flex-1 p-6 text-sm text-gray-700 space-y-5 leading-relaxed">

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-1">Important Notice</p>
                    <p class="text-xs text-blue-700">These Terms and Conditions were updated on <strong>March 29, 2026</strong>. All users (including existing accounts) must accept these updated terms to continue using MedEquip.</p>
                </div>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">1. Acceptance of Terms</h3>
                    <p class="text-xs text-gray-600">By creating an account or continuing to use MedEquip, you agree to be bound by these Terms and Conditions. If you do not agree, you must not use the platform. These terms apply to all users including Customers, Distributors, and Couriers.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">2. Platform Description</h3>
                    <p class="text-xs text-gray-600">MedEquip is a B2B/B2C marketplace facilitating the sale of medical equipment and consumables between verified distributors and healthcare providers or individual customers within the Cavite region, Philippines.</p>
                </section>

                <!-- CUSTOMER TERMS -->
                <div v-if="!role || role === 'customer'" class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-green-800 uppercase tracking-wider mb-3">🛒 Customer-Specific Terms</p>
                    <div class="space-y-2 text-xs text-green-800">
                        <p><strong>3.1 Eligibility.</strong> Customers must be at least 18 years old. Healthcare professionals ordering prescription-only medical devices must upload a valid prescription or license as required.</p>
                        <p><strong>3.2 Ordering.</strong> Orders are confirmed upon payment. You agree that prices and availability are subject to change without prior notice.</p>
                        <p><strong>3.3 Delivery.</strong> MedEquip uses third-party couriers. Estimated delivery times are not guaranteed. You are responsible for providing an accurate delivery address within the supported area (Cavite).</p>
                        <p><strong>3.4 Returns & Refunds.</strong> Medical equipment and consumables are generally not returnable once delivered unless defective. Disputes must be raised within 48 hours of delivery.</p>
                        <p><strong>3.5 Wallet & Payments.</strong> Wallet top-ups are non-refundable except under exceptional circumstances approved by MedEquip Admin. COD payments must be made in full at the time of delivery.</p>
                        <p><strong>3.6 Prohibited Use.</strong> You may not resell products purchased through MedEquip without a valid distributor account and approval from the platform.</p>
                    </div>
                </div>

                <!-- DISTRIBUTOR TERMS -->
                <div v-if="!role || role === 'distributor'" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-3">🏪 Distributor-Specific Terms</p>
                    <div class="space-y-2 text-xs text-blue-800">
                        <p><strong>4.1 Verification Requirement.</strong> Distributors must complete the onboarding verification process and submit valid business documents (DTI/SEC, Business Permit, BIR Form 2303, FDA LTO, PRC ID, and a valid government ID). Operating without verification is prohibited.</p>
                        <p><strong>4.2 New Requirements (March 2026 Update).</strong> As part of platform updates, all existing distributors must now ensure their accounts comply with updated verification requirements. Distributors who have completed onboarding but have not accepted these updated terms must do so to retain full platform access.</p>
                        <p><strong>4.3 Product Listings.</strong> You are solely responsible for the accuracy of product information including descriptions, pricing, and stock levels. Listing counterfeit, expired, or unregistered medical products is strictly prohibited.</p>
                        <p><strong>4.4 FDA & Regulatory Compliance.</strong> All products must comply with applicable Philippine FDA regulations. Distributors must maintain valid LTO and must not sell without valid licenses.</p>
                        <p><strong>4.5 Commission & Platform Fees.</strong> MedEquip charges a platform service fee per completed order as defined in your distributor agreement. Fees are deducted from payouts when funds are released to you.</p>
                        <p><strong>4.6 Payments & Payouts.</strong> Buyer payments for online orders are held by the platform until the order is delivered and confirmed. MedEquip reserves the right to withhold payouts if fraud or a dispute is detected.</p>
                        <p><strong>4.7 Account Suspension.</strong> Repeated order cancellations, customer complaints, or regulatory violations may result in temporary suspension or permanent termination of your distributor account.</p>
                        <p><strong>4.8 Staff Accounts.</strong> You may create staff accounts under your distributor profile. You are responsible for all actions taken by staff accounts.</p>
                    </div>
                </div>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">5. Privacy & Data</h3>
                    <p class="text-xs text-gray-600">MedEquip collects and processes personal data in accordance with the Philippine Data Privacy Act of 2012 (RA 10173). Your data is used solely for platform operations and will not be sold to third parties. By using MedEquip you consent to our Privacy Policy.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">6. Prohibited Activities</h3>
                    <p class="text-xs text-gray-600">Users must not: (a) submit false or fraudulent information; (b) manipulate listings or reviews; (c) circumvent platform fees; (d) engage in any activity that violates Philippine law; (e) attempt to hack or disrupt platform services.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">7. Limitation of Liability</h3>
                    <p class="text-xs text-gray-600">MedEquip is a marketplace facilitator and is not liable for the quality, safety, or fitness of products sold by distributors. To the maximum extent permitted by law, MedEquip's liability is limited to the amount of fees paid by you in the 30 days preceding the claim.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">8. Governing Law</h3>
                    <p class="text-xs text-gray-600">These terms are governed by the laws of the Republic of the Philippines. Any disputes shall be subject to the exclusive jurisdiction of the courts in Cavite City, Philippines.</p>
                </section>

                <section>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm">9. Changes to Terms</h3>
                    <p class="text-xs text-gray-600">MedEquip reserves the right to modify these terms at any time. Significant changes will be notified via email or an in-app prompt requiring re-acceptance. Continued use after notification constitutes acceptance of the revised terms.</p>
                </section>

                <p class="text-xs text-gray-400 text-center pt-2">Last updated: March 29, 2026 · Version 2026-03-29</p>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                <button @click="$emit('close')"
                    class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    role: { type: String, default: null }, // 'customer' | 'distributor' | null (shows both)
});
defineEmits(['close']);
</script>
