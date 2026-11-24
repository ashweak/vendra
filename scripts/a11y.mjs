// Accessibility check (axe-core, WCAG 2.1 A/AA) of customer pages, sign-in pages and the dashboard.
// Fails on "serious" or "critical" problems. Usage: BASE=http://127.0.0.1:8000 node scripts/a11y.mjs
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const report = [];
let failed = 0;

async function check(page, path, label = path) {
    await page.goto(BASE + path);
    await page.waitForLoadState('networkidle').catch(() => {});
    const res = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .exclude('.cf-turnstile') // third-party widget (only when Turnstile keys are set)
        .analyze();
    const bad = res.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
    const minor = res.violations.length - bad.length;
    report.push({ page: label, serious: bad.map((v) => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.slice(0, 5).map((n) => n.target.join(' ') + ' → ' + (n.failureSummary || '').split('\n').slice(1, 3).join(' ')) })), minor });
    console.log(`${bad.length ? '✗' : '✓'} ${label}${bad.length ? ` — ${bad.length} serious/critical` : ''}${minor ? ` (${minor} minor)` : ''}`);
    for (const v of bad) {
        console.log(`    [${v.impact}] ${v.id}: ${v.help}`);
        for (const n of v.nodes.slice(0, 4)) console.log(`        ${n.target.join(' ')}`);
    }
    failed += bad.length;
}

// Customer side, phone and desktop.
for (const viewport of [{ width: 390, height: 844 }, { width: 1280, height: 860 }]) {
    const ctx = await browser.newContext({ viewport, locale: 'en-PH', timezoneId: 'Asia/Manila' });
    const page = await ctx.newPage();
    const tag = viewport.width < 500 ? 'phone' : 'desktop';
    await check(page, '/', `${tag} · landing`);
    for (const layout of ['classic', 'brochure', 'spotlight', 'cafe', 'panel', 'shop']) {
        await check(page, `/b/lumina-studio?layout=${layout}`, `${tag} · shop page (${layout})`);
    }
    await check(page, '/b/lumina-studio?preview=off', `${tag} · shop page (saved look)`);
    if (tag === 'desktop') {
        for (const palette of ['coral', 'ocean', 'espresso', 'slate', 'garden', 'berry', 'lavender']) {
            await check(page, `/b/lumina-studio?layout=brochure&palette=${palette}`, `desktop · palette ${palette}`);
        }
        await check(page, '/b/lumina-studio?preview=off', 'desktop · reset preview');
    }
    await check(page, '/b/lumina-studio/book', `${tag} · book`);
    await check(page, '/b/ganda-beauty/order', `${tag} · order`);
    await check(page, '/b/lumina-studio/quote', `${tag} · quote`);
    await check(page, '/b/kislap-cleaning/request', `${tag} · service request`);
    await check(page, '/b/lumina-studio/track', `${tag} · track`);
    await check(page, '/b/lumina-studio/privacy', `${tag} · privacy notice`);
    await check(page, '/b/ganda-beauty?lang=fil', `${tag} · Filipino page`);
    await check(page, '/b/ganda-beauty?lang=en', `${tag} · back to English`);
    await check(page, '/login', `${tag} · sign in`);
    await check(page, '/register', `${tag} · register`);
    await check(page, '/forgot-password', `${tag} · forgot password`);
    await check(page, '/privacy', `${tag} · platform privacy`);
    await check(page, '/terms', `${tag} · terms`);
    await ctx.close();
}

// Dashboard (owner).
const ctx = await browser.newContext({ viewport: { width: 1280, height: 860 } });
const page = await ctx.newPage();
await page.goto(`${BASE}/login`);
await page.fill('#email', 'owner@lumina.test');
await page.fill('#password', 'password');
await page.click('button:has-text("Sign in")');
await page.waitForLoadState('networkidle');
for (const p of ['/app', '/app/conversations', '/app/appointments', '/app/orders', '/app/quotations', '/app/service-requests', '/app/customers',
    '/app/catalog', '/app/reports', '/app/team', '/app/account', '/app/activity', '/app/settings/profile', '/app/settings/appearance',
    '/app/settings/photos', '/app/settings/workflow', '/app/settings/hours', '/app/settings/knowledge', '/app/settings/unanswered',
    '/app/settings/assistant', '/app/settings/data']) {
    await check(page, p, `dashboard · ${p}`);
}
await page.goto(`${BASE}/app/conversations`);
const conv = await page.$('a[href*="/app/conversations/"]');
if (conv) await check(page, new URL(await conv.getAttribute('href'), BASE).pathname, 'dashboard · conversation');

await browser.close();
fs.mkdirSync('quality', { recursive: true });
fs.writeFileSync('quality/a11y.json', JSON.stringify(report, null, 2));
console.log(failed ? `\n${failed} serious/critical accessibility problem(s).` : '\nNo serious or critical accessibility problems.');
process.exit(failed ? 1 : 0);
