<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ping } from '@/routes';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ping',
                href: ping(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Ping" />
    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
    <div class="grid gap-2">
                    <Label for="website">Ping website</Label>
                    <Input
                        id="website"
                        type="website"
                        name="website"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="website"
                        placeholder="www.google.com"
                    />
                </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >



            <PlaceholderPattern />
        </div>
    </div>
</template>