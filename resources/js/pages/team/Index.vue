<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Users, UserPlus, Mail } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update, resend, cancel } from '@/routes/team';
import type { Paginated } from '@/types/animal';

defineProps<{
    members: Paginated<{
        id: number;
        name: string;
        email: string;
        role: string;
        is_active: boolean;
        pending: boolean;
        invitation_status: 'pending' | 'expired' | 'cancelled' | null;
        invitation_expires_at: string | null;
    }>;
    roles: Record<string, string>;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Zespół', href: index() }] },
});
const statusLabels = {
    pending: 'Zaproszenie wysłane',
    expired: 'Zaproszenie wygasło',
    cancelled: 'Zaproszenie anulowane',
};
</script>
<template>
    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-8">
        <Head title="Zespół schroniska" />
        <header class="space-y-2">
            <p class="text-sm font-medium text-primary">
                {{ $page.props.auth.organization?.shelter_name }}
            </p>
            <h1 class="text-3xl font-bold">Zespół schroniska</h1>
            <p class="text-muted-foreground">
                Ludzie, którzy wspólnie dbają o zwierzęta. Zarządzaj dostępem do
                Waszej przestrzeni.
            </p>
        </header>
        <InputError :message="$page.props.errors.member" role="alert" />
        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-4">
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <Users
                        class="size-5 text-primary"
                        aria-hidden="true"
                    />Osoby w zespole
                    <span class="text-sm font-normal text-muted-foreground"
                        >({{ members.total }})</span
                    >
                </h2>
                <article
                    v-for="member in members.data"
                    :key="member.id"
                    class="settings-card"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div class="min-w-0 space-y-1">
                            <h3 class="font-bold">
                                {{ member.name }}
                                <span
                                    v-if="
                                        member.id === $page.props.auth.user.id
                                    "
                                    class="text-sm font-normal text-muted-foreground"
                                    >(Ty)</span
                                >
                            </h3>
                            <p class="text-sm break-all text-muted-foreground">
                                {{ member.email }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium"
                            :class="
                                member.pending
                                    ? 'bg-secondary text-secondary-foreground'
                                    : member.is_active
                                      ? 'bg-accent text-accent-foreground'
                                      : 'bg-destructive/10 text-destructive'
                            "
                            >{{
                                member.invitation_status
                                    ? statusLabels[member.invitation_status]
                                    : member.is_active
                                      ? 'Aktywne konto'
                                      : 'Zablokowane konto'
                            }}</span
                        >
                    </div>
                    <template v-if="member.pending">
                        <p class="text-sm text-muted-foreground">
                            Rola: {{ roles[member.role] }}. Konto zostanie
                            aktywowane po ustawieniu hasła przez pracownika.
                        </p>
                        <p
                            v-if="member.invitation_expires_at"
                            class="text-xs text-muted-foreground"
                        >
                            Ważność linku:
                            {{
                                new Intl.DateTimeFormat('pl-PL', {
                                    dateStyle: 'medium',
                                    timeStyle: 'short',
                                }).format(
                                    new Date(member.invitation_expires_at),
                                )
                            }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <Form
                                v-bind="resend.form(member.id)"
                                v-slot="{ processing }"
                                ><Button
                                    variant="outline"
                                    :disabled="processing"
                                    ><Mail
                                        class="size-4"
                                        aria-hidden="true"
                                    />{{
                                        processing
                                            ? 'Wysyłanie…'
                                            : 'Ponów zaproszenie'
                                    }}</Button
                                ></Form
                            >
                            <Form
                                v-if="member.invitation_status !== 'cancelled'"
                                v-bind="cancel.form(member.id)"
                                v-slot="{ processing }"
                                ><Button variant="ghost" :disabled="processing"
                                    >Anuluj zaproszenie</Button
                                ></Form
                            >
                        </div>
                    </template>
                    <p
                        v-else-if="member.id === $page.props.auth.user.id"
                        class="text-sm text-muted-foreground"
                    >
                        {{ roles[member.role] }} · Zmianę Twoich uprawnień może
                        wykonać inny administrator.
                    </p>
                    <Form
                        v-else
                        v-bind="update.form(member.id)"
                        v-slot="{ processing, errors }"
                        class="space-y-3"
                    >
                        <div
                            class="grid items-end gap-3 sm:grid-cols-[1fr_1fr_auto]"
                        >
                            <div class="space-y-2">
                                <Label :for="`role-${member.id}`">Rola</Label
                                ><select
                                    :id="`role-${member.id}`"
                                    name="role"
                                    :value="member.role"
                                    class="animal-select"
                                >
                                    <option
                                        v-for="(label, value) in roles"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label :for="`active-${member.id}`"
                                    >Dostęp</Label
                                ><select
                                    :id="`active-${member.id}`"
                                    name="is_active"
                                    :value="member.is_active ? '1' : '0'"
                                    class="animal-select"
                                >
                                    <option value="1">Aktywny</option>
                                    <option value="0">Zablokowany</option>
                                </select>
                            </div>
                            <Button variant="outline" :disabled="processing">{{
                                processing ? 'Zapisywanie…' : 'Zapisz'
                            }}</Button>
                        </div>
                        <InputError
                            :message="errors.role || errors.is_active"
                        />
                    </Form>
                </article>
                <nav
                    v-if="members.last_page > 1"
                    class="flex items-center justify-center gap-4"
                    aria-label="Strony zespołu"
                >
                    <Button
                        v-if="members.prev_page_url"
                        variant="outline"
                        as-child
                        ><Link :href="members.prev_page_url"
                            >Poprzednia</Link
                        ></Button
                    ><span class="text-sm"
                        >{{ members.current_page }} /
                        {{ members.last_page }}</span
                    ><Button
                        v-if="members.next_page_url"
                        variant="outline"
                        as-child
                        ><Link :href="members.next_page_url"
                            >Następna</Link
                        ></Button
                    >
                </nav>
            </section>
            <aside class="space-y-5">
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['name', 'email']"
                    v-slot="{ errors, processing }"
                    class="settings-card"
                >
                    <h2 class="flex items-center gap-2 text-lg font-bold">
                        <UserPlus
                            class="size-5 text-primary"
                            aria-hidden="true"
                        />Zaproś do zespołu
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Pracownik otrzyma e-mail z linkiem do ustawienia
                        własnego hasła. Zaproszenie jest ważne przez 7 dni.
                    </p>
                    <div v-auto-animate class="space-y-2">
                        <Label for="invite-name">Imię i nazwisko</Label
                        ><Input
                            id="invite-name"
                            name="name"
                            required
                            maxlength="255"
                            autocomplete="off"
                        /><InputError :message="errors.name" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="invite-email">Adres e-mail</Label
                        ><Input
                            id="invite-email"
                            name="email"
                            type="email"
                            required
                            maxlength="255"
                            autocomplete="off"
                            placeholder="anna@przyklad.pl"
                        /><InputError :message="errors.email" />
                    </div>
                    <div v-auto-animate class="space-y-2">
                        <Label for="invite-role">Rola w schronisku</Label
                        ><select
                            id="invite-role"
                            name="role"
                            class="animal-select"
                            :value="'caretaker'"
                        >
                            <option
                                v-for="(label, value) in roles"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option></select
                        ><InputError :message="errors.role" />
                    </div>
                    <Button class="w-full" :disabled="processing">{{
                        processing ? 'Wysyłanie…' : 'Wyślij zaproszenie'
                    }}</Button>
                </Form>
                <section class="rounded-2xl bg-accent/50 p-5 text-sm">
                    <h2 class="mb-3 font-bold">Role i uprawnienia</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="font-medium">Administrator</dt>
                            <dd class="text-muted-foreground">
                                Zarządza organizacją, zespołem, kartoteką
                                zwierząt i dokumentacją weterynaryjną.
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium">Weterynarz</dt>
                            <dd class="text-muted-foreground">
                                Prowadzi dokumentację weterynaryjną, zapisuje
                                zalecenia i pracuje z zadaniami opieki.
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium">Opiekun</dt>
                            <dd class="text-muted-foreground">
                                Pracuje w kartotece i zadaniach, czyta zalecenia
                                i dodaje obserwacje. Nie zmienia wpisów
                                medycznych ani uprawnień zespołu.
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-4 border-t pt-3 text-muted-foreground">
                        Blokada odbiera dostęp do panelu. Dane konta i autorstwo
                        wcześniejszych wpisów pozostają zachowane.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
