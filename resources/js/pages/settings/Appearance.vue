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
    <Head title="Wygląd aplikacji" />

    <h1 class="sr-only">Wygląd aplikacji</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Wygląd aplikacji"
            description="Wybierz motyw, w którym pracuje Ci się najwygodniej."
        />
        <AppearanceTabs />
    </div>
</template>
