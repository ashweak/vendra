// Captures screenshots of the running app (fictional seed data) for review and the portfolio.
// Usage: BASE=http://127.0.0.1:8000 node scripts/screenshots.mjs
import { chromium } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const OUT = 'screenshots';
fs.mkdirSync(OUT, { recursive: true });
const browser = await chromium.launch();
const log = (...a) => console.log('•', ...a);

async function shot(page, name, full = true) {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: full });
    log(name, page.url());
}
async function login(page, email) {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', email);
    await page.fill('#password', 'password');
    await page.click('button:has-text("Sign in")');
    await page.waitForLoadState('networkidle');
}

// ---------- Customer side (desktop)
const ctx = await browser.newContext({ viewport: { width: 1280, height: 860 }, locale: 'en-PH', timezoneId: 'Asia/Manila' });
let page = await ctx.newPage();
const errors = [];
page.on('pageerror', e => errors.push(String(e)));
await page.goto(BASE); await shot(page, '01-landing');
await page.goto(`${BASE}/b/lumina-studio`); await shot(page, '02-public-home');

await page.goto(`${BASE}/b/lumina-studio/book`);
const opts = await page.locator('#service option').allTextContents();
await page.selectOption('#service', { index: 1 });
// next Monday–Saturday date
const d = new Date(); d.setDate(d.getDate() + 2); if (d.getDay() === 0) d.setDate(d.getDate() + 1);
const iso = d.toISOString().slice(0, 10);
await page.fill('#date', iso); await page.dispatchEvent('#date', 'change');
await page.waitForSelector('#slots button', { timeout: 8000 });
await page.locator('#slots button').nth(2).click();
await page.fill('#name', 'Rina Flores'); await page.fill('#email', 'rina@example.test'); await page.fill('#phone', '0918 555 0000');
await shot(page, '03-booking-form');
await page.click('button:has-text("Review booking")');
await shot(page, '04-review');
await page.check('input[name=confirmed]');
await page.click('button:has-text("Confirm and submit")');
await shot(page, '05-result');

// Chat
await page.goto(`${BASE}/b/lumina-studio`);
await page.click('#vchat-open');
for (const msg of ['Magkano po ang haircut?', `Pa-book po ng haircut sa ${['linggo','lunes','martes','miyerkules','huwebes','biyernes','sabado'][d.getDay()]} alas tres`]) {
    await page.fill('#vchat-input', msg);
    await page.click('#vchat-form button[aria-label=Send]');
    await page.waitForTimeout(1500);
}
await shot(page, '06-chat', false);
await ctx.close();

// ---------- Mobile
const m = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, timezoneId: 'Asia/Manila' });
page = await m.newPage();
await page.goto(`${BASE}/b/ganda-beauty`); await shot(page, '07-mobile-shop');
await page.click('#vchat-open');
await page.fill('#vchat-input', 'I want to order 2 niacinamide toner for pickup');
await page.click('#vchat-form button[aria-label=Send]');
await page.waitForTimeout(1500);
await shot(page, '08-mobile-chat-order-draft', false);
await login(page, 'owner@lumina.test');
await page.goto(`${BASE}/app`); await shot(page, '09-mobile-dashboard', false);
await m.close();

// ---------- Dashboard (desktop)
const dctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, timezoneId: 'Asia/Manila' });
page = await dctx.newPage();
page.on('pageerror', e => errors.push(String(e)));
await login(page, 'owner@lumina.test');
await page.goto(`${BASE}/app`); await shot(page, '10-dashboard-overview');
await page.goto(`${BASE}/app/conversations`);
await page.locator('main a.card').first().click(); await shot(page, '11-inbox-handoff');
const tmr = new Date(Date.now() + 86400000); if (tmr.getDay() === 0) tmr.setDate(tmr.getDate() + 1);
await page.goto(`${BASE}/app/appointments?date=${tmr.toISOString().slice(0, 10)}`); await shot(page, '12-appointments');
if (await page.locator('main a.card').count()) { await page.locator('main a.card').first().click(); await shot(page, '13-appointment-detail'); }
await page.goto(`${BASE}/app/quotations`);
if (await page.locator('main table a').count()) { await page.locator('main table a').first().click(); await shot(page, '14-quotation'); }
await page.goto(`${BASE}/app/settings/assistant`);
await page.fill('#tm', 'Can I get a discount for 3 people?'); await page.click('form:has(#tm) button'); await page.waitForLoadState('networkidle');
await shot(page, '15-assistant-test');
await page.goto(`${BASE}/app/reports`); await shot(page, '16-reports');
await page.goto(`${BASE}/app/catalog`); await shot(page, '17-catalog');
await page.goto(`${BASE}/app/team`); await shot(page, '18-team');
await page.click('button:has-text("Sign out")');

await login(page, 'owner@ganda.test');
await page.goto(`${BASE}/app/orders`); await shot(page, '19-orders');
if (await page.locator('main table a').count()) { await page.locator('main table a').first().click(); await shot(page, '20-order-detail'); }
await page.click('button:has-text("Sign out")');

await login(page, 'manager@lumina.test');
await page.goto(`${BASE}/app/settings/profile`); await shot(page, '21-manager-forbidden-settings');
await page.context().clearCookies();

await login(page, 'admin@vendra.test');
await page.goto(`${BASE}/admin`); await shot(page, '22-admin');
await dctx.close();
await browser.close();

fs.writeFileSync(`${OUT}/page-errors.txt`, errors.join('\n') || 'none');
console.log('JS errors:', errors.length ? errors : 'none');
