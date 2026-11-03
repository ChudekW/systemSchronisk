<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show } from '@/routes/animals';
import { index as tasks } from '@/routes/animals/tasks';
import { index as adoptions } from '@/routes/animals/adoptions';
import { index as health } from '@/routes/animals/health';
defineProps<{
    animalId: number;
    active: 'details' | 'tasks' | 'health' | 'adoptions';
}>();
</script>
<template>
    <nav
        class="flex flex-wrap gap-2 border-b pb-3"
        aria-label="Karta zwierzęcia"
    >
        <Link
            v-for="tab in [
                {
                    key: 'details',
                    label: 'Dane zwierzęcia',
                    href: show(animalId),
                },
                {
                    key: 'tasks',
                    label: 'Opieka i zadania',
                    href: tasks(animalId),
                },
                { key: 'health', label: 'Zdrowie', href: health(animalId) },
                {
                    key: 'adoptions',
                    label: 'Adopcje',
                    href: adoptions(animalId),
                },
            ]"
            :key="tab.key"
            :href="tab.href"
            class="rounded-lg px-4 py-2 text-sm transition-colors"
            :class="
                active === tab.key
                    ? 'bg-primary text-primary-foreground'
                    : 'hover:bg-accent'
            "
            :aria-current="active === tab.key ? 'page' : undefined"
            >{{ tab.label }}</Link
        >
    </nav>
</template>
