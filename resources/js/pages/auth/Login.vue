<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div class="home-page">
        <header class="site-header">
            <div class="header-inner">
                <a href="/" class="brand" aria-label="ParsiPolito home"
                    ><img src="/ParsiLogo.jpg" alt="ParsiPolito logo" /><span
                        >PARSI<small>THE STUDENT HUB</small></span
                    ></a
                >
                <nav><a href="/" class="nav-explore">Back to the hub</a></nav>
            </div>
        </header>
        <main class="content login-content">
            <section class="welcome-banner login-banner">
                <div class="banner-copy">
                    <span class="banner-badge"
                        >YOUR COMMUNITY. YOUR CAMPUS.</span
                    >
                    <h1>Welcome back.</h1>
                    <p>
                        Shared knowledge. Stronger community. Log in to your
                        ParsiPolito account.
                    </p>
                </div>
            </section>
            <section class="login-card" aria-labelledby="login-title">
                <p class="eyebrow">PARSI STUDENT HUB</p>
                <h2 id="login-title">Log in to your account</h2>
                <div
                    v-if="status"
                    class="mb-4 text-center text-sm font-medium text-green-600"
                >
                    {{ status }}
                </div>
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-6"
                >
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="email@example.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">Password</Label>
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="Password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center justify-between">
                            <Label
                                for="remember"
                                class="flex items-center space-x-3"
                            >
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    :tabindex="3"
                                />
                                <span>Remember me</span>
                            </Label>
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
                    </div>
                </Form>
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
<style scoped src="../../../css/student-hub.css"></style>
<style scoped>
.login-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 40px;
    padding-block: 56px;
}
.login-banner {
    min-height: 440px;
    display: flex;
    align-items: center;
}
.login-banner .banner-copy {
    max-width: 100%;
}
.login-banner h1 {
    font-size: 42px;
    line-height: 1.15;
    color: white;
    margin: 24px 0;
}
.login-card {
    background: white;
    color: #182238;
    border: 1px solid #e8eaf1;
    border-radius: 16px;
    padding: 36px;
    color-scheme: light;
    --foreground: #182238;
    --background: #fff;
    --input: #d9dce6;
    --muted-foreground: #737b8c;
    --primary: #212343;
    --primary-foreground: #fff;
}
.login-card h2 {
    font-size: 24px;
    font-weight: 650;
    margin-bottom: 28px;
}
@media (max-width: 760px) {
    .login-content {
        grid-template-columns: 1fr;
        gap: 24px;
        padding-block: 28px;
    }
    .login-banner {
        min-height: auto;
    }
    .login-banner h1 {
        font-size: 32px;
    }
    .login-card {
        padding: 24px;
    }
}
</style>
