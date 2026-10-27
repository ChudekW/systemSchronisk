<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Bezpieczeństwo konta',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <div class="space-y-6">
        <Head title="Bezpieczeństwo konta" />

        <h2 class="sr-only">Bezpieczeństwo konta</h2>

        <div class="settings-card">
            <Heading
                variant="small"
                title="Zmień hasło"
                description="Wybierz długie, unikalne hasło, aby chronić swoje konto."
            />

            <Form
                v-bind="SecurityController.update.form()"
                :options="{
                    preserveScroll: true,
                }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="max-w-xl space-y-5"
                v-slot="{ errors, processing }"
            >
                <div
                    v-auto-animate="{ duration: 180, easing: 'ease-out' }"
                    class="grid gap-2"
                >
                    <Label for="current_password">Obecne hasło</Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        placeholder="Obecne hasło"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div
                    v-auto-animate="{ duration: 180, easing: 'ease-out' }"
                    class="grid gap-2"
                >
                    <Label for="password">Nowe hasło</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Nowe hasło"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div
                    v-auto-animate="{ duration: 180, easing: 'ease-out' }"
                    class="grid gap-2"
                >
                    <Label for="password_confirmation">Potwierdź hasło</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Potwierdź hasło"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-4 border-t pt-5">
                    <Button
                        :disabled="processing"
                        data-test="update-password-button"
                    >
                        Zapisz zmiany
                    </Button>
                </div>
            </Form>
        </div>

        <ManageTwoFactor
            :canManageTwoFactor="canManageTwoFactor"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />

        <ManagePasskeys
            :canManagePasskeys="canManagePasskeys"
            :passkeys="passkeys"
        />
    </div>
</template>
