<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-8 bg-background px-4 py-10 sm:px-6"
    >
        <div class="flex flex-col items-center gap-3 text-center">
            <Link
                :href="home()"
                class="rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
            >
                <AppLogo class="public-logo-transition" />
            </Link>
            <p class="text-sm text-muted-foreground">
                Wszystko dla lepszego życia zwierząt.
            </p>
        </div>
        <div
            class="page-transition-content w-full max-w-md rounded-2xl border bg-card p-6 shadow-sm sm:p-8"
        >
            <div class="flex flex-col gap-8">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ title }}
                    </h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
