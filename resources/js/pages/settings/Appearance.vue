<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Wygląd aplikacji',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <div class="space-y-6">
        <Head title="Wygląd aplikacji" />

        <h2 class="sr-only">Wygląd aplikacji</h2>

        <div class="settings-card">
            <Heading
                variant="small"
                title="Wygląd aplikacji"
                description="Wybierz motyw, w którym pracuje Ci się najwygodniej."
            />
            <AppearanceTabs />
            <p class="text-sm leading-relaxed text-muted-foreground">
                Zmiana zapisuje się automatycznie w tej przeglądarce. Motyw
                systemowy dopasowuje się do ustawień Twojego urządzenia.
            </p>
        </div>
    </div>
</template>
