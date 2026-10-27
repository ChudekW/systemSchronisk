<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="settings-card border-destructive/20">
        <Heading
            variant="small"
            title="Usuń konto"
            description="Trwale usuń swoje konto i powiązane z nim dane."
        />
        <div class="space-y-4 rounded-xl bg-destructive/5 p-4">
            <div class="relative space-y-1 text-destructive">
                <p class="font-medium">Uwaga</p>
                <p class="text-sm">Tej operacji nie można cofnąć.</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button"
                        >Usuń konto</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >Czy na pewno chcesz usunąć konto?</DialogTitle
                            >
                            <DialogDescription>
                                Usunięcie konta trwale usunie również powiązane
                                z nim dane. Wpisz hasło, aby potwierdzić
                                usunięcie konta.
                            </DialogDescription>
                        </DialogHeader>

                        <div
                            v-auto-animate="{
                                duration: 180,
                                easing: 'ease-out',
                            }"
                            class="grid gap-2"
                        >
                            <Label for="password" class="sr-only">Hasło</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Hasło"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Anuluj
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Usuń konto
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
