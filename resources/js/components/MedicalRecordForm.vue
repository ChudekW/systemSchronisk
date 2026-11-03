<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/animals/health';
import type { MedicalRecord } from '@/types/medical';
const props = defineProps<{
    animalId: number;
    kinds: Record<string, string>;
    canManageMedical: boolean;
    members: { id: number; name: string }[];
    today: string;
    correction: MedicalRecord | null;
}>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const attachmentInput = ref<HTMLInputElement | null>(null);
const form = useForm({
    kind:
        props.correction?.kind ??
        (props.canManageMedical ? 'visit' : 'observation'),
    title: props.correction?.title ?? '',
    occurred_on: props.correction?.occurred_on ?? props.today,
    performed_by: props.correction?.performed_by ?? '',
    description: props.correction?.description ?? '',
    recommendations: props.correction?.recommendations ?? '',
    weight_kg: props.correction?.weight_kg ?? '',
    correction_of_id: props.correction?.id ?? null,
    correction_reason: '',
    next_due_date: '',
    assignee_id: '' as string | number,
    attachments: [] as File[],
});
const isMedical = computed(() => form.kind !== 'observation');
const availableKinds = computed(() =>
    props.canManageMedical
        ? props.kinds
        : { observation: props.kinds.observation },
);
watch(isMedical, (medical) => {
    if (!medical) {
        form.performed_by = '';
        form.recommendations = '';
        form.weight_kg = '';
        form.next_due_date = '';
        form.assignee_id = '';
    }
});
function filesChanged(event: Event) {
    form.attachments = Array.from(
        (event.target as HTMLInputElement).files ?? [],
    );
}
function submit() {
    form.post(store.url(props.animalId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (attachmentInput.value) attachmentInput.value.value = '';
            emit('saved');
        },
    });
}
const fieldClass =
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';
</script>
<template>
    <form
        class="space-y-5"
        @submit.prevent="submit"
        v-auto-animate="{ duration: 180 }"
    >
        <div
            v-if="correction"
            class="space-y-2 rounded-xl bg-accent/50 p-4 text-sm"
        >
            <p>
                Korekta wpisu #{{ correction.id }}:
                <strong>{{ correction.title }}</strong>
            </p>
            <p>
                Oryginał i jego załączniki pozostaną w historii. Istniejące
                zadanie nie zmieni się automatycznie — w razie potrzeby
                zaktualizuj je w sekcji zadań.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="emit('cancel')"
                >Anuluj korektę</Button
            >
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="medical-kind">Rodzaj wpisu</Label
                ><select
                    id="medical-kind"
                    v-model="form.kind"
                    :class="fieldClass"
                    :disabled="!!correction"
                >
                    <option
                        v-for="(label, value) in availableKinds"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option></select
                ><InputError :message="form.errors.kind" />
            </div>
            <div class="space-y-2">
                <Label for="medical-date">Data czynności</Label
                ><Input
                    id="medical-date"
                    v-model="form.occurred_on"
                    type="date"
                    :max="today"
                    required
                /><InputError :message="form.errors.occurred_on" />
            </div>
        </div>
        <div class="space-y-2">
            <Label for="medical-title">Tytuł</Label
            ><Input
                id="medical-title"
                v-model="form.title"
                required
                maxlength="160"
                placeholder="Np. badanie kontrolne"
            /><InputError :message="form.errors.title" />
        </div>
        <div v-if="isMedical" class="space-y-2">
            <Label for="medical-performer">Osoba wykonująca / lekarz</Label
            ><Input
                id="medical-performer"
                v-model="form.performed_by"
                required
                maxlength="255"
                placeholder="Imię i nazwisko"
            /><InputError :message="form.errors.performed_by" />
        </div>
        <div class="space-y-2">
            <Label for="medical-description">{{
                isMedical
                    ? 'Opis czynności i wyniki'
                    : 'Zaobserwowane objawy i zachowanie'
            }}</Label
            ><textarea
                id="medical-description"
                v-model="form.description"
                :class="fieldClass"
                rows="4"
                required
                maxlength="10000"
            /><InputError :message="form.errors.description" />
            <p
                v-if="form.kind === 'vaccination' || form.kind === 'deworming'"
                class="text-xs text-muted-foreground"
            >
                Wpisz nazwę preparatu, dawkę, sposób podania i numer serii,
                jeśli dotyczy.
            </p>
        </div>
        <template v-if="isMedical"
            ><div class="space-y-2">
                <Label for="medical-weight"
                    >Masa ciała (kg){{
                        form.kind === 'weight' ? ' — wymagana' : ''
                    }}</Label
                ><Input
                    id="medical-weight"
                    v-model="form.weight_kg"
                    type="number"
                    min="0.001"
                    max="5000"
                    step="0.001"
                    :required="form.kind === 'weight'"
                /><InputError :message="form.errors.weight_kg" />
            </div>
            <div class="space-y-2">
                <Label for="medical-recommendations"
                    >Zalecenia dla zespołu</Label
                ><textarea
                    id="medical-recommendations"
                    v-model="form.recommendations"
                    :class="fieldClass"
                    rows="3"
                    maxlength="10000"
                /><InputError :message="form.errors.recommendations" />
            </div>
            <fieldset
                class="space-y-4 rounded-xl border p-4"
                v-auto-animate="{ duration: 180 }"
            >
                <legend class="px-1 text-sm font-semibold">
                    Kolejny termin (opcjonalnie)
                </legend>
                <p class="text-xs text-muted-foreground">
                    Uzupełnienie terminu utworzy zadanie opieki dla wskazanej
                    osoby.
                </p>
                <div class="space-y-2">
                    <Label for="medical-next"
                        >Termin kontroli / kolejnej czynności</Label
                    ><Input
                        id="medical-next"
                        v-model="form.next_due_date"
                        type="date"
                        :min="today"
                    /><InputError :message="form.errors.next_due_date" />
                </div>
                <div v-if="form.next_due_date" class="space-y-2">
                    <Label for="medical-assignee">Osoba odpowiedzialna</Label
                    ><select
                        id="medical-assignee"
                        v-model="form.assignee_id"
                        :class="fieldClass"
                        required
                    >
                        <option value="" disabled>Wybierz osobę</option>
                        <option
                            v-for="member in members"
                            :key="member.id"
                            :value="member.id"
                        >
                            {{ member.name }}
                        </option></select
                    ><InputError :message="form.errors.assignee_id" />
                </div>
            </fieldset>
        </template>
        <div v-if="correction" class="space-y-2">
            <Label for="medical-reason">Powód korekty</Label
            ><textarea
                id="medical-reason"
                v-model="form.correction_reason"
                :class="fieldClass"
                rows="2"
                maxlength="2000"
                required
            /><InputError :message="form.errors.correction_reason" /><InputError
                :message="form.errors.correction_of_id"
            />
        </div>
        <div class="space-y-2">
            <Label for="medical-files">Załączniki</Label
            ><input
                ref="attachmentInput"
                id="medical-files"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                multiple
                :class="fieldClass"
                @change="filesChanged"
            />
            <p class="text-xs text-muted-foreground">
                Do 3 plików PDF, JPG lub PNG, każdy do 10 MB. Widoczne tylko w
                Twoim schronisku.
            </p>
            <InputError
                v-for="(message, key) in form.errors"
                :key="key"
                :message="
                    String(key).startsWith('attachments') ? message : undefined
                "
            />
        </div>
        <progress
            v-if="form.progress"
            :value="form.progress.percentage"
            max="100"
            class="w-full"
            aria-label="Wysyłanie załączników"
        />
        <Button :disabled="form.processing">{{
            form.processing
                ? 'Zapisywanie…'
                : correction
                  ? 'Zapisz korektę'
                  : 'Dodaj wpis'
        }}</Button>
        <p class="text-xs text-muted-foreground">
            Wpis zachowa autora i datę zapisu. Późniejsze poprawki dodasz jako
            korektę.
        </p>
    </form>
</template>
