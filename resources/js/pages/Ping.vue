<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ping } from '@/routes';
import {
    all as websitesAll,
    store as websitesStore,
    update as websitesUpdate,
    destroy as websitesDestroy,
} from '@/routes/websites';

const websites = ref<
    Array<{
        id: number;
        name: string;
        url: string;
        check_interval: number;
        status: string;
        status_code?: number | null;
        last_checked_at?: string | null;
        created_at: string;
        updated_at: string;
    }>
>([]);
const editingWebsite = ref<{
    id: number;
    name: string;
    url: string;
    check_interval: number;
} | null>(null);
const name = ref('');
const url = ref('');
const checkInterval = ref(1);
const processing = ref(false);
const errors = ref<{ name?: string; url?: string; check_interval?: string }>({});

const now = ref(Date.now());
let nowIntervalId: number | null = null;
let refreshIntervalId: number | null = null;

const updateNow = () => {
    now.value = Date.now();
};

const formatRelativeTime = (dateString: string | null | undefined) => {
    if (!dateString) {
        return 'never';
    }

    const date = new Date(dateString);
    const diffSeconds = Math.floor((now.value - date.getTime()) / 1000);

    if (diffSeconds < 60) {
        return 'moments ago';
    }

    const diffMinutes = Math.floor(diffSeconds / 60);

    if (diffMinutes < 60) {
        return diffMinutes === 1
            ? 'a minute ago'
            : `${diffMinutes} minutes ago`;
    }

    const diffHours = Math.floor(diffMinutes / 60);

    if (diffHours < 24) {
        return diffHours === 1
            ? 'an hour ago'
            : `${diffHours} hours ago`;
    }

    const diffDays = Math.floor(diffHours / 24);

    return diffDays === 1 ? 'a day ago' : `${diffDays} days ago`;
};

const statusClasses = (status: string) => {
    return status === 'online'
        ? 'inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700'
        : 'inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700';
};

const loadWebsites = async () => {
    try {
        const response = await fetch(websitesAll.url(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            websites.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch websites:', error);
    }
};

const resetForm = () => {
    editingWebsite.value = null;
    name.value = '';
    url.value = '';
    checkInterval.value = 1;
    errors.value = {};
};

const submitWebsite = async () => {
    const trimmedName = name.value.trim();
    const trimmedUrl = url.value.trim();

    errors.value = {};

    if (!trimmedName) {
        errors.value.name = 'Please enter a website name.';
    }

    if (!trimmedUrl) {
        errors.value.url = 'Please enter a website URL.';
    }

    if (!checkInterval.value || checkInterval.value < 1) {
        errors.value.check_interval = 'Please enter an interval of at least 1 hour.';
    }

    if (errors.value.name || errors.value.url || errors.value.check_interval) {
        return;
    }

    if (!trimmedUrl.includes('.') || trimmedUrl.includes(' ')) {
        errors.value.url = 'Please enter a valid website URL with a dot, for example example.com.';

        return;
    }

    processing.value = true;

    try {
        const isUpdating = editingWebsite.value !== null;
        const endpoint = isUpdating
            ? websitesUpdate.url(editingWebsite.value!.id)
            : websitesStore.url();
        const method = isUpdating ? 'PATCH' : 'POST';

        const response = await fetch(endpoint, {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: trimmedName,
                url: trimmedUrl,
                check_interval: checkInterval.value,
            }),
        });

        if (response.ok) {
            const savedWebsite = await response.json();

            if (isUpdating) {
                websites.value = websites.value.map((item) =>
                    item.id === savedWebsite.id ? savedWebsite : item,
                );
                resetForm();
            } else {
                websites.value.unshift(savedWebsite);
                resetForm();
            }
        } else if (response.status === 422) {
            const responseData = await response.json();
            errors.value = responseData.errors ?? {};
        } else {
            console.error('Failed to save website:', await response.text());
        }
    } catch (error) {
        console.error('Failed to save website:', error);
    } finally {
        processing.value = false;
    }
};

const editWebsite = (websiteItem: {
    id: number;
    name: string;
    url: string;
    check_interval: number;
}) => {
    editingWebsite.value = websiteItem;
    name.value = websiteItem.name;
    url.value = websiteItem.url;
    checkInterval.value = websiteItem.check_interval;
    errors.value = {};
};

const deleteWebsite = async (websiteItem: { id: number; name: string }) => {
    if (!confirm(`Delete ${websiteItem.name}?`)) {
        return;
    }

    try {
        const response = await fetch(websitesDestroy.url(websiteItem.id), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            websites.value = websites.value.filter((item) => item.id !== websiteItem.id);

            if (editingWebsite.value?.id === websiteItem.id) {
                resetForm();
            }
        } else {
            console.error('Failed to delete website:', await response.text());
        }
    } catch (error) {
        console.error('Failed to delete website:', error);
    }
};

onMounted(() => {
    loadWebsites();
    updateNow();
    nowIntervalId = window.setInterval(updateNow, 60_000);
    refreshIntervalId = window.setInterval(loadWebsites, 30_000);
});

onUnmounted(() => {
    if (nowIntervalId !== null) {
        clearInterval(nowIntervalId);
    }

    if (refreshIntervalId !== null) {
        clearInterval(refreshIntervalId);
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

    <div class="space-y-6">
        <form
            @submit.prevent="submitWebsite"
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-950"
        >
            <div class="grid gap-6 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="website-name">Website name</Label>
                    <Input
                        id="website-name"
                        type="text"
                        name="name"
                        required
                        autocomplete="off"
                        placeholder="Google"
                        v-model="name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="website-url">Website URL</Label>
                    <Input
                        id="website-url"
                        type="text"
                        name="url"
                        required
                        autocomplete="off"
                        placeholder="https://www.google.com"
                        v-model="url"
                    />
                    <InputError :message="errors.url" />
                </div>

                <div class="space-y-2">
                    <Label for="website-interval">Check interval (hours)</Label>
                    <Input
                        id="website-interval"
                        type="number"
                        name="check_interval"
                        required
                        min="1"
                        v-model="checkInterval"
                    />
                    <InputError :message="errors.check_interval" />
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <Button type="submit" class="w-full sm:w-auto" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{ editingWebsite ? 'Update Website' : 'Add Website' }}
                </Button>

                <button
                    v-if="editingWebsite"
                    type="button"
                    class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-transparent px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 sm:w-auto"
                    @click="resetForm"
                >
                    Cancel edit
                </button>
            </div>
        </form>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-950">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Monitored Websites</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Auto-refreshes every 30 seconds.</p>
                </div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Updated {{ formatRelativeTime(new Date().toISOString()) }}</div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700 dark:divide-slate-700 dark:text-slate-200">
                    <thead class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-200">
                        <tr>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">URL</th>
                            <th class="px-4 py-3 font-medium">Interval</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Last checked</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr v-for="websiteItem in websites" :key="websiteItem.id">
                            <td class="px-4 py-4 font-medium">{{ websiteItem.name }}</td>
                            <td class="px-4 py-4 break-all text-slate-600 dark:text-slate-300">{{ websiteItem.url }}</td>
                            <td class="px-4 py-4">{{ websiteItem.check_interval }}h</td>
                            <td class="px-4 py-4">
                                <span :class="statusClasses(websiteItem.status)">
                                    {{ websiteItem.status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">{{ formatRelativeTime(websiteItem.last_checked_at) }}</td>
                            <td class="px-4 py-4 space-x-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-700"
                                    @click="editWebsite(websiteItem)"
                                >
                                    <Pencil class="h-4 w-4" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md border border-rose-200 bg-transparent px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                    @click="deleteWebsite(websiteItem)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="websites.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                <!-- <PlaceholderPattern /> -->
                                <div class="mt-4">No websites added yet. Add one above to start monitoring.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
