<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    all as pingsAll,
    store as pingsStore,
    update as pingsUpdate,
    destroy as pingsDestroy,
} from '@/routes/pings';
import { ping } from '@/routes';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import InputError from '@/components/InputError.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { Pencil, Trash2 } from 'lucide-vue-next';

const pings = ref<
    Array<{
        id: number;
        site_name: string;
        website_address: string;
        status_code?: number | null;
        created_at: string;
        updated_at: string;
    }>
>([]);
const editingPing = ref<{
    id: number;
    site_name: string;
    website_address: string;
    status_code?: number | null;
    created_at?: string;
    updated_at?: string;
} | null>(null);
const siteName = ref('');
const website = ref('');
const processing = ref(false);
const errors = ref<{ site_name?: string; website_address?: string }>({});

const now = ref(Date.now());
let nowIntervalId: number | null = null;

const updateNow = () => {
    now.value = Date.now();
};

const formatRelativeTime = (dateString: string) => {
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
    if (diffDays === 1) {
        return 'a day ago';
    }

    return `${diffDays} days ago`;
};

const loadPings = async () => {
    try {
        const response = await fetch(pingsAll.url(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
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

onMounted(() => {
    loadPings();
    updateNow();
    nowIntervalId = window.setInterval(updateNow, 60_000);
});

onUnmounted(() => {
    if (nowIntervalId !== null) {
        clearInterval(nowIntervalId);
    }
});

const submitPing = async () => {
    const trimmedSiteName = siteName.value.trim();
    const trimmedWebsite = website.value.trim();

    errors.value = {};

    if (!trimmedSiteName) {
        errors.value.site_name = 'Please enter a site name.';
    }

    if (!trimmedWebsite) {
        errors.value.website_address = 'Please enter a website address.';
    }

    if (errors.value.site_name || errors.value.website_address) {
        return;
    }

    processing.value = true;

    if (!trimmedWebsite.includes('.') || trimmedWebsite.includes(' ')) {
        errors.value.website_address =
            'Please enter a valid website address with a dot, for example google.com.';
        processing.value = false;
        return;
    }

    try {
        const isUpdating = editingPing.value !== null;
        const url = isUpdating
            ? pingsUpdate.url(editingPing.value!.id)
            : pingsStore.url();
        const method = isUpdating ? 'PATCH' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                site_name: siteName.value,
                website_address: trimmedWebsite,
            }),
        });

        if (response.ok) {
            const savedPing = await response.json();

            if (isUpdating) {
                pings.value = pings.value.map((item) =>
                    item.id === savedPing.id ? savedPing : item,
                );
                resetForm();
            } else {
                pings.value.unshift(savedPing);
                siteName.value = '';
                website.value = '';
                errors.value = {};
            }
        } else if (response.status === 422) {
            const responseData = await response.json();
            errors.value = responseData.errors ?? {};
        } else {
            console.error('Failed to save ping:', await response.text());
        }
    } catch (error) {
        console.error('Failed to save ping:', error);
    } finally {
        processing.value = false;
    }
};

const resetForm = () => {
    editingPing.value = null;
    siteName.value = '';
    website.value = '';
    errors.value = {};
};

const startEditing = (pingItem: {
    id: number;
    site_name: string;
    website_address: string;
    status_code?: number | null;
}) => {
    editingPing.value = pingItem;
    siteName.value = pingItem.site_name;
    website.value = pingItem.website_address;
    errors.value = {};
};

const deletePing = async (pingItem: {
    id: number;
    site_name: string;
    website_address: string;
    status_code?: number | null;
}) => {
    if (!confirm(`Delete ${pingItem.site_name}?`)) {
        return;
    }

    try {
        const response = await fetch(pingsDestroy.url(pingItem.id), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            pings.value = pings.value.filter((item) => item.id !== pingItem.id);
            if (editingPing.value?.id === pingItem.id) {
                resetForm();
            }
        } else {
            console.error('Failed to delete ping:', await response.text());
        }
    } catch (error) {
        console.error('Failed to delete ping:', error);
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

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
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
                <InputError :message="errors.site_name" />
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
                <InputError :message="errors.website_address" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                :tabindex="3"
            >
                <Spinner v-if="processing" />
                {{ editingPing ? 'Update Ping' : 'Save Ping' }}
            </Button>

            <button
                v-if="editingPing"
                type="button"
                class="mt-2 inline-flex w-full items-center justify-center rounded-md border border-border bg-transparent px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="resetForm"
            >
                Cancel editing
            </button>
        </form>

        <div v-if="pings.length > 0" class="mt-4">
            <h3 class="text-lg font-semibold">Your Pings</h3>
            <ul class="space-y-3">
                <li
                    v-for="pingItem in pings"
                    :key="pingItem.id"
                    class="rounded-xl border border-border bg-card p-4 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <p class="font-semibold">
                                {{ pingItem.site_name }}
                            </p>
                            <p
                                class="text-sm text-slate-600 dark:text-slate-400"
                            >
                                {{ pingItem.website_address }}
                            </p>
                            <p
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                Status:
                                <span
                                    class="font-medium"
                                    :class="
                                        pingItem.status_code == 200
                                            ? 'text-green-500'
                                            : 'text-red-500'
                                    "
                                >
                                    {{ pingItem.status_code ?? 'Failed' }}
                                </span>
                            </p>
                            <p
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                Created: {{ formatRelativeTime(pingItem.created_at) }}
                            </p>
                            <p
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                Updated: {{ formatRelativeTime(pingItem.updated_at) }}
                            </p>
                        </div>
                        <div class="mt-3 flex items-center gap-2 md:mt-0">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-md bg-amber-400 px-3 py-2 text-sm font-medium text-slate-950 transition hover:bg-amber-300"
                                @click="startEditing(pingItem)"
                            >
                                <Pencil class="h-4 w-4" />
                                Edit
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-md bg-rose-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-rose-400"
                                @click="deletePing(pingItem)"
                            >
                                <Trash2 class="h-4 w-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <div
            class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
