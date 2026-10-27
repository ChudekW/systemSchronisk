<script setup lang="ts">
import { Check, Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Jasny' },
    { value: 'dark', Icon: Moon, label: 'Ciemny' },
    { value: 'system', Icon: Monitor, label: 'Systemowy' },
] as const;
</script>

<template>
    <div
        class="grid gap-3 sm:grid-cols-3"
        role="group"
        aria-label="Motyw aplikacji"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            @click="updateAppearance(value)"
            :class="[
                'flex min-w-0 flex-col gap-3 rounded-xl border p-3 text-left transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-card motion-reduce:transition-none',
                appearance === value
                    ? 'border-primary bg-accent/50'
                    : 'border-border hover:border-primary/40 hover:bg-accent/20',
            ]"
        >
            <span
                aria-hidden="true"
                class="flex h-24 w-full overflow-hidden rounded-lg border border-black/10"
                :class="
                    value === 'dark'
                        ? 'bg-[#17231f]'
                        : value === 'system'
                          ? 'bg-linear-to-r from-[#f7f5ef] from-50% to-[#17231f] to-50%'
                          : 'bg-[#f7f5ef]'
                "
            >
                <span
                    class="flex w-7 shrink-0 flex-col gap-2 border-r border-black/5 p-2"
                    :class="value === 'dark' ? 'bg-[#20312a]' : 'bg-[#e4eee5]'"
                >
                    <span class="size-3 rounded bg-[#246b5a]"></span>
                    <span class="h-1 rounded bg-[#879a90]"></span>
                    <span class="h-1 rounded bg-[#879a90]"></span>
                </span>
                <span class="flex flex-1 flex-col gap-2 p-3">
                    <span class="h-2 w-2/3 rounded bg-[#879a90]"></span>
                    <span
                        class="flex-1 rounded border border-black/5"
                        :class="
                            value === 'dark'
                                ? 'bg-[#334b3e]'
                                : value === 'system'
                                  ? 'bg-linear-to-r from-white from-40% to-[#334b3e] to-40%'
                                  : 'bg-white'
                        "
                    ></span>
                </span>
            </span>
            <span class="flex w-full items-center gap-2 text-sm font-medium">
                <component :is="Icon" class="size-4" aria-hidden="true" />
                <span class="flex-1">{{ label }}</span>
                <Check
                    v-if="appearance === value"
                    class="size-4 text-primary"
                    aria-hidden="true"
                />
            </span>
        </button>
    </div>
</template>
