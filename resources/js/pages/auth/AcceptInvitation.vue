<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login, logout } from '@/routes';
import { store } from '@/routes/invitations';
defineProps<{
    token: string | null;
    invitation: {
        name: string;
        email: string;
        shelter: string;
        role: string;
    } | null;
}>();
defineOptions({
    layout: {
        title: 'Dołącz do zespołu',
        description: 'Twoje miejsce we wspólnej opiece nad zwierzętami.',
    },
});
</script>
<template>
    <div class="space-y-6">
        <Head title="Zaproszenie do zespołu" />
        <template v-if="!invitation || !token"
            ><h2 class="font-bold">Zaproszenie jest niedostępne</h2>
            <p class="text-sm text-muted-foreground">
                Link wygasł, został anulowany lub został już wykorzystany. Jeśli
                nie masz jeszcze dostępu, poproś administratora schroniska o
                nowe zaproszenie.
            </p>
            <Button variant="outline" as-child
                ><Link :href="login()">Przejdź do logowania</Link></Button
            ></template
        >
        <template v-else-if="$page.props.auth.user"
            ><p class="text-sm text-muted-foreground">
                Masz już otwarte konto w aplikacji. Wyloguj się, a następnie
                ponownie otwórz link z zaproszenia.
            </p>
            <Button as-child
                ><Link :href="logout()" method="post" as="button"
                    >Wyloguj się</Link
                ></Button
            ></template
        >
        <template v-else>
            <div class="space-y-1 rounded-xl bg-accent p-4 text-sm">
                <p class="font-bold">{{ invitation.shelter }}</p>
                <p>{{ invitation.email }}</p>
                <p class="text-muted-foreground">Rola: {{ invitation.role }}</p>
            </div>
            <Form
                v-bind="store.form(token)"
                :reset-on-error="['password', 'password_confirmation']"
                v-slot="{ errors, processing }"
                class="space-y-5"
            >
                <div v-auto-animate class="space-y-2">
                    <Label for="name">Imię i nazwisko</Label
                    ><Input
                        id="name"
                        name="name"
                        :default-value="invitation.name"
                        required
                        maxlength="255"
                        autocomplete="name"
                    /><InputError :message="errors.name" />
                </div>
                <div v-auto-animate class="space-y-2">
                    <Label for="password">Ustaw hasło</Label
                    ><PasswordInput
                        id="password"
                        name="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                    />
                    <p class="text-xs text-muted-foreground">
                        Minimum 12 znaków, mała i wielka litera oraz cyfra.
                    </p>
                    <InputError :message="errors.password" />
                </div>
                <div v-auto-animate class="space-y-2">
                    <Label for="password_confirmation">Powtórz hasło</Label
                    ><PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        minlength="12"
                        autocomplete="new-password"
                    /><InputError :message="errors.password_confirmation" />
                </div>
                <Button class="w-full" :disabled="processing">{{
                    processing ? 'Aktywowanie…' : 'Aktywuj konto'
                }}</Button>
            </Form>
        </template>
    </div>
</template>
