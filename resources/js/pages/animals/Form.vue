<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show, store, update, photo } from '@/routes/animals';
import type { Animal, AnimalOptions } from '@/types/animal';
const props = defineProps<{
    animal: Animal | null;
    options: AnimalOptions;
    today: string;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Zwierzęta', href: index() },
            { title: 'Karta zwierzęcia' },
        ],
    },
});
const status = ref(props.animal?.status ?? 'admitted');
const identityFields = [
    {
        key: 'name',
        label: 'Imię / nazwa robocza',
        required: true,
        max: 120,
        placeholder: 'Np. Luna lub Pies znaleziony w parku',
    },
    {
        key: 'registration_number',
        label: 'Numer ewidencyjny',
        required: true,
        max: 80,
        placeholder: 'Np. P/2026/001',
    },
    {
        key: 'breed',
        label: 'Rasa / typ',
        max: 120,
        placeholder: 'Np. mieszaniec',
    },
    {
        key: 'color',
        label: 'Umaszczenie',
        max: 120,
        placeholder: 'Np. czarno-białe',
    },
    {
        key: 'microchip',
        label: 'Numer mikroczipa',
        max: 15,
        placeholder: '9–15 cyfr, jeśli znany',
    },
] as const;
const noteFields = [
    {
        key: 'health_notes',
        label: 'Zdrowie',
        hint: 'Stan zdrowia, znane choroby, szczepienia i odrobaczenia.',
    },
    {
        key: 'behavior_notes',
        label: 'Charakter i zachowanie',
        hint: 'Kontakt z ludźmi, innymi zwierzętami i reakcje na stres.',
    },
    {
        key: 'care_notes',
        label: 'Zalecenia opieki',
        hint: 'Dieta, potrzeby i ważne wskazówki dla opiekunów.',
    },
    {
        key: 'description',
        label: 'Dodatkowy opis',
        hint: 'Znaki szczególne i inne informacje pomocne w opiece.',
    },
] as const;
</script>
<template>
    <div class="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-8">
        <Head :title="animal ? `Edycja: ${animal.name}` : 'Dodaj zwierzę'" />
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                {{ $page.props.auth.organization?.shelter_name }}
            </p>
            <h1 class="text-3xl font-bold">
                {{ animal ? 'Edytuj kartę zwierzęcia' : 'Dodaj zwierzę' }}
            </h1>
            <p class="text-muted-foreground">
                Pola oznaczone * są wymagane. Nieznane informacje możesz
                uzupełnić później.
            </p>
        </header>
        <Form
            :key="animal?.id ?? 'new'"
            :action="animal ? update.url(animal.id) : store.url()"
            method="post"
            v-slot="{ errors, processing }"
            class="space-y-6"
            :disable-while-processing="true"
        >
            <input v-if="animal" type="hidden" name="_method" value="put" />
            <section class="settings-card">
                <h2 class="text-lg font-bold">Tożsamość i wygląd</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div
                        v-for="field in identityFields"
                        :key="field.key"
                        v-auto-animate
                        class="space-y-2"
                    >
                        <Label :for="field.key"
                            >{{ field.label
                            }}{{ 'required' in field ? ' *' : '' }}</Label
                        >
                        <Input
                            :id="field.key"
                            :name="field.key"
                            :default-value="animal?.[field.key] ?? ''"
                            :maxlength="field.max"
                            :required="'required' in field"
                            :placeholder="field.placeholder"
                            :aria-invalid="!!errors[field.key]"
                            :aria-describedby="`${field.key}-error`"
                        />
                        <InputError
                            :id="`${field.key}-error`"
                            :message="errors[field.key]"
                        />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="species">Gatunek *</Label
                        ><select
                            id="species"
                            name="species"
                            :value="animal?.species ?? 'dog'"
                            class="animal-select"
                            required
                        >
                            <option
                                v-for="(label, value) in options.species"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option></select
                        ><InputError :message="errors.species" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="sex">Płeć *</Label
                        ><select
                            id="sex"
                            name="sex"
                            :value="animal?.sex ?? 'unknown'"
                            class="animal-select"
                            required
                        >
                            <option
                                v-for="(label, value) in options.sexes"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option></select
                        ><InputError :message="errors.sex" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="weight_kg">Masa ciała (kg)</Label
                        ><Input
                            id="weight_kg"
                            name="weight_kg"
                            type="number"
                            step="0.01"
                            min="0.01"
                            max="9999"
                            :default-value="animal?.weight_kg ?? ''"
                        /><InputError :message="errors.weight_kg" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="birth_date">Data urodzenia</Label
                        ><Input
                            id="birth_date"
                            name="birth_date"
                            type="date"
                            :max="today"
                            :default-value="animal?.birth_date ?? ''"
                        /><InputError :message="errors.birth_date" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="birth_date_estimated"
                            >Dokładność daty urodzenia *</Label
                        ><select
                            id="birth_date_estimated"
                            name="birth_date_estimated"
                            :value="animal?.birth_date_estimated ? '1' : '0'"
                            class="animal-select"
                        >
                            <option value="0">Dokładna / brak daty</option>
                            <option value="1">Szacunkowa</option></select
                        ><InputError :message="errors.birth_date_estimated" />
                    </div>
                </div>
            </section>
            <section class="settings-card">
                <h2 class="text-lg font-bold">Przyjęcie i pobyt</h2>
                <p class="text-sm text-muted-foreground">
                    Zwierzę zostanie przypisane do:
                    {{ $page.props.auth.organization?.shelter_name }}.
                </p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div v-auto-animate class="space-y-2">
                        <Label for="admitted_at">Data przyjęcia *</Label
                        ><Input
                            id="admitted_at"
                            name="admitted_at"
                            type="date"
                            :max="today"
                            :default-value="animal?.admitted_at ?? today"
                            required
                        /><InputError :message="errors.admitted_at" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="admission_reason">Powód przyjęcia *</Label
                        ><select
                            id="admission_reason"
                            name="admission_reason"
                            :value="animal?.admission_reason ?? 'found'"
                            class="animal-select"
                        >
                            <option
                                v-for="(
                                    label, value
                                ) in options.admissionReasons"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option></select
                        ><InputError :message="errors.admission_reason" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="found_location">Miejsce znalezienia</Label
                        ><Input
                            id="found_location"
                            name="found_location"
                            :default-value="animal?.found_location ?? ''"
                            maxlength="255"
                        /><InputError :message="errors.found_location" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="location">Lokalizacja / boks</Label
                        ><Input
                            id="location"
                            name="location"
                            :default-value="animal?.location ?? ''"
                            maxlength="120"
                            placeholder="Np. Pawilon A, boks 12"
                        /><InputError :message="errors.location" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="status">Status *</Label
                        ><select
                            id="status"
                            v-model="status"
                            name="status"
                            class="animal-select"
                        >
                            <option
                                v-for="(label, value) in options.statuses"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option></select
                        ><InputError :message="errors.status" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="sterilization_status"
                            >Kastracja / sterylizacja *</Label
                        ><select
                            id="sterilization_status"
                            name="sterilization_status"
                            :value="animal?.sterilization_status ?? 'unknown'"
                            class="animal-select"
                        >
                            <option
                                v-for="(label, value) in options.sterilization"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option></select
                        ><InputError :message="errors.sterilization_status" />
                    </div>
                </div>
                <div v-auto-animate>
                    <div
                        v-if="!animal || status !== animal.status"
                        class="space-y-2"
                    >
                        <Label for="status_note">Notatka do statusu</Label
                        ><textarea
                            id="status_note"
                            name="status_note"
                            rows="3"
                            maxlength="2000"
                            class="animal-textarea"
                            placeholder="Np. rozpoczęcie kwarantanny po przyjęciu"
                        /><InputError :message="errors.status_note" />
                    </div>
                </div>
            </section>
            <section class="settings-card">
                <h2 class="text-lg font-bold">Zdrowie i codzienna opieka</h2>
                <div
                    v-for="field in noteFields"
                    :key="field.key"
                    v-auto-animate
                    class="space-y-2"
                >
                    <Label :for="field.key">{{ field.label }}</Label>
                    <p class="text-sm text-muted-foreground">
                        {{ field.hint }}
                    </p>
                    <textarea
                        :id="field.key"
                        :name="field.key"
                        :value="animal?.[field.key] ?? ''"
                        rows="3"
                        maxlength="10000"
                        class="animal-textarea"
                    /><InputError :message="errors[field.key]" />
                </div>
            </section>
            <section v-auto-animate class="settings-card">
                <h2 class="text-lg font-bold">Zdjęcie główne</h2>
                <img
                    v-if="animal?.has_photo"
                    :src="photo.url(animal.id)"
                    :alt="animal.name"
                    class="max-h-48 rounded-xl object-contain"
                />
                <div class="space-y-2">
                    <Label for="photo">{{
                        animal?.has_photo ? 'Zastąp zdjęcie' : 'Dodaj zdjęcie'
                    }}</Label
                    ><input
                        id="photo"
                        name="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full rounded-md border border-input px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-accent file:px-3 file:py-1 file:text-accent-foreground focus-visible:outline-ring"
                    />
                    <p class="text-sm text-muted-foreground">
                        JPG, PNG lub WebP, do 5 MB i 6000 × 6000 px. Zdjęcie
                        jest dostępne tylko dla Twojej organizacji.
                    </p>
                    <InputError :message="errors.photo" />
                </div>
                <Label v-if="animal?.has_photo" class="flex items-center gap-2"
                    ><input
                        type="checkbox"
                        name="remove_photo"
                        value="1"
                        class="size-4 accent-primary"
                    />Usuń obecne zdjęcie</Label
                >
            </section>
            <p
                v-if="Object.keys(errors).length"
                role="alert"
                class="text-sm text-destructive"
            >
                Nie zapisano karty. Sprawdź pola oznaczone komunikatem błędu.
            </p>
            <footer class="flex flex-wrap justify-end gap-3">
                <Button variant="outline" as-child
                    ><Link :href="animal ? show(animal.id) : index()"
                        >Anuluj</Link
                    ></Button
                ><Button type="submit" :disabled="processing">{{
                    processing
                        ? 'Zapisywanie…'
                        : animal
                          ? 'Zapisz zmiany'
                          : 'Dodaj do kartoteki'
                }}</Button>
            </footer>
        </Form>
    </div>
</template>
