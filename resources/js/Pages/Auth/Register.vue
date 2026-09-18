<template>
    <Head title="Create Account · MedEquip" />
    <TermsModal :show="showTermsModal" :role="form.role" @close="showTermsModal = false" />

    <div class="min-h-screen min-h-[100dvh] flex min-w-0 overflow-x-hidden">
        <!-- Left: Brand Panel (Fixed) -->
        <div class="hidden lg:flex lg:w-[40%] xl:w-[42%] bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 flex-col justify-between p-10 relative overflow-hidden flex-shrink-0 sticky top-0 h-screen">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-72 h-72 bg-cyan-400/20 rounded-full blur-2xl"></div>
            </div>
            <div class="relative z-10">
                <a href="/products" class="inline-flex items-center gap-3">
                    <img :src="'/images/logo.png'" alt="MedEquip" class="h-8 sm:h-10 md:h-14 lg:h-20 xl:h-24 w-auto brightness-0 invert transition-all duration-300" />
                </a>
            </div>
            <div class="relative z-10 space-y-5">
                <h1 class="text-3xl xl:text-4xl font-black text-white leading-tight">
                    Stock your clinic with ease.<br>
                    <span class="text-cyan-300">Get started today.</span>
                </h1>
                <p class="text-blue-100 leading-relaxed">
                    Browse and order trusted medical equipment and essential supplies from verified local suppliers—fast, simple, and reliable.
                </p>
                <ul class="space-y-3 pt-2">
                    <li v-for="item in ['Verified local distributors', 'Secure payments', 'Convenient ordering', 'Fast, direct delivery']" :key="item" class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-emerald-400/30 border border-emerald-400/50 flex items-center justify-center">
                            <svg class="h-3 w-3 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                        {{ item }}
                    </li>
                </ul>
            </div>
            <p class="relative z-10 text-blue-200 text-xs">&copy; 2026 MedEquip Platform. Cavite, Philippines.</p>
        </div>

        <!-- Right: Registration Form (Scrollable) -->
        <div class="flex-1 overflow-y-auto bg-slate-50 flex justify-center py-10 px-4 sm:px-8">
            <div class="w-full max-w-xl">
                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-6">
                    <a href="/products"><img :src="'/images/logo.png'" alt="MedEquip" class="h-10 w-auto mx-auto" /></a>
                </div>

                <!-- Registration Card -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

                    <!-- Progress Bar -->
                    <div class="px-8 pt-8 pb-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span v-for="n in 3" :key="n" class="text-[10px] font-bold uppercase tracking-wider flex-1 text-center transition-colors duration-300"
                                :class="n <= currentStep ? 'text-blue-600' : 'text-gray-300'">
                                <span v-if="n === 1">Who you are</span>
                                <span v-if="n === 2">Credentials</span>
                                <span v-if="n === 3">Location</span>
                            </span>
                        </div>
                        <div class="flex gap-1.5">
                            <div v-for="n in 3" :key="n" class="h-1.5 flex-1 rounded-full transition-all duration-500"
                                :class="n <= currentStep ? 'bg-blue-500' : 'bg-gray-100'"></div>
                        </div>
                    </div>

                    <div class="px-8 pb-8">
                        <!-- Draft Saved Badge -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-black text-gray-900">Get started</h2>
                                <p class="text-gray-400 text-sm mt-0.5">Step {{ currentStep }} of 3</p>
                            </div>
                            <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 scale-90" enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-90">
                                <div v-if="lastSaved" class="flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-black border border-emerald-100 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Saved {{ lastSaved }}
                                </div>
                            </transition>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">

                            <!-- ─── STEP 1: Who are you? ───────────────────────────────── -->
                            <div v-show="currentStep === 1" class="space-y-5">
                                <!-- Account Type -->
                                <div class="space-y-3">
                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Account Type</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label :class="form.role === 'customer' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50'"
                                            class="flex items-start gap-3 cursor-pointer p-4 rounded-2xl border-2 transition-all">
                                            <input type="radio" v-model="form.role" value="customer" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                            <div>
                                                <span class="font-bold text-gray-900 text-sm block">Customer</span>
                                                <p class="text-xs text-gray-500 mt-0.5">Buy medical supplies</p>
                                            </div>
                                        </label>
                                        <label :class="form.role === 'distributor' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50'"
                                            class="flex items-start gap-3 cursor-pointer p-4 rounded-2xl border-2 transition-all">
                                            <input type="radio" v-model="form.role" value="distributor" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                            <div>
                                                <span class="font-bold text-gray-900 text-sm block">Distributor</span>
                                                <p class="text-xs text-gray-500 mt-0.5">Sell & manage inventory</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Customer Profile Type -->
                                <div v-if="form.role === 'customer'" class="space-y-3">
                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Buying as</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label :class="!form.is_business ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50'"
                                            class="flex items-start gap-3 cursor-pointer p-4 rounded-2xl border-2 transition-all">
                                            <input type="radio" v-model="form.is_business" :value="false" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                            <div>
                                                <span class="font-bold text-gray-900 text-sm block">Personal</span>
                                                <p class="text-xs text-gray-500 mt-0.5">Buying for myself</p>
                                            </div>
                                        </label>
                                        <label :class="form.is_business ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50'"
                                            class="flex items-start gap-3 cursor-pointer p-4 rounded-2xl border-2 transition-all">
                                            <input type="radio" v-model="form.is_business" :value="true" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                            <div>
                                                <span class="font-bold text-gray-900 text-sm block">Business</span>
                                                <p class="text-xs text-gray-500 mt-0.5">Clinic, hospital, etc.</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Business Details (if business customer or distributor) -->
                                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                                    <div v-if="needsBusinessDetails" class="space-y-4">
                                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Business Details</h3>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Company Name <span class="text-red-500">*</span></label>
                                            <input type="text" v-model="form.company_name" placeholder="Your Company Name"
                                                class="w-full border-2 rounded-xl px-4 py-3 text-sm focus:outline-none transition-all placeholder-gray-300"
                                                :class="step1Errors.company_name ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                            <p v-if="step1Errors.company_name" class="text-red-500 text-xs mt-1.5">{{ step1Errors.company_name }}</p>
                                            <p v-if="form.errors.company_name" class="text-red-600 text-xs mt-1.5">{{ form.errors.company_name }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div v-if="form.role === 'customer'">
                                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Business Type <span class="text-red-500">*</span></label>
                                                <select v-model="form.business_type"
                                                    class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none transition-all bg-white text-sm"
                                                    :class="step1Errors.business_type ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                                    <option value="">Select type</option>
                                                    <option value="Hospital">Hospital</option>
                                                    <option value="Clinic">Clinic</option>
                                                    <option value="Pharmacy">Pharmacy</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <p v-if="step1Errors.business_type" class="text-red-500 text-xs mt-1.5">{{ step1Errors.business_type }}</p>
                                                <p v-if="form.errors.business_type" class="text-red-600 text-xs mt-1.5">{{ form.errors.business_type }}</p>
                                            </div>
                                            <div :class="form.role === 'distributor' ? 'col-span-2' : ''">
                                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">TIN Number <span class="text-gray-400 font-normal">(Optional)</span></label>
                                                <input type="text" v-model="form.tin_number" placeholder="123-456-789-000"
                                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-gray-300">
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>

                            <!-- ─── STEP 2: Credentials ────────────────────────────────── -->
                            <div v-show="currentStep === 2" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Username <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.username" autocomplete="username" placeholder="e.g. cavite_clinic"
                                        class="w-full border-2 rounded-xl px-4 py-3 text-sm focus:outline-none transition-all placeholder-gray-300"
                                        :class="usernameHint.state === 'taken' || usernameHint.state === 'invalid' || step2Errors.username ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : usernameHint.state === 'available' ? 'border-emerald-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                    <p class="text-xs text-gray-500 mt-1">4–20 characters: letters, numbers, underscore (stored lowercase).</p>
                                    <p v-if="usernameHint.state === 'loading'" class="text-xs text-gray-400 mt-1 flex items-center gap-1.5">
                                        <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Checking availability…
                                    </p>
                                    <p v-else-if="usernameHint.state !== 'idle'" class="text-xs mt-1.5 font-medium flex items-center gap-1.5"
                                        :class="usernameHint.state === 'available' ? 'text-emerald-600' : 'text-red-600'">
                                        <span class="inline-flex h-4 w-4 rounded-full items-center justify-center text-[10px]"
                                            :class="usernameHint.state === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                            {{ usernameHint.state === 'available' ? '✓' : '×' }}
                                        </span>
                                        {{ usernameHint.message }}
                                    </p>
                                    <p v-if="step2Errors.username" class="text-red-500 text-xs mt-1.5">{{ step2Errors.username }}</p>
                                    <p v-if="form.errors.username" class="text-red-600 text-xs mt-1.5">{{ form.errors.username }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                    <input type="email" v-model="form.email" placeholder="you@example.com"
                                        class="w-full border-2 rounded-xl px-4 py-3 text-sm focus:outline-none transition-all placeholder-gray-300"
                                        :class="step2Errors.email ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                    <p v-if="step2Errors.email" class="text-red-500 text-xs mt-1.5">{{ step2Errors.email }}</p>
                                    <p v-if="form.errors.email" class="text-red-600 text-xs mt-1.5">{{ form.errors.email }}</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold mb-1.5 text-gray-700">Password <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="••••••••"
                                                class="w-full border-2 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none transition-all placeholder-gray-300"
                                                :class="step2Errors.password ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                        <p v-if="step2Errors.password" class="text-red-500 text-xs mt-1.5">{{ step2Errors.password }}</p>
                                        <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
                                        <ul v-if="form.password.length > 0" class="mt-2 space-y-0.5 text-xs text-gray-500">
                                            <li :class="pwdRules.len ? 'text-emerald-600' : ''">{{ pwdRules.len ? '✓' : '○' }} 10+ characters</li>
                                            <li :class="pwdRules.upper ? 'text-emerald-600' : ''">{{ pwdRules.upper ? '✓' : '○' }} Uppercase letter</li>
                                            <li :class="pwdRules.lower ? 'text-emerald-600' : ''">{{ pwdRules.lower ? '✓' : '○' }} Lowercase letter</li>
                                            <li :class="pwdRules.num ? 'text-emerald-600' : ''">{{ pwdRules.num ? '✓' : '○' }} Number</li>
                                            <li :class="pwdRules.sym ? 'text-emerald-600' : ''">{{ pwdRules.sym ? '✓' : '○' }} Symbol (e.g. !@#$)</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold mb-1.5 text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                        <input :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" placeholder="••••••••"
                                            class="w-full border-2 rounded-xl px-4 py-3 text-sm focus:outline-none transition-all placeholder-gray-300"
                                            :class="step2Errors.password_confirmation ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                        <p v-if="step2Errors.password_confirmation" class="text-red-500 text-xs mt-1.5">{{ step2Errors.password_confirmation }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Contact Number <span class="text-red-500">*</span></label>
                                    <input v-model="form.contact_number" @input="sanitizeContactNumber" @blur="touchedContact = true" type="tel" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" placeholder="09XXXXXXXXX"
                                        class="w-full border-2 rounded-xl px-4 py-3 text-sm focus:outline-none transition-all placeholder-gray-300"
                                        :class="contactError ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                    <p v-if="contactError" class="text-red-500 text-xs mt-1.5">{{ contactError }}</p>
                                    <p v-if="form.errors.contact_number" class="text-red-600 text-xs mt-1.5">{{ form.errors.contact_number }}</p>
                                </div>
                            </div>

                            <!-- ─── STEP 3: Location & Finalize ────────────────────────── -->
                            <div v-show="currentStep === 3" class="space-y-4">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Delivery Address</h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">City / Municipality <span class="text-red-500">*</span></label>
                                        <select v-model="selectedCity" @change="onCityChange"
                                            class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none transition-all bg-white text-sm"
                                            :class="step3Errors.city ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                            <option value="">Select city</option>
                                            <option v-for="(data, city) in cities" :key="city" :value="city">{{ city }}</option>
                                        </select>
                                        <p v-if="step3Errors.city" class="text-red-500 text-xs mt-1.5">{{ step3Errors.city }}</p>
                                        <p v-if="form.errors.city" class="text-red-600 text-xs mt-1.5">{{ form.errors.city }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barangay <span class="text-red-500">*</span></label>
                                        <select v-if="availableBarangays.length > 0" v-model="selectedBarangay"
                                            class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none transition-all bg-white text-sm"
                                            :class="step3Errors.barangay ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'">
                                            <option value="">Select barangay</option>
                                            <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">{{ brgy }}</option>
                                            <option value="other">Other (type manually)</option>
                                        </select>
                                        <input v-else v-model="manualBarangay" type="text" placeholder="Enter barangay name"
                                            class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none transition-all text-sm placeholder-gray-300"
                                            :class="step3Errors.barangay ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'" />
                                        <p v-if="step3Errors.barangay" class="text-red-500 text-xs mt-1.5">{{ step3Errors.barangay }}</p>
                                        <p v-if="form.errors.barangay" class="text-red-600 text-xs mt-1.5">{{ form.errors.barangay }}</p>
                                    </div>
                                </div>

                                <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barangay Name <span class="text-red-500">*</span></label>
                                    <input v-model="manualBarangay" type="text" placeholder="Type your barangay name"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-sm placeholder-gray-300" />
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Street Address <span class="text-red-500">*</span></label>
                                    <input v-model="form.address_line" type="text" placeholder="e.g., Blk 5 Lot 10 Sampaguita St."
                                        class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none transition-all text-sm placeholder-gray-300"
                                        :class="step3Errors.address_line ? 'border-red-400 focus:border-red-400 focus:ring-4 focus:ring-red-400/10' : 'border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'" />
                                    <p v-if="step3Errors.address_line" class="text-red-500 text-xs mt-1.5">{{ step3Errors.address_line }}</p>
                                    <p v-if="form.errors.address_line" class="text-red-600 text-xs mt-1.5">{{ form.errors.address_line }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Zip Code</label>
                                    <input v-model="zipCode" type="text" readonly placeholder="Auto-filled from city" class="w-full px-4 py-3 border-2 border-gray-100 rounded-xl bg-gray-50 text-gray-500 text-sm" />
                                </div>

                                <!-- Map Pin -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center gap-2">
                                        Pin Exact Location <span class="text-red-500">*</span>
                                        <span class="text-xs font-normal text-gray-400 italic">(Required for delivery)</span>
                                    </label>
                                    <MapPicker
                                        v-model:lat="form.latitude"
                                        v-model:lng="form.longitude"
                                        :geocodeQuery="geocodeQuery"
                                        height="200px"
                                        @update:address="onMapAddressPicked"
                                    />
                                    <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                                        <p v-if="detectedLocation" class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                            {{ detectedLocation }}
                                        </p>
                                    </transition>
                                    <p v-if="step3Errors.location" class="text-red-500 text-xs mt-1.5">{{ step3Errors.location }}</p>
                                    <p v-if="form.errors.latitude" class="text-red-600 text-xs mt-1.5">{{ form.errors.latitude }}</p>
                                </div>

                                <!-- Terms -->
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3.5">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" v-model="form.terms_accepted"
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0">
                                        <div class="text-sm leading-snug text-gray-700">
                                            I agree to the
                                            <button type="button" @click="showTermsModal = true" class="text-blue-600 hover:underline font-semibold">Terms and Conditions</button>
                                            of MedEquip, including the
                                            <span v-if="form.role === 'customer'" class="font-semibold text-gray-800">Customer Terms</span>
                                            <span v-else class="font-semibold text-gray-800">Distributor Terms</span>.
                                        </div>
                                    </label>
                                    <p v-if="step3Errors.terms" class="text-red-500 text-xs mt-1.5 ml-7">{{ step3Errors.terms }}</p>
                                    <p v-if="form.errors.terms_accepted" class="text-red-600 text-xs mt-1 ml-7">{{ form.errors.terms_accepted }}</p>
                                </div>
                            </div>

                            <!-- ─── Navigation ─────────────────────────────────────────── -->
                            <div class="pt-2 flex items-center justify-between gap-3">
                                <button type="button" v-if="currentStep > 1" @click="currentStep--"
                                    class="text-gray-500 hover:text-gray-800 font-semibold text-sm px-4 py-2.5 rounded-xl hover:bg-gray-100 transition">
                                    Back
                                </button>
                                <div v-else></div>

                                <!-- Next button (steps 1 and 2) -->
                                <button type="button" v-if="currentStep < 3" @click="tryAdvance"
                                    class="font-bold text-sm px-8 py-2.5 rounded-xl transition shadow-sm flex items-center gap-2"
                                    :class="canAdvance ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                    Next
                                </button>

                                <!-- Submit button (step 3) -->
                                <button type="submit" v-if="currentStep === 3" :disabled="form.processing"
                                    class="bg-blue-600 text-white px-8 py-2.5 rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-sm hover:shadow-md disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2">
                                    <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ form.processing ? 'Creating account…' : 'Create Account' }}
                                </button>
                            </div>
                        </form>

                        <p class="text-sm text-center mt-6 text-gray-500">
                            Already have an account?
                            <Link href="/login" class="text-blue-600 hover:underline font-bold ml-1">Sign in</Link>
                        </p>
                        <p class="text-xs text-center mt-3 text-gray-400">
                            Purchasing for a hospital or clinic?
                            <a href="/corporate" class="text-blue-500 hover:underline">Apply for a Corporate Account</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TermsModal from '@/Components/TermsModal.vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    cities: Object,
    barangays: Object,
});

// ─── UI state ────────────────────────────────────────────────────────────────
const showPassword = ref(false);
const showTermsModal = ref(false);
const currentStep = ref(1);
const touchedContact = ref(false);

// ─── Address refs ─────────────────────────────────────────────────────────────
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

// ─── Map refs ─────────────────────────────────────────────────────────────────
const geocodeQuery = ref(null);
const detectedLocation = ref('');
let detectedTimer = null;
let isProgrammaticChange = false;

// ─── Username availability ────────────────────────────────────────────────────
const usernameHint = reactive({ state: 'idle', message: '' });
let usernameDebounce = null;
let usernameAbort = null;

// ─── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'customer',
    contact_number: '',
    address_line: '',
    city: '',
    barangay: '',
    latitude: null,
    longitude: null,
    terms_accepted: false,
    is_business: false,
    company_name: '',
    business_type: '',
    tin_number: '',
});

// ─── Draft persistence ────────────────────────────────────────────────────────
const STORAGE_KEY = 'registration_draft';
const lastSaved = ref(null);

onMounted(() => {
    const draft = localStorage.getItem(STORAGE_KEY);
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            Object.keys(parsed).forEach(key => {
                if (['password', 'password_confirmation'].includes(key)) return;
                if (parsed[key] !== undefined) form[key] = parsed[key];
            });
            if (form.city) {
                selectedCity.value = form.city;
                _applyCityChange(form.city);
                if (props.barangays?.[form.city]) {
                    const list = props.barangays[form.city];
                    if (list.includes(form.barangay)) {
                        selectedBarangay.value = form.barangay;
                    } else if (form.barangay) {
                        selectedBarangay.value = 'other';
                        manualBarangay.value = form.barangay;
                    }
                }
            }
        } catch (e) {
            console.error('Failed to load registration draft', e);
        }
    }
});

watch(() => ({
    username: form.username, email: form.email, role: form.role, contact_number: form.contact_number,
    address_line: form.address_line, city: form.city, barangay: form.barangay,
    latitude: form.latitude, longitude: form.longitude, is_business: form.is_business,
    company_name: form.company_name, business_type: form.business_type, tin_number: form.tin_number,
}), (newVal) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
    lastSaved.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}, { deep: true });

// ─── Computed helpers ─────────────────────────────────────────────────────────
const needsBusinessDetails = computed(() =>
    (form.role === 'customer' && form.is_business) || form.role === 'distributor'
);

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

const pwd = computed(() => form.password || '');
const pwdRules = computed(() => ({
    len: pwd.value.length >= 10,
    upper: /[A-Z]/.test(pwd.value),
    lower: /[a-z]/.test(pwd.value),
    num: /[0-9]/.test(pwd.value),
    sym: /[^A-Za-z0-9]/.test(pwd.value),
}));
const allPwdRulesPass = computed(() => Object.values(pwdRules.value).every(Boolean));

const contactError = computed(() => {
    if (!touchedContact.value && !form.contact_number) return null;
    if (!form.contact_number) return 'Contact number is required.';
    if (!/^09[0-9]{9}$/.test(form.contact_number)) return 'Must be 11 digits starting with 09 (e.g. 09123456789).';
    return null;
});

// ─── Per-step validation ───────────────────────────────────────────────────────
const step1Errors = reactive({ company_name: '', business_type: '' });
const step2Errors = reactive({ username: '', email: '', password: '', password_confirmation: '' });
const step3Errors = reactive({ city: '', barangay: '', address_line: '', location: '', terms: '' });

const validateStep1 = () => {
    step1Errors.company_name = '';
    step1Errors.business_type = '';
    let valid = true;
    if (needsBusinessDetails.value) {
        if (!form.company_name.trim()) {
            step1Errors.company_name = 'Company name is required.';
            valid = false;
        }
        if (form.role === 'customer' && form.is_business && !form.business_type) {
            step1Errors.business_type = 'Business type is required.';
            valid = false;
        }
    }
    return valid;
};

const validateStep2 = () => {
    step2Errors.username = '';
    step2Errors.email = '';
    step2Errors.password = '';
    step2Errors.password_confirmation = '';
    let valid = true;
    if (!form.username.trim()) {
        step2Errors.username = 'Username is required.';
        valid = false;
    } else if (usernameHint.state === 'taken') {
        step2Errors.username = 'This username is already taken.';
        valid = false;
    } else if (usernameHint.state === 'invalid') {
        step2Errors.username = usernameHint.message;
        valid = false;
    } else if (usernameHint.state === 'loading') {
        step2Errors.username = 'Please wait while we check username availability.';
        valid = false;
    }
    if (!form.email.trim()) {
        step2Errors.email = 'Email address is required.';
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        step2Errors.email = 'Enter a valid email address.';
        valid = false;
    }
    if (!allPwdRulesPass.value) {
        step2Errors.password = 'Password does not meet all requirements.';
        valid = false;
    }
    if (form.password !== form.password_confirmation) {
        step2Errors.password_confirmation = 'Passwords do not match.';
        valid = false;
    }
    if (!form.contact_number || !/^09[0-9]{9}$/.test(form.contact_number)) {
        touchedContact.value = true;
        valid = false;
    }
    return valid;
};

const validateStep3 = () => {
    step3Errors.city = '';
    step3Errors.barangay = '';
    step3Errors.address_line = '';
    step3Errors.location = '';
    step3Errors.terms = '';
    let valid = true;
    if (!form.city) { step3Errors.city = 'City is required.'; valid = false; }
    if (!form.barangay) { step3Errors.barangay = 'Barangay is required.'; valid = false; }
    if (!form.address_line.trim()) { step3Errors.address_line = 'Street address is required.'; valid = false; }
    if (!form.latitude || !form.longitude) { step3Errors.location = 'Please pin your location on the map.'; valid = false; }
    if (!form.terms_accepted) { step3Errors.terms = 'You must accept the Terms and Conditions.'; valid = false; }
    return valid;
};

const canAdvance = computed(() => {
    if (currentStep.value === 1) {
        if (needsBusinessDetails.value) {
            if (!form.company_name.trim()) return false;
            if (form.role === 'customer' && form.is_business && !form.business_type) return false;
        }
        return true;
    }
    if (currentStep.value === 2) {
        return form.username.trim() &&
            usernameHint.state === 'available' &&
            form.email.trim() &&
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) &&
            allPwdRulesPass.value &&
            form.password === form.password_confirmation &&
            /^09[0-9]{9}$/.test(form.contact_number);
    }
    return false;
});

const tryAdvance = () => {
    let valid = false;
    if (currentStep.value === 1) valid = validateStep1();
    if (currentStep.value === 2) valid = validateStep2();
    if (valid) currentStep.value++;
};

// ─── Contact number sanitizer ─────────────────────────────────────────────────
const sanitizeContactNumber = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length > 11) val = val.slice(0, 11);
    form.contact_number = val;
};

// ─── Fuzzy matching ───────────────────────────────────────────────────────────
const normalize = (str) =>
    String(str).toLowerCase().normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();

const fuzzyMatch = (input, list) => {
    if (!input || !list?.length) return null;
    const n = normalize(input);
    if (!n) return null;
    return list.find(i => normalize(i) === n)
        || list.find(i => normalize(i).startsWith(n + ' ') || normalize(i) === n)
        || list.find(i => n.includes(normalize(i)))
        || list.find(i => normalize(i).includes(n))
        || null;
};

// ─── City/barangay helpers ────────────────────────────────────────────────────
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    form.city = city || '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
        if ((props.barangays?.[city] || []).length === 0) selectedBarangay.value = 'other';
    } else {
        zipCode.value = '';
    }
};

const onCityChange = () => {
    _applyCityChange(selectedCity.value);
    if (!isProgrammaticChange && selectedCity.value) geocodeQuery.value = `${selectedCity.value}, Cavite, Philippines`;
};

watch(selectedBarangay, (brgy) => {
    if (!isProgrammaticChange && brgy && brgy !== 'other' && selectedCity.value)
        geocodeQuery.value = `Barangay ${brgy}, ${selectedCity.value}, Cavite, Philippines`;
});

watch([selectedCity, selectedBarangay, manualBarangay, zipCode], () => {
    form.city = selectedCity.value || '';
    form.barangay = selectedBarangay.value === 'other' ? (manualBarangay.value || '') : (selectedBarangay.value || '');
});

// ─── Map pin handler ──────────────────────────────────────────────────────────
const onMapAddressPicked = ({ city, barangay }) => {
    if (!city && !barangay) return;
    isProgrammaticChange = true;
    const cityKeys = Object.keys(props.cities || {});
    const matchedCity = fuzzyMatch(city, cityKeys);
    let matchedBrgy = null;
    if (matchedCity) {
        selectedCity.value = matchedCity;
        _applyCityChange(matchedCity);
        if (barangay) {
            const brgys = props.barangays?.[matchedCity] || [];
            matchedBrgy = fuzzyMatch(barangay, brgys);
            if (matchedBrgy) {
                selectedBarangay.value = matchedBrgy;
            } else {
                selectedBarangay.value = 'other';
                manualBarangay.value = barangay;
            }
        }
    }
    const cityLabel = matchedCity || city || '';
    const brgyLabel = matchedBrgy || (barangay ? `Brgy. ${barangay}` : '');
    if (cityLabel) {
        detectedLocation.value = `Pin Location: ${brgyLabel ? brgyLabel + ', ' : ''}${cityLabel}, Cavite`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }
    isProgrammaticChange = false;
};

// ─── Username watcher ─────────────────────────────────────────────────────────
watch(() => form.username, (val) => {
    clearTimeout(usernameDebounce);
    usernameAbort?.abort();
    const u = (val || '').trim().toLowerCase();
    if (!u) { usernameHint.state = 'idle'; usernameHint.message = ''; return; }
    if (u.length < 4 || u.length > 20 || !/^[a-zA-Z0-9_]+$/.test(u)) {
        usernameHint.state = 'invalid';
        usernameHint.message = 'Use 4–20 letters, numbers, or underscores only.';
        return;
    }
    usernameHint.state = 'loading';
    usernameHint.message = '';
    usernameDebounce = setTimeout(async () => {
        usernameAbort = new AbortController();
        try {
            const res = await fetch(`/register/username-available?username=${encodeURIComponent(u)}`, { signal: usernameAbort.signal, headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.valid) { usernameHint.state = 'invalid'; usernameHint.message = data.message || 'Invalid username.'; return; }
            usernameHint.state = data.available ? 'available' : 'taken';
            usernameHint.message = data.message || (data.available ? 'Available!' : 'Username taken.');
        } catch (e) {
            if (e?.name !== 'AbortError') { usernameHint.state = 'idle'; usernameHint.message = ''; }
        }
    }, 400);
});

// ─── Submit ───────────────────────────────────────────────────────────────────
const submit = () => {
    if (!validateStep3()) return;
    form.post('/register', {
        onSuccess: () => localStorage.removeItem(STORAGE_KEY),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>