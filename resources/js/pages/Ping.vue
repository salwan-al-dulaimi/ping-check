<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ping } from '@/routes';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { onMounted, ref } from 'vue';

const pings = ref([]);

onMounted(async () => {
    try {
        const response = await fetch(pingsAll(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });
        if (response.ok) {
            pings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch pings:', error);
    }
});

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
                            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Log in
            </Button>
            <div v-if="pings.length > 0" class="mt-4">
                <h3 class="text-lg font-semibold">Your Pings</h3>
                <ul class="list-disc list-inside">
                    <li v-for="ping in pings" :key="ping.id">
                        {{ ping.website_address }}
                    </li>
                </ul>
            </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >

            <PlaceholderPattern />
        </div>
    </div>
</template>