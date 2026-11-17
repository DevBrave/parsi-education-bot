<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUpRight,
    BookOpen,
    GraduationCap,
    LayoutDashboard,
    Library,
    Send,
    ShieldCheck,
} from '@lucide/vue';

defineProps<{ telegramUrl: string }>();
const page = usePage();
const isLoggedIn = computed(() => Boolean(page.props.auth.user));
const canAccessAdmin = computed(
    () => isLoggedIn.value && page.props.canAccessAdmin === true,
);

const features = [
    {
        icon: Library,
        label: 'Study resources',
        title: 'Your next study session, sorted.',
        description:
            'Find shared notes, exam materials, and useful resources through our Telegram bot.',
        tag: 'Learn together',
        color: 'purple',
    },
    {
        icon: BookOpen,
        label: 'Courses',
        title: 'Keep your courses close.',
        description:
            'Explore materials organised by course, so you can focus on what you are studying.',
        tag: 'Stay focused',
        color: 'blue',
    },
    {
        icon: GraduationCap,
        label: 'Programmes',
        title: 'Find your academic path.',
        description:
            'Navigate resources for your programme and academic year, all in one community.',
        tag: 'Built for students',
        color: 'green',
    },
];
</script>

<template>
    <Head title="PARSI | Student Hub" />
    <div class="home-page">
        <header class="site-header">
            <div class="header-inner">
                <a href="/" class="brand" aria-label="ParsiPolito home">
                    <img src="/ParsiLogo.jpg" alt="ParsiPolito logo" />
                    <span
                        >PARSI<span class="brand-accent"></span
                        ><small>THE STUDENT HUB</small></span
                    >
                </a>
                <nav aria-label="Main navigation">
                    <a href="#explore" class="nav-explore">Explore</a>
                    <a
                        v-if="!isLoggedIn"
                        href="/login"
                        class="button button-dark"
                        >Log in</a
                    >
                    <a
                        v-else-if="canAccessAdmin"
                        href="/admin"
                        class="button button-dark"
                        ><LayoutDashboard :size="16" /> Admin Panel
                        <ArrowUpRight :size="15"
                    /></a>
                    <a v-else href="/" class="button button-dark">My account</a>
                </nav>
            </div>
        </header>

        <main class="content">
            <div class="page-heading">
                <div>
                    <p class="eyebrow">YOUR COMMUNITY. YOUR CAMPUS.</p>
                    <h1>A little closer to everything you need.</h1>
                </div>
                <span class="community-label"
                    ><span></span> Made for Polito students</span
                >
            </div>

            <section class="welcome-banner" aria-labelledby="welcome-title">
                <div class="banner-copy">
                    <span class="banner-badge"
                        ><GraduationCap :size="15" /> Welcome to
                        ParsiPolito</span
                    >
                    <h2 id="welcome-title">
                        Study smarter.<br />Go further, together.
                    </h2>
                    <p>
                        Your community for sharing knowledge, finding study
                        materials, and making university life a little easier.
                    </p>
                    <div class="banner-actions">
                        <a
                            :href="telegramUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="button button-white"
                            ><Send :size="17" /> Open Telegram Bot
                            <ArrowUpRight :size="16" /></a
                        ><a href="#explore" class="banner-link"
                            >Explore the hub <ArrowDown :size="15"
                        /></a>
                    </div>
                </div>
                <div class="banner-art" aria-hidden="true">
                    <div class="orbit orbit-one"></div>
                    <div class="orbit orbit-two"></div>
                    <div class="logo-disc">
                        <img src="/ParsiLogo.jpg" alt="" />
                    </div>
                    <span class="floating-note note-top"
                        ><BookOpen :size="18" /> Share knowledge</span
                    ><span class="floating-note note-bottom"
                        ><GraduationCap :size="18" /> Grow together</span
                    >
                </div>
            </section>

            <section
                id="explore"
                class="explore-section"
                aria-labelledby="explore-title"
            >
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">A PLACE TO GET STARTED</p>
                        <h2 id="explore-title">Everything connects here.</h2>
                    </div>
                    <span>One community, plenty of possibilities.</span>
                </div>
                <div class="feature-grid">
                    <a
                        v-for="feature in features"
                        :key="feature.label"
                        :href="telegramUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="feature-card"
                        :class="feature.color"
                    >
                        <div class="card-top">
                            <span class="feature-icon"
                                ><component
                                    :is="feature.icon"
                                    :size="23" /></span
                            ><ArrowUpRight :size="19" class="card-arrow" />
                        </div>
                        <p class="card-label">{{ feature.label }}</p>
                        <h3>{{ feature.title }}</h3>
                        <p class="card-description">
                            {{ feature.description }}
                        </p>
                        <span class="card-tag">{{ feature.tag }}</span>
                    </a>
                </div>
            </section>

            <section
                v-if="canAccessAdmin"
                class="admin-callout"
                aria-labelledby="admin-title"
            >
                <span class="admin-icon"><ShieldCheck :size="25" /></span>
                <div>
                    <h2 id="admin-title">Behind the scenes</h2>
                    <p>
                        Manage resources, courses, and the community from the
                        admin dashboard.
                    </p>
                </div>
                <a href="/admin" class="button button-outline"
                    >Go to Admin Panel <ArrowUpRight :size="16"
                /></a>
            </section>
        </main>

        <footer class="site-footer">
            <div class="footer-inner">
                <a href="/" class="footer-brand"
                    ><img src="/ParsiLogo.jpg" alt="" /> ParsiPolito</a
                >
                <p>Shared knowledge. Stronger community.</p>
                <span>&copy; {{ new Date().getFullYear() }} ParsiPolito</span>
            </div>
        </footer>
    </div>
</template>

<style scoped src="../../css/student-hub.css"></style>
