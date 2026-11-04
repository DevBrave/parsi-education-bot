<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
    <Head title="ParsiPolito | Student Hub" />
    <div class="home-page">
        <header class="site-header">
            <div class="header-inner">
                <a href="/" class="brand" aria-label="ParsiPolito home">
                    <img src="/ParsiLogo.jpg" alt="ParsiPolito logo" />
                    <span
                        >Parsi<span class="brand-accent">Polito</span
                        ><small>THE STUDENT HUB</small></span
                    >
                </a>
                <nav aria-label="Main navigation">
                    <a href="#explore" class="nav-explore">Explore</a>
                    <a href="/admin" class="button button-dark"
                        ><LayoutDashboard :size="16" /> Admin Panel
                        <ArrowUpRight :size="15"
                    /></a>
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

            <section class="admin-callout" aria-labelledby="admin-title">
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

<style scoped>
.home-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f6f7fb;
    color: #182238;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
    font-size: 14px;
    -webkit-font-smoothing: antialiased;
}
.home-page a {
    text-decoration: none;
}
.site-header {
    background: #fff;
    border-bottom: 1px solid #e8eaf1;
}
.header-inner,
.content,
.footer-inner {
    width: min(1180px, calc(100% - 64px));
    margin: auto;
}
.header-inner {
    min-height: 90px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}
.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #192238;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.8px;
}
.brand img {
    width: 46px;
    height: 46px;
    object-fit: cover;
    border-radius: 50%;
}
.brand-accent {
    color: #5346ca;
}
.brand small {
    display: block;
    font-size: 9px;
    letter-spacing: 2.3px;
    color: #8a91a1;
    font-weight: 600;
    margin-top: 1px;
}
nav {
    display: flex;
    align-items: center;
    gap: 30px;
}
.nav-explore {
    color: #656d80;
    font-weight: 600;
}
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border-radius: 9px;
    padding: 12px 17px;
    font-size: 12px;
    font-weight: 650;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
.button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px #20205015;
}
.button-dark {
    background: #212343;
    color: white;
}
.content {
    flex: 1;
    padding-top: 38px;
    padding-bottom: 42px;
}
.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 26px;
}
.eyebrow {
    font-size: 10px;
    font-weight: 750;
    letter-spacing: 1.8px;
    color: #9298aa;
    margin: 0 0 8px;
}
h1 {
    font-size: 22px;
    letter-spacing: -0.7px;
    font-weight: 650;
    margin: 0;
}
.community-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #7c8495;
    white-space: nowrap;
}
.community-label span {
    width: 6px;
    height: 6px;
    background: #24a987;
    border-radius: 50%;
}
.welcome-banner {
    display: flex;
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    min-height: 348px;
    background: linear-gradient(115deg, #211e52, #373083 65%, #5146b4);
    color: white;
    padding: 38px 44px;
    box-shadow: 0 8px 24px #312e8110;
}
.banner-copy {
    max-width: 560px;
    position: relative;
    z-index: 1;
}
.banner-badge {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    border: 1px solid #ffffff25;
    background: #ffffff0d;
    padding: 6px 11px;
    border-radius: 30px;
    font-size: 10px;
    color: #e1dffa;
}
.banner-copy h2 {
    font-size: 43px;
    line-height: 1.15;
    letter-spacing: -1.5px;
    font-weight: 700;
    margin: 19px 0 14px;
}
.banner-copy > p {
    max-width: 420px;
    color: #c7c6e5;
    font-size: 12px;
    line-height: 1.9;
}
.banner-actions {
    display: flex;
    gap: 23px;
    align-items: center;
    margin-top: 24px;
}
.button-white {
    background: white;
    color: #363079;
}
.banner-link {
    display: flex;
    gap: 8px;
    align-items: center;
    font-size: 11px;
    color: #e4e2fb;
}
.banner-art {
    position: absolute;
    width: 340px;
    height: 340px;
    right: 40px;
    top: 10px;
    display: grid;
    place-items: center;
}
.orbit {
    position: absolute;
    border: 1px solid #ffffff12;
    border-radius: 50%;
}
.orbit-one {
    width: 320px;
    height: 320px;
}
.orbit-two {
    width: 250px;
    height: 250px;
}
.logo-disc {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    padding: 13px;
    background: #ffffff10;
    border: 1px solid #ffffff20;
    transform: rotate(-8deg);
    box-shadow: 0 20px 50px #17133e44;
}
.logo-disc img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.floating-note {
    display: flex;
    gap: 9px;
    align-items: center;
    position: absolute;
    background: #ffffff12;
    border: 1px solid #ffffff20;
    backdrop-filter: blur(12px);
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 11px;
    box-shadow: 0 8px 20px #16123a20;
}
.note-top {
    top: 55px;
    right: 0;
    transform: rotate(6deg);
}
.note-bottom {
    bottom: 42px;
    left: 0;
    transform: rotate(-5deg);
}
.explore-section {
    scroll-margin-top: 24px;
    margin-top: 35px;
}
.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 20px;
}
.section-heading h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 650;
    letter-spacing: -0.6px;
}
.section-heading > span {
    font-size: 11px;
    color: #9298a7;
}
.feature-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}
.feature-card {
    display: block;
    background: white;
    border: 1px solid #e8eaf1;
    border-radius: 13px;
    padding: 24px;
    color: #20293c;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px #2222440b;
}
.card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}
.feature-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: #6954c9;
    background: #f0ebff;
}
.blue .feature-icon {
    background: #eaf2ff;
    color: #4d83cb;
}
.green .feature-icon {
    background: #e6f6f0;
    color: #399c80;
}
.card-arrow {
    color: #a0a7b6;
}
.card-label {
    font-size: 10px;
    color: #8c93a3;
    margin-bottom: 7px;
}
.feature-card h3 {
    font-size: 16px;
    font-weight: 650;
    letter-spacing: -0.4px;
    margin: 0 0 10px;
}
.card-description {
    color: #858c9d;
    font-size: 11px;
    line-height: 1.9;
    max-width: 280px;
    margin: 0 0 20px;
}
.card-tag {
    font-size: 9px;
    color: #7f739c;
    background: #f7f5fb;
    border-radius: 5px;
    padding: 5px 8px;
}
.blue .card-tag {
    background: #f1f6fc;
    color: #6484ad;
}
.green .card-tag {
    background: #eff8f4;
    color: #608e7f;
}
.admin-callout {
    display: flex;
    align-items: center;
    gap: 17px;
    border: 1px solid #e4e5ef;
    border-radius: 12px;
    background: #eeeff7;
    padding: 22px 25px;
    margin-top: 24px;
}
.admin-icon {
    display: grid;
    place-items: center;
    color: #726c9e;
}
.admin-callout h2 {
    font-size: 13px;
    font-weight: 650;
    margin: 0 0 4px;
}
.admin-callout p {
    font-size: 11px;
    color: #858a9c;
    margin: 0;
}
.admin-callout .button {
    margin-left: auto;
    white-space: nowrap;
}
.button-outline {
    background: #fff;
    border: 1px solid #dddeea;
    color: #55516f;
    font-size: 11px;
}
.site-footer {
    background: white;
    border-top: 1px solid #e8eaf1;
}
.footer-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    min-height: 86px;
    color: #979dad;
    font-size: 10px;
}
.footer-brand {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #545b6d;
    font-size: 12px;
    font-weight: 700;
}
.footer-brand img {
    width: 26px;
    height: 26px;
    object-fit: cover;
    border-radius: 50%;
}
.home-page a:focus-visible {
    outline: 3px solid #a89df4;
    outline-offset: 5px;
}
@media (min-width: 1400px) {
    .content {
        padding-top: 48px;
        padding-bottom: 48px;
    }
    .welcome-banner {
        min-height: 370px;
    }
    .banner-art {
        top: 20px;
    }
}
@media (max-width: 960px) {
    .banner-art {
        right: -60px;
        opacity: 0.5;
    }
    .banner-copy {
        max-width: 70%;
    }
    .banner-copy h2 {
        font-size: 37px;
    }
    .feature-card {
        padding: 20px;
    }
    .community-label {
        display: none;
    }
    .admin-callout p {
        max-width: 300px;
    }
}
@media (max-width: 640px) {
    .header-inner,
    .content,
    .footer-inner {
        width: calc(100% - 32px);
    }
    .header-inner {
        min-height: 78px;
    }
    .brand {
        font-size: 19px;
        gap: 8px;
    }
    .brand img {
        width: 37px;
        height: 37px;
    }
    .nav-explore {
        display: none;
    }
    .button-dark {
        font-size: 10px;
        padding: 10px;
        gap: 6px;
    }
    .button-dark > svg:last-child {
        display: none;
    }
    .content {
        padding-top: 27px;
    }
    h1 {
        font-size: 20px;
    }
    .welcome-banner {
        padding: 29px 24px;
        min-height: auto;
    }
    .banner-copy {
        max-width: 100%;
    }
    .banner-copy h2 {
        font-size: 36px;
    }
    .banner-art {
        display: none;
    }
    .banner-actions {
        gap: 17px;
        flex-wrap: wrap;
    }
    .section-heading > span {
        display: none;
    }
    .feature-grid {
        grid-template-columns: 1fr;
    }
    .feature-card {
        padding: 24px;
    }
    .card-top {
        margin-bottom: 15px;
    }
    .card-description {
        max-width: none;
    }
    .admin-callout {
        flex-wrap: wrap;
        padding: 20px;
        gap: 12px;
    }
    .admin-callout > div {
        flex: 1;
    }
    .admin-callout .button {
        margin-left: 37px;
    }
    .footer-inner {
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        padding: 22px 0;
    }
    .footer-inner p {
        width: 100%;
        text-align: center;
        order: 2;
        margin: 0;
    }
    .footer-inner > span {
        margin-left: auto;
    }
}
@media (prefers-reduced-motion: reduce) {
    .button,
    .feature-card {
        transition: none;
    }
}
</style>
