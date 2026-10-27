<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import OrganizationFields from '@/components/OrganizationFields.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/organizations';
import { update as updateOwn } from '@/routes/tenant/organization';
const props = defineProps<{
    organization: {
        id: number;
        name: string;
        shelter_name: string;
        email: string | null;
        phone: string | null;
        address: string | null;
        is_active: boolean;
    };
    members: {
        id: number;
        name: string;
        email: string;
        is_organization_admin: boolean;
    }[];
    platform: boolean;
}>();
const action = computed(() =>
    props.platform ? update.form(props.organization.id) : updateOwn.form(),
);
defineOptions({ layout: { breadcrumbs: [{ title: 'Dane organizacji' }] } });
</script>
<template>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-8">
        <Head :title="organization.name" />
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                {{ platform ? 'Zarządzanie platformą' : 'Twoja organizacja' }}
            </p>
            <h1 class="text-3xl font-bold">{{ organization.name }}</h1>
            <p class="text-muted-foreground">Dane organizacji i schroniska.</p>
        </header>
        <Form
            v-bind="action"
            v-slot="{ errors, processing }"
            class="settings-card"
        >
            <OrganizationFields :organization="organization" :errors="errors" />
            <div v-if="platform" class="space-y-3 rounded-xl bg-accent/40 p-4">
                <input type="hidden" name="is_active" value="0" />
                <Label class="flex gap-3"
                    ><Checkbox
                        name="is_active"
                        value="1"
                        :default-value="organization.is_active"
                    />Organizacja aktywna</Label
                >
                <p class="text-sm text-muted-foreground">
                    Wyłączenie organizacji blokuje dostęp jej administratorów do
                    danych organizacji. Konta i dane pozostają zachowane.
                </p>
                <InputError :message="errors.is_active" />
            </div>
            <div class="border-t pt-5">
                <Button :disabled="processing">{{
                    processing ? 'Zapisywanie…' : 'Zapisz zmiany'
                }}</Button>
            </div>
        </Form>
        <section class="settings-card">
            <h2 class="text-lg font-bold">Konta organizacji</h2>
            <ul class="divide-y">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex flex-wrap items-center justify-between gap-2 py-4"
                >
                    <div class="min-w-0">
                        <p class="font-medium">{{ member.name }}</p>
                        <p class="text-sm break-all text-muted-foreground">
                            {{ member.email }}
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-accent px-3 py-1 text-xs text-accent-foreground"
                        >{{
                            member.is_organization_admin
                                ? 'Administrator organizacji'
                                : 'Pracownik'
                        }}</span
                    >
                </li>
            </ul>
        </section>
    </div>
</template>
