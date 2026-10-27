<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { vAutoAnimate } from '@formkit/auto-animate';
import OrganizationFields from '@/components/OrganizationFields.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { index, store } from '@/routes/organizations';
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Organizacje', href: index() },
            { title: 'Nowa organizacja' },
        ],
    },
});
</script>
<template>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-8">
        <Head title="Nowa organizacja" />
        <header class="space-y-2">
            <h1 class="text-3xl font-bold">Nowa organizacja</h1>
            <p class="text-muted-foreground">
                Utwórz przestrzeń schroniska i konto jego administratora.
            </p>
        </header>
        <Form
            v-bind="store.form()"
            :reset-on-error="['admin_password', 'admin_password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <section class="settings-card">
                <h2 class="text-lg font-bold">Organizacja i schronisko</h2>
                <OrganizationFields :errors="errors" />
            </section>
            <section class="settings-card">
                <h2 class="text-lg font-bold">Konto administratora</h2>
                <p class="text-sm text-muted-foreground">
                    Użyj indywidualnego adresu e-mail. Administrator zaloguje
                    się podanym hasłem i potwierdzi adres e-mail przed wejściem
                    do organizacji. Przekaż dane dostępu bezpiecznym kanałem.
                </p>
                <div v-auto-animate class="grid gap-2">
                    <Label for="admin_name">Imię i nazwisko</Label
                    ><Input
                        id="admin_name"
                        name="admin_name"
                        required
                        autocomplete="off"
                    /><InputError :message="errors.admin_name" />
                </div>
                <div v-auto-animate class="grid gap-2">
                    <Label for="admin_email">Adres e-mail administratora</Label
                    ><Input
                        id="admin_email"
                        name="admin_email"
                        type="email"
                        required
                        autocomplete="off"
                    /><InputError :message="errors.admin_email" />
                </div>
                <div v-auto-animate class="grid gap-2">
                    <Label for="admin_password">Hasło startowe</Label
                    ><PasswordInput
                        id="admin_password"
                        name="admin_password"
                        required
                        autocomplete="new-password"
                    />
                    <p class="text-xs text-muted-foreground">
                        Co najmniej 12 znaków, mała i wielka litera oraz cyfra.
                    </p>
                    <InputError :message="errors.admin_password" />
                </div>
                <div class="grid gap-2">
                    <Label for="admin_password_confirmation"
                        >Powtórz hasło</Label
                    ><PasswordInput
                        id="admin_password_confirmation"
                        name="admin_password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </section>
            <div class="flex gap-3">
                <Button :disabled="processing">{{
                    processing ? 'Tworzenie…' : 'Utwórz organizację'
                }}</Button
                ><Button variant="outline" as-child
                    ><Link :href="index()">Anuluj</Link></Button
                >
            </div>
        </Form>
    </div>
</template>
