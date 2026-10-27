<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Zaloguj się do swojego konta',
        description: 'Wpisz adres e-mail i hasło, aby przejść dalej.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Zaloguj się" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div
                v-auto-animate="{ duration: 180, easing: 'ease-out' }"
                class="grid gap-2"
            >
                <Label for="email">Adres e-mail</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="jan@przyklad.pl"
                />
                <InputError :message="errors.email" />
            </div>

            <div
                v-auto-animate="{ duration: 180, easing: 'ease-out' }"
                class="grid gap-2"
            >
                <div class="flex items-center justify-between">
                    <Label for="password">Hasło</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Nie pamiętasz hasła?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Hasło"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Zapamiętaj mnie</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Zaloguj się
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Nie masz jeszcze konta?
            <TextLink :href="register()" :tabindex="5">Załóż konto</TextLink>
        </div>
    </Form>
</template>
