<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';

type Feature = { icon: string; title: string; desc: string };
type Tile = { title: string; desc: string; emoji: string };

const features: Feature[] = [
    { icon: 'mapPin', title: 'Temukan Destinasi', desc: 'Jelajahi tempat wisata, kuliner, dan budaya.' },
    { icon: 'calendar', title: 'Rencanakan Perjalanan', desc: 'Atur itinerary dan simpan favoritmu.' },
    { icon: 'users', title: 'Komunitas Traveler', desc: 'Ikuti ulasan dan rekomendasi terbaru.' },
];

const tiles: Tile[] = [
    { title: 'Pantai', desc: 'Pasir putih & sunset', emoji: '🏖️' },
    { title: 'Gunung', desc: 'Pendakian & camping', emoji: '⛰️' },
    { title: 'Air Terjun', desc: 'Segar & menenangkan', emoji: '🌊' },
    { title: 'Kuliner', desc: 'Rasa lokal terbaik', emoji: '🍜' },
    { title: 'Sejarah', desc: 'Cagar budaya', emoji: '🏛️' },
    { title: 'Keluarga', desc: 'Ramah anak', emoji: '👨‍👩‍👧‍👦' },
];
</script>

<template>
    <Head title="Beranda" />

    <!-- Navbar -->
    <header class="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary/10 text-primary">ES</div>
                <span class="text-lg font-semibold tracking-tight">ExploreSewu</span>
            </div>
            <nav class="hidden items-center gap-6 text-sm md:flex">
                <a href="#fitur" class="text-muted-foreground hover:text-foreground">Fitur</a>
                <a href="#kategori" class="text-muted-foreground hover:text-foreground">Kategori</a>
                <a href="#tentang" class="text-muted-foreground hover:text-foreground">Tentang</a>
            </nav>
            <div class="flex items-center gap-2">
                <template v-if="$page.props.auth?.user">
                    <Link :href="dashboard()">
                        <Button size="sm" variant="outline">Dashboard</Button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="login()">
                        <Button class="hover:cursor-pointer" size="sm" variant="ghost">Masuk</Button>
                    </Link>
                    <Link :href="register()">
                        <Button class="hover:cursor-pointer" size="sm">Daftar</Button>
                    </Link>
                </template>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="relative isolate overflow-hidden bg-gradient-to-b from-background to-muted/40">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 py-16 md:grid-cols-2 md:py-24">
            <div class="flex flex-col justify-center gap-6">
                <h1 class="text-balance text-4xl font-semibold leading-tight sm:text-5xl">
                    Jelajahi Keindahan Nusantara bersama ExploreSewu
                </h1>
                <p class="text-muted-foreground">
                    Temukan destinasi terbaik, rencanakan perjalanan, dan bagikan pengalamanmu
                    bersama komunitas traveler Indonesia.
                </p>
                <div class="flex w-full items-center gap-2">
                    <Input class="h-11" placeholder="Cari destinasi, kota, atau kategori..." />
                    <Button class="h-11 px-5">
                        <Icon name="search" class="mr-2" /> Cari
                    </Button>
                </div>
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                    <span class="flex items-center gap-2"><Icon name="sparkles" /> Rekomendasi harian</span>
                    <span class="flex items-center gap-2"><Icon name="shieldCheck" /> Konten terverifikasi</span>
                    <span class="flex items-center gap-2"><Icon name="heart" /> Disukai komunitas</span>
                </div>
            </div>

            <div class="relative">
                <div class="aspect-[4/3] w-full overflow-hidden rounded-xl border bg-gradient-to-br from-primary/10 via-transparent to-secondary/10">
                    <div class="absolute inset-0 grid grid-cols-3 gap-3 p-3">
                        <div v-for="(t, i) in tiles" :key="i" class="rounded-lg border bg-card/60 p-4 shadow-sm backdrop-blur">
                            <div class="text-3xl">{{ t.emoji }}</div>
                            <div class="mt-2 font-medium">{{ t.title }}</div>
                            <div class="text-sm text-muted-foreground">{{ t.desc }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="fitur" class="mx-auto max-w-6xl px-4 py-12 md:py-16">
        <div class="grid gap-4 md:grid-cols-3">
            <Card v-for="(f, idx) in features" :key="idx">
                <CardHeader class="px-6 pt-5 pb-1">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Icon :name="f.icon" class="h-5 w-5 text-primary" />
                        {{ f.title }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-6 pb-6 pt-2 text-sm text-muted-foreground">
                    {{ f.desc }}
                </CardContent>
            </Card>
        </div>
    </section>

    <!-- Categories / Featured -->
    <section id="kategori" class="mx-auto max-w-6xl px-4 pb-12 md:pb-16">
        <div class="mb-6 flex items-end justify-between">
            <h2 class="text-xl font-semibold">Jelajahi Kategori Populer</h2>
            <a href="#" class="text-sm text-primary hover:underline">Lihat semua</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="(t, i) in tiles" :key="i">
                <CardContent class="flex items-center gap-4 px-6 py-5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-md bg-primary/10 text-2xl">
                        {{ t.emoji }}
                    </div>
                    <div class="space-y-1">
                        <div class="font-medium">{{ t.title }}</div>
                        <div class="text-sm text-muted-foreground">{{ t.desc }}</div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </section>

    <!-- CTA Newsletter / App promo -->
    <section id="tentang" class="mx-auto max-w-6xl px-4 pb-16">
        <Card>
            <CardContent class="flex flex-col items-start gap-4 px-6 py-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-lg font-semibold">Dapatkan rekomendasi terbaru tiap minggu</div>
                    <div class="text-sm text-muted-foreground">Berlangganan newsletter kami — gratis dan bisa berhenti kapan saja.</div>
                </div>
                <div class="flex w-full max-w-md items-center gap-2">
                    <Input type="email" placeholder="Alamat email" />
                    <Button>Berlangganan</Button>
                </div>
            </CardContent>
        </Card>
    </section>

    <!-- Footer -->
    <footer class="border-t py-10">
        <div class="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-4 px-4 text-sm text-muted-foreground md:flex-row">
            <div>© {{ new Date().getFullYear() }} ExploreSewu. Semua hak dilindungi.</div>
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-foreground">Kebijakan Privasi</a>
                <a href="#" class="hover:text-foreground">Syarat & Ketentuan</a>
                <a href="#" class="hover:text-foreground">Kontak</a>
            </div>
        </div>
    </footer>
    
</template>
