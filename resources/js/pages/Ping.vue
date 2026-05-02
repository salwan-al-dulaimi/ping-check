<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { all as pingsAll, store as pingsStore } from '@/routes/pings';
import { ping } from '@/routes';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { onMounted, ref } from 'vue';

const pings = ref<Array<{ id: number; site_name: string; website_address: string }>>([]);
const siteName = ref('');
const website = ref('');
const processing = ref(false);

const loadPings = async () => {
    try {
        const response = await fetch(pingsAll.url(), {
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
};

const submitPing = async () => {
    if (!siteName.value || !website.value) {
        return;
    }

    processing.value = true;

    try {
        const response = await fetch(pingsStore.url(), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                site_name: siteName.value,
                website_address: website.value,
            }),
        });

        if (response.ok) {
            const createdPing = await response.json();
            pings.value.unshift(createdPing);
            siteName.value = '';
            website.value = '';
        } else {
            console.error('Failed to save ping:', await response.text());
        }
    } catch (error) {
        console.error('Failed to save ping:', error);
    } finally {
        processing.value = false;
    }
};

onMounted(loadPings);

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

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <form @submit.prevent="submitPing" class="grid gap-4">
            <div class="grid gap-2">
                <Label for="site-name">Site name</Label>
                <Input
                    id="site-name"
                    type="text"
                    name="site_name"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="off"
                    placeholder="Google"
                    v-model="siteName"
                />
            </div>

            <div class="grid gap-2">
                <Label for="website">Website address</Label>
                <Input
                    id="website"
                    type="text"
                    name="website_address"
                    required
                    :tabindex="2"
                    autocomplete="off"
                    placeholder="www.google.com"
                    v-model="website"
                />
            </div>

            <Button type="submit" class="mt-2 w-full" :disabled="processing" :tabindex="3">
                <Spinner v-if="processing" />
                Save Ping
            </Button>
        </form>

        <div v-if="pings.length > 0" class="mt-4">
            <h3 class="text-lg font-semibold">Your Pings</h3>
            <ul class="list-disc list-inside space-y-2">
                <li v-for="pingItem in pings" :key="pingItem.id">
                    <strong>{{ pingItem.site_name }}</strong> – {{ pingItem.website_address }}
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