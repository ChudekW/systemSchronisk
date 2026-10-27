<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    organization?: {
        name: string;
        shelter_name: string;
        email: string | null;
        phone: string | null;
        address: string | null;
    };
    errors: Record<string, string>;
}>();
const fields = [
    { name: 'name', label: 'Nazwa organizacji', type: 'text', required: true },
    {
        name: 'shelter_name',
        label: 'Nazwa schroniska',
        type: 'text',
        required: true,
    },
    {
        name: 'email',
        label: 'Kontaktowy adres e-mail',
        type: 'email',
        required: false,
    },
    { name: 'phone', label: 'Telefon', type: 'tel', required: false },
    {
        name: 'address',
        label: 'Adres schroniska',
        type: 'text',
        required: false,
    },
] as const;
</script>
<template>
    <div class="grid gap-5 sm:grid-cols-2">
        <div
            v-for="field in fields"
            :key="field.name"
            v-auto-animate="{ duration: 180 }"
            class="grid gap-2"
            :class="field.name === 'address' ? 'sm:col-span-2' : ''"
        >
            <Label :for="field.name">{{ field.label }}</Label>
            <Input
                :id="field.name"
                :name="field.name"
                :type="field.type"
                :required="field.required"
                :default-value="organization?.[field.name] ?? ''"
                :maxlength="field.name === 'phone' ? 40 : 255"
            />
            <InputError :message="errors[field.name]" />
        </div>
    </div>
</template>
