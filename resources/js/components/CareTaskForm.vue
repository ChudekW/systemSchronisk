<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, update } from '@/routes/animals/tasks';
import type { CareTask, CareOptions } from '@/types/care-task';
const props = defineProps<{
    animalId: number;
    options: CareOptions;
    task?: CareTask;
    today?: string;
}>();
const form = useForm({
    title: props.task?.title ?? '',
    description: props.task?.description ?? '',
    assignee_id: props.task?.assignee_id ?? '',
    due_date: props.task?.due_date ?? props.today ?? '',
    priority: props.task?.priority ?? 'normal',
    status: props.task?.status ?? 'todo',
    note: '',
    version: props.task?.version ?? 1,
});
function submit() {
    if (props.task) {
        form.put(update.url({ animal: props.animalId, task: props.task.id }), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('note');
                form.version = props.task!.version;
            },
        });
    } else {
        form.post(store.url(props.animalId));
    }
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
        <InputError :message="form.errors.version" role="alert" />
        <div class="space-y-2">
            <Label for="task-title">Zadanie</Label
            ><Input
                id="task-title"
                v-model="form.title"
                required
                maxlength="200"
                placeholder="Np. spacer i obserwacja zachowania"
            /><InputError :message="form.errors.title" />
        </div>
        <div class="space-y-2">
            <Label for="task-description">Opis i wskazówki</Label
            ><textarea
                id="task-description"
                v-model="form.description"
                :class="fieldClass"
                rows="3"
                maxlength="5000"
            /><InputError :message="form.errors.description" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="task-assignee">Osoba odpowiedzialna</Label
                ><select
                    id="task-assignee"
                    v-model="form.assignee_id"
                    :class="fieldClass"
                    required
                >
                    <option value="" disabled>Wybierz osobę</option>
                    <option
                        v-if="
                            task?.assignee_id &&
                            !options.members.some(
                                (member) => member.id === task?.assignee_id,
                            )
                        "
                        :value="task.assignee_id"
                        disabled
                    >
                        {{ task.assignee?.name ?? 'Usunięte konto' }} —
                        nieaktywne
                    </option>
                    <option
                        v-for="member in options.members"
                        :key="member.id"
                        :value="member.id"
                    >
                        {{ member.name }}
                    </option></select
                ><InputError :message="form.errors.assignee_id" />
            </div>
            <div class="space-y-2">
                <Label for="task-date">Termin wykonania</Label
                ><Input
                    id="task-date"
                    v-model="form.due_date"
                    type="date"
                    required
                /><InputError :message="form.errors.due_date" />
            </div>
            <div class="space-y-2">
                <Label for="task-priority">Priorytet</Label
                ><select
                    id="task-priority"
                    v-model="form.priority"
                    :class="fieldClass"
                >
                    <option
                        v-for="(label, value) in options.priorities"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option></select
                ><InputError :message="form.errors.priority" />
            </div>
            <div class="space-y-2">
                <Label for="task-status">Status</Label
                ><select
                    id="task-status"
                    v-model="form.status"
                    :class="fieldClass"
                >
                    <option
                        v-for="(label, value) in options.statuses"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option></select
                ><InputError :message="form.errors.status" />
            </div>
        </div>
        <div
            v-if="task || form.status === 'done'"
            class="space-y-2"
            v-auto-animate="{ duration: 180 }"
        >
            <Label for="task-note">{{
                form.status === 'done'
                    ? 'Notatka z wykonania (wymagana)'
                    : 'Notatka do historii'
            }}</Label
            ><textarea
                id="task-note"
                v-model="form.note"
                :class="fieldClass"
                rows="3"
                maxlength="5000"
                :required="form.status === 'done'"
                placeholder="Co zrobiono? Jak zwierzę się czuło?"
            /><InputError :message="form.errors.note" />
            <p class="text-xs text-muted-foreground">
                Notatka zostanie zapisana z Twoim imieniem i datą.
            </p>
        </div>
        <Button :disabled="form.processing">{{
            form.processing
                ? 'Zapisywanie…'
                : task
                  ? 'Zapisz zmiany'
                  : 'Dodaj zadanie'
        }}</Button>
    </form>
</template>
