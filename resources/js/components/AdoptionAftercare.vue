<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { vAutoAnimate } from '@formkit/auto-animate';
import { aftercare } from '@/routes/adoptions';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Adoption } from '@/types/adoption';
import adoptions from '@/routes/adoptions';
const props = defineProps<{
    application: Adoption;
    canRegisterReturn: boolean;
    members: { id: number; name: string }[];
    today: string;
}>();
const page = usePage();
const contact = useForm({
    version: props.application.version,
    contact_on: props.today,
    note: '',
    task_title: '',
    task_due_date: '',
    assignee_id: props.members.some(
        (member) => member.id === props.application.coordinator_id,
    )
        ? props.application.coordinator_id!
        : ('' as string | number),
});
const admission = useForm({
    version: props.application.version,
    returned_on: props.today,
    return_reason: '',
    received_by: page.props.auth.user.name,
});
const fieldClass =
    'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-ring';
function saveContact() {
    contact.post(aftercare.url(props.application.id), {
        preserveScroll: true,
        errorBag: 'aftercare',
    });
}
function saveReturn() {
    admission.post(adoptions.return.url(props.application.id), {
        preserveScroll: true,
        errorBag: 'return',
    });
}
</script>
<template>
    <div class="space-y-6">
        <section class="space-y-4">
            <h2 class="text-xl font-bold">Opieka poadopcyjna</h2>
            <p class="text-sm text-muted-foreground">
                Zapisuj rozmowy, obserwacje i ustalenia. Dane zakończonej
                adopcji pozostaną bez zmian.
            </p>
            <form
                class="space-y-4"
                @submit.prevent="saveContact"
                v-auto-animate="{ duration: 180 }"
            >
                <InputError :message="contact.errors.version" role="alert" />
                <div class="space-y-2">
                    <Label for="aftercare-date">Data kontaktu / ustalenia</Label
                    ><Input
                        id="aftercare-date"
                        v-model="contact.contact_on"
                        type="date"
                        :min="application.adopted_on ?? undefined"
                        :max="today"
                        required
                    /><InputError :message="contact.errors.contact_on" />
                </div>
                <div class="space-y-2">
                    <Label for="aftercare-note">Notatka</Label
                    ><textarea
                        id="aftercare-note"
                        v-model="contact.note"
                        :class="fieldClass"
                        rows="4"
                        maxlength="5000"
                        required
                    /><InputError :message="contact.errors.note" />
                </div>
                <fieldset class="space-y-3 rounded-xl border p-4">
                    <legend class="px-1 text-sm font-medium">
                        Zaplanuj kolejny kontakt (opcjonalnie)
                    </legend>
                    <Label for="aftercare-task">Zadanie</Label
                    ><Input
                        id="aftercare-task"
                        v-model="contact.task_title"
                        maxlength="160"
                        placeholder="Np. zadzwonić i zapytać o adaptację"
                    /><InputError
                        :message="contact.errors.task_title"
                    /><template v-if="contact.task_title"
                        ><Label for="aftercare-due">Termin</Label
                        ><Input
                            id="aftercare-due"
                            v-model="contact.task_due_date"
                            type="date"
                            :min="today"
                            required /><InputError
                            :message="contact.errors.task_due_date" /><Label
                            for="aftercare-worker"
                            >Osoba odpowiedzialna</Label
                        ><select
                            id="aftercare-worker"
                            v-model="contact.assignee_id"
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
                        ><InputError :message="contact.errors.assignee_id"
                    /></template>
                </fieldset>
                <Button :disabled="contact.processing">{{
                    contact.processing
                        ? 'Zapisywanie…'
                        : 'Zapisz kontakt i ustalenia'
                }}</Button>
            </form>
        </section>
        <details v-if="canRegisterReturn" class="rounded-xl border p-4">
            <summary class="cursor-pointer font-semibold">
                Zarejestruj powrót zwierzęcia
            </summary>
            <form
                class="mt-4 space-y-4"
                @submit.prevent="saveReturn"
                v-auto-animate="{ duration: 180 }"
            >
                <p class="text-sm text-muted-foreground">
                    Zwierzę wróci do statusu „Przyjęte”. Poprzednia adopcja i
                    jej dokumenty pozostaną w historii. Przejrzyj niewykonane
                    zadania związane z tą adopcją.
                </p>
                <InputError :message="admission.errors.version" role="alert" />
                <div class="space-y-2">
                    <Label for="return-date">Data powrotu</Label
                    ><Input
                        id="return-date"
                        v-model="admission.returned_on"
                        type="date"
                        :min="application.adopted_on ?? undefined"
                        :max="today"
                        required
                    /><InputError :message="admission.errors.returned_on" />
                </div>
                <div class="space-y-2">
                    <Label for="return-person">Osoba przyjmująca zwierzę</Label
                    ><Input
                        id="return-person"
                        v-model="admission.received_by"
                        required
                        maxlength="255"
                    /><InputError :message="admission.errors.received_by" />
                </div>
                <div class="space-y-2">
                    <Label for="return-reason">Powód powrotu i ustalenia</Label
                    ><textarea
                        id="return-reason"
                        v-model="admission.return_reason"
                        :class="fieldClass"
                        rows="4"
                        required
                        maxlength="5000"
                    /><InputError :message="admission.errors.return_reason" />
                </div>
                <Button variant="outline" :disabled="admission.processing">{{
                    admission.processing
                        ? 'Zapisywanie…'
                        : 'Potwierdź przyjęcie zwierzęcia do schroniska'
                }}</Button>
            </form>
        </details>
    </div>
</template>
