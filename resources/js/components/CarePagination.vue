<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
defineProps<{
    pages: {
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();
</script>
<template>
    <nav
        v-if="pages.last_page > 1"
        class="flex flex-wrap items-center gap-4"
        aria-label="Strony listy"
    >
        <Button v-if="pages.prev_page_url" variant="outline" as-child
            ><Link :href="pages.prev_page_url" preserve-scroll
                >Poprzednia</Link
            ></Button
        >
        <span class="text-sm"
            >{{ pages.current_page }} / {{ pages.last_page }}</span
        >
        <Button v-if="pages.next_page_url" variant="outline" as-child
            ><Link :href="pages.next_page_url" preserve-scroll
                >Następna</Link
            ></Button
        >
    </nav>
</template>
