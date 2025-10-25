<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { PropType } from 'vue';
import { computed, onMounted, ref } from 'vue';

interface ImageItem {
    preview_url?: string;
    full_url?: string;
    url?: string;
    image?: string;
    is_cover?: boolean;
}

const emit = defineEmits<{ (e: 'update:open', value: boolean): void }>();

const props = defineProps({
    detail: { type: Object, required: true },
    title: { type: String, required: true },
    images: { type: Array as PropType<ImageItem[]>, default: () => [] },
    open: { type: Boolean, required: true },
});
const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});
onMounted(() => {
    console.log(props.title)
});
const detail = computed(() => props.detail || {});
const images = computed(() => props.images || []);
const activeIndex = ref(0);

function imageSrc(item: any) {
    return (
        item?.preview_url || item?.full_url || item?.url || item?.image || null
    );
}

function formatDate(dateStr: string | null | undefined) {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleString();
    } catch {
        return dateStr;
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="max-h-[95vh] overflow-y-auto rounded-xl bg-background p-0 sm:max-w-4xl"
        >
            <DialogHeader class="border-b px-6 py-4">
                <DialogTitle
                    class="flex items-center gap-2 text-xl font-semibold"
                >
                    🗺️ Detail Destination
                </DialogTitle>
                <DialogDescription class="text-sm text-muted-foreground">
                    View complete information about this destination.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 p-6">
                <div
                    v-if="detail"
                    class="grid grid-cols-1 gap-6 md:grid-cols-3"
                >
                    <!-- Image Gallery -->
                    <div class="md:col-span-1">
                        <div
                            class="relative flex h-64 w-full items-center justify-center overflow-hidden rounded-lg bg-muted"
                        >
                            <template v-if="images.length > 0">
                                <img
                                    :src="imageSrc(images[activeIndex])"
                                    class="h-full w-full object-cover transition-all duration-300 hover:scale-105"
                                    alt="Destination image"
                                />
                                <div
                                    class="absolute right-2 bottom-2 rounded-md bg-black/40 px-2 py-1 text-xs text-white"
                                    v-if="images[activeIndex]?.is_cover"
                                >
                                    Cover images
                                </div>
                                <button
                                    v-if="activeIndex > 0"
                                    @click="activeIndex--"
                                    class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-black/40 p-1 text-white hover:bg-black/60"
                                >
                                    ‹
                                </button>
                                <button
                                    v-if="activeIndex < images.length - 1"
                                    @click="activeIndex++"
                                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-black/40 p-1 text-white hover:bg-black/60"
                                >
                                    ›
                                </button>
                            </template>

                            <div
                                v-else
                                class="text-center text-sm text-muted-foreground select-none"
                            >
                                No image available
                            </div>
                        </div>

                        <!-- Thumbnails -->
                        <div
                            v-if="images.length > 1"
                            class="mt-3 grid grid-cols-4 gap-2"
                        >
                            <button
                                v-for="(img, idx) in images"
                                :key="idx"
                                @click.prevent="activeIndex = idx"
                                class="h-16 w-full overflow-hidden rounded-md border transition-all hover:ring-2 hover:ring-primary"
                                :class="
                                    idx === activeIndex
                                        ? 'ring-2 ring-primary'
                                        : ''
                                "
                            >
                                <img
                                    :src="imageSrc(img)"
                                    class="h-full w-full object-cover"
                                    alt="Thumbnail"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Details -->
                    <!-- RIGHT: Detail Info (replace your old block) -->
                    <div class="space-y-6 md:col-span-2">
                        <!-- Title -->
                        <div>
                            <h3
                                class="text-xl font-semibold tracking-tight capitalize"
                            >
                                {{
                                    title || 'Untitled Destination'
                                }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Destination information
                            </p>
                        </div>

                        <!-- Definition list: clean, aligned, responsive -->
                        <dl
                            class="grid grid-cols-1 gap-x-10 gap-y-5 text-sm sm:grid-cols-2"
                        >
                            <!-- Address -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">Address</dt>
                                <dd class="leading-relaxed font-medium">
                                    <span v-if="detail.address">{{
                                        detail.address
                                    }}</span>
                                    <span v-else>—</span>
                                </dd>
                            </div>

                            <!-- Village -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">Village</dt>
                                <dd class="font-medium">
                                    {{ detail.village?.name || '—' }}
                                </dd>
                            </div>

                            <!-- District -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">District</dt>
                                <dd class="font-medium">
                                    {{ detail.district?.name || '—' }}
                                </dd>
                            </div>

                            <!-- Maps -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">Maps</dt>
                                <dd class="font-medium">
                                    <a
                                        v-if="detail.maps_link"
                                        :href="detail.maps_link"
                                        target="_blank"
                                        rel="noopener"
                                        class="underline decoration-primary/50 underline-offset-4 hover:decoration-primary"
                                    >
                                        Open in Maps
                                    </a>
                                    <span v-else>—</span>
                                </dd>
                            </div>

                            <!-- Ticket price -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">
                                    Ticket price
                                </dt>
                                <dd class="font-medium">
                                    {{ detail.ticket_price || '—' }}
                                </dd>
                            </div>

                            <!-- Open / Close -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">
                                    Operating hours
                                </dt>
                                <dd class="font-medium">
                                    <span>{{ detail.open_hours || '—' }}</span>
                                    <span class="mx-2 text-muted-foreground"
                                        >–</span
                                    >
                                    <span>{{ detail.close_hours || '—' }}</span>
                                </dd>
                            </div>

                            <!-- Phone -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">Phone</dt>
                                <dd class="font-medium">
                                    <a
                                        v-if="detail.phone"
                                        :href="`tel:${detail.phone}`"
                                        class="underline-offset-4 hover:underline"
                                    >
                                        {{ detail.phone }}
                                    </a>
                                    <span v-else>—</span>
                                </dd>
                            </div>

                            <!-- PIC -->
                            <div class="space-y-1">
                                <dt class="text-muted-foreground">PIC</dt>
                                <dd class="font-medium">
                                    {{ detail.pic || '—' }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Status badge -->
                        <div class="border-t pt-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-muted-foreground"
                                    >Status</span
                                >
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        detail.status === 'published'
                                            ? 'bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/30'
                                            : 'bg-zinc-500/10 text-zinc-300 ring-1 ring-zinc-500/30'
                                    "
                                >
                                    {{ detail.status || '—' }}
                                </span>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div
                            class="grid grid-cols-1 gap-2 border-t pt-4 text-xs text-muted-foreground sm:grid-cols-2"
                        >
                            <div>
                                <span class="font-medium">Created:</span>
                                {{ formatDate(detail.created_at) }}
                            </div>
                            <div>
                                <span class="font-medium">Updated:</span>
                                {{ formatDate(detail.updated_at) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No destination detail available.
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
