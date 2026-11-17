import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { fileURLToPath } from 'node:url';
import { createInertiaApp } from '@inertiajs/vue3';
import vue from '@vitejs/plugin-vue';
import { createServer } from 'vite';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

let server;
let Welcome;
let Login;

before(async () => {
    server = await createServer({
        configFile: false,
        plugins: [vue()],
        resolve: {
            alias: {
                '@': fileURLToPath(
                    new URL('../../resources/js', import.meta.url),
                ),
            },
        },
        server: { middlewareMode: true, watch: null, ws: false },
        appType: 'custom',
    });
    Welcome = (await server.ssrLoadModule('/resources/js/pages/Welcome.vue'))
        .default;
    Login = (await server.ssrLoadModule('/resources/js/pages/auth/Login.vue'))
        .default;
});

after(async () => {
    await server?.close();
});

async function render(component, props) {
    const result = await createInertiaApp({
        page: {
            component: component === Welcome ? 'Welcome' : 'auth/Login',
            props: { errors: {}, ...props },
            url: '/',
            version: null,
        },
        resolve: () => component,
        setup: ({ App, props: appProps, plugin }) =>
            createSSRApp({ render: () => h(App, appProps) }).use(plugin),
        render: renderToString,
    });

    // Only inspect rendered markup, not Inertia's serialized page props.
    return result.body.replace(
        /<script\b[^>]*data-page="app"[^>]*>[\s\S]*?<\/script>/,
        '',
    );
}

const homeProps = {
    telegramUrl: 'https://t.me/parsi_test_bot',
    auth: { user: null },
    canAccessAdmin: false,
};

void test('guests see a login link and no admin links anywhere on the homepage', async () => {
    const html = await render(Welcome, homeProps);

    assert.match(html, /href="\/login"[^>]*>\s*Log in\s*<\/a>/);
    assert.doesNotMatch(html, /href="\/admin"|Admin Panel|Behind the scenes/);
});

void test('guests cannot see admin links even if an access flag is accidentally true', async () => {
    const html = await render(Welcome, { ...homeProps, canAccessAdmin: true });

    assert.match(html, /href="\/login"/);
    assert.doesNotMatch(html, /href="\/admin"|Admin Panel/);
});

void test('students see their account link and no login or admin links', async () => {
    const html = await render(Welcome, {
        ...homeProps,
        auth: { user: { id: 1, role: 'student' } },
    });

    assert.match(html, />\s*My account\s*<\/a>/);
    assert.doesNotMatch(html, /href="\/login"|href="\/admin"|Admin Panel/);
});

void test('authenticated staff see the admin navigation and callout', async () => {
    const html = await render(Welcome, {
        ...homeProps,
        auth: { user: { id: 2, role: 'editor' } },
        canAccessAdmin: true,
    });

    assert.match(html, /href="\/admin"/);
    assert.match(html, /Admin Panel/);
    assert.match(html, /Behind the scenes/);
    assert.doesNotMatch(html, /href="\/login"/);
});

void test('the custom login page has the shared branding and posts credentials to the login endpoint', async () => {
    const html = await render(Login, {
        ...homeProps,
        canResetPassword: true,
        status: null,
    });

    assert.match(html, /ParsiLogo\.jpg/);
    assert.match(html, /THE STUDENT HUB/);
    assert.match(html, /Welcome back\./);
    assert.match(html, /<form[^>]*action="\/login"[^>]*method="post"/i);
    assert.match(html, /<input[^>]*name="email"/);
    assert.match(html, /<input[^>]*type="password"[^>]*name="password"/);
    assert.match(html, /autocomplete="current-password"/);
    assert.match(html, /name="remember"/);
    assert.match(html, /data-test="login-button"/);
    assert.doesNotMatch(html, /Admin Panel/);
});

void test('status messages are displayed as escaped text', async () => {
    const html = await render(Login, {
        ...homeProps,
        canResetPassword: false,
        status: '<script>alert("status")</script>',
    });

    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>alert/);
});
