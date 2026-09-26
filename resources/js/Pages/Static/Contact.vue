<template>
    <Head title="Contact us · MedEquip" />
    <MainLayout>
        <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 pb-24 md:pb-16">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-ink">Contact us</h1>
            <p class="mt-3 text-ink-soft">Questions about an order, your account or selling on MedEquip? Reach out and we'll help.</p>

            <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-5">
                <!-- Contact details -->
                <section class="rounded-card border border-line bg-white p-6 md:col-span-2" aria-labelledby="get-in-touch">
                    <h2 id="get-in-touch" class="font-semibold text-ink">Get in touch</h2>
                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-sm text-ink-soft">Headquarters</dt>
                            <dd class="mt-0.5 text-ink">Dasmariñas City, Cavite,<br>Philippines 4114</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-ink-soft">Email</dt>
                            <dd class="mt-0.5"><a href="mailto:contact@medequip.shop" class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">contact@medequip.shop</a></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-ink-soft">Phone</dt>
                            <dd class="mt-0.5 tabular-nums text-ink">+63 912 345 6789</dd>
                        </div>
                    </dl>
                </section>

                <!-- Message form -->
                <section class="rounded-card border border-line bg-white p-6 md:col-span-3" aria-labelledby="send-message">
                    <h2 id="send-message" class="font-semibold text-ink">Send a message</h2>
                    <form @submit.prevent="submit" class="mt-5 space-y-4">
                        <TextInput v-model="form.name" label="Name" type="text" required autocomplete="name" :error="form.errors.name" />
                        <TextInput v-model="form.email" label="Email" type="email" required autocomplete="email" :error="form.errors.email" />
                        <div>
                            <label for="contact-message" class="mb-1 block text-sm font-medium text-ink">Message</label>
                            <textarea
                                id="contact-message"
                                v-model="form.message"
                                rows="5"
                                required
                                class="block w-full rounded-control border bg-white px-3 py-2 text-ink placeholder:text-ink-faint focus:outline-none focus:ring-2"
                                :class="form.errors.message ? 'border-danger focus:border-danger focus:ring-red-100' : 'border-line focus:border-brand focus:ring-brand-tint'"
                            ></textarea>
                            <p v-if="form.errors.message" class="mt-1 text-sm text-danger">{{ form.errors.message }}</p>
                        </div>
                        <BaseButton type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                            {{ form.processing ? 'Sending…' : 'Send message' }}
                        </BaseButton>
                    </form>
                </section>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const form = useForm({
    name: '',
    email: '',
    message: '',
});

const submit = () => {
    form.post('/contact', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
