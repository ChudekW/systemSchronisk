<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-if="message" role="alert">
        <p class="text-sm text-destructive">
            {{ message }}
        </p>
    </div>
</template>
