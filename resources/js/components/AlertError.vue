<script setup lang="ts">
import { vAutoAnimate } from '@formkit/auto-animate';
import { AlertCircle } from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    errors: string[];
    title?: string;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Nie udało się wykonać operacji. Spróbuj ponownie.',
});

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
</script>

<template>
    <Alert variant="destructive">
        <AlertCircle class="size-4" />
        <AlertTitle>{{ title }}</AlertTitle>
        <AlertDescription>
            <ul
                v-auto-animate="{ duration: 180, easing: 'ease-out' }"
                class="list-inside list-disc text-sm"
            >
                <li v-for="error in uniqueErrors" :key="error">
                    {{ error }}
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
