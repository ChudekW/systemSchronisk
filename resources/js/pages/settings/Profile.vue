<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ustawienia profilu',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="space-y-6">
        <Head title="Ustawienia profilu" />

        <h2 class="sr-only">Ustawienia profilu</h2>

        <div class="settings-card">
            <Heading
                variant="small"
                title="Profil"
                description="Zaktualizuj swoje imię, nazwisko i adres e-mail."
            />

            <Form
                v-bind="ProfileController.update.form()"
                class="max-w-xl space-y-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Imię i nazwisko</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        placeholder="Imię i nazwisko"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Adres e-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        name="email"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        placeholder="Adres e-mail"
                    />
                    <InputError class="mt-2" :message="errors.email" />
                </div>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                >
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Twój adres e-mail nie został jeszcze potwierdzony.
                        <Link
                            :href="send()"
                            as="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        >
                            Wyślij ponownie link weryfikacyjny.
                        </Link>
                    </p>

                    <div
                        v-if="page.props.status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600"
                    >
                        Wysłaliśmy nowy link weryfikacyjny na Twój adres e-mail.
                    </div>
                </div>

                <div class="flex items-center gap-4 border-t pt-5">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                        >Zapisz zmiany</Button
                    >
                </div>
            </Form>
        </div>

        <DeleteUser />
    </div>
</template>
