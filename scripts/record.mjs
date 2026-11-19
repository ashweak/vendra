// Records a short walkthrough of the real app (fictional seed data): a customer asks the AI,
// books through it, and the owner approves. Output: recording/raw.webm (converted to MP4/GIF in CI).
// Usage: BASE=http://127.0.0.1:8000 node scripts/record.mjs
import { chromium } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const OUT = 'recording';
fs.mkdirSync(OUT, { recursive: true });
const size = { width: 1280, height: 720 };
const browser = await chromium.launch({ slowMo: 60 });
const ctx = await browser.newContext({ viewport: size, recordVideo: { dir: OUT, size }, locale: 'en-PH', timezoneId: 'Asia/Manila' });
const page = await ctx.newPage();
const pause = ms => page.waitForTimeout(ms);
const errors = [];
page.on('pageerror', e => errors.push(String(e)));

// Caption bar so the video explains itself without sound.
async function caption(text) {
    await page.evaluate(t => {
        let el = document.getElementById('rec-caption');
        if (!el) {
            el = document.createElement('div');
            el.id = 'rec-caption';
            el.style.cssText = 'position:fixed;left:50%;bottom:22px;transform:translateX(-50%);z-index:99999;background:#111;color:#fff;font:600 15px/1.2 Inter,system-ui,sans-serif;padding:10px 16px;border-radius:999px;box-shadow:0 8px 24px -8px rgba(0,0,0,.5);pointer-events:none;white-space:nowrap';
            document.body.appendChild(el);
        }
        el.innerHTML = '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ffc83d;margin-right:9px;vertical-align:middle"></span>' + t;
    }, text);
}
async function go(url, text) {
    await page.goto(BASE + url);
    await page.waitForLoadState('networkidle').catch(() => {});
    if (text) await caption(text);
}
async function scrollTo(y) {
    await page.evaluate(top => window.scrollTo({ top, behavior: 'smooth' }), y);
    await pause(900);
}
async function say(msg) {
    await page.click('#vchat-input');
    await page.type('#vchat-input', msg, { delay: 35 });
    const before = await page.locator('#vchat-log > *').count();
    await page.click('#vchat-form button[aria-label=Send]');
    await page.waitForFunction(n => document.querySelectorAll('#vchat-log > *').length >= n + 2, before, { timeout: 15000 }).catch(() => {});
    await pause(1800);
}

// Booking day: two days ahead in Manila, skipping Sunday (closed).
const now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
const d = new Date(now); d.setDate(d.getDate() + 2); if (d.getDay() === 0) d.setDate(d.getDate() + 1);
const day = ['linggo', 'lunes', 'martes', 'miyerkules', 'huwebes', 'biyernes', 'sabado'][d.getDay()];
const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

// 1. Landing
await go('/', 'Vendra — AI assistant for small Philippine businesses');
await pause(1800);
await scrollTo(700); await pause(800); await scrollTo(0); await pause(500);

// 2. Business page + AI chat
await go('/b/lumina-studio', '1 · A customer opens the salon’s Vendra page');
await pause(1600);
await page.click('#vchat-open');
await caption('2 · They ask the AI in Taglish — prices come from the real catalog');
await pause(900);
await say('Magkano po ang hair color?');
await caption('3 · The AI checks live availability and drafts the booking');
await say(`Pa-book po ng hair color sa ${day} alas dos ng hapon`);
await pause(800);

// 3. Review & confirm (the customer always confirms; the AI never books on its own)
const cta = page.locator('#vchat-log a:has-text("Review & confirm")').last();
if (await cta.count()) {
    await cta.click();
} else {
    console.log('No draft from chat; opening the booking form directly.');
    await page.goto(`${BASE}/b/lumina-studio/book?date=${iso}`);
}
await page.waitForLoadState('networkidle').catch(() => {});
await caption('4 · The draft opens pre-filled — the customer adds contact details');
await pause(1200);
if (!(await page.inputValue('#time'))) {
    const v = await page.locator('#service option', { hasText: 'Hair Color' }).first().getAttribute('value');
    await page.selectOption('#service', v);
    await page.fill('#date', iso); await page.dispatchEvent('#date', 'change');
    await page.waitForSelector('#slots button', { timeout: 8000 });
    await page.locator('#slots button').first().click();
}
await page.locator('#name').scrollIntoViewIfNeeded();
await page.type('#name', 'Rina Flores', { delay: 40 });
await page.type('#email', 'rina@example.test', { delay: 25 });
await pause(600);
await page.click('button:has-text("Review booking")');
await page.waitForLoadState('networkidle').catch(() => {});
await caption('5 · Review everything, then confirm');
await pause(1500);
await page.check('input[name=confirmed]');
await pause(500);
await page.click('button:has-text("Confirm and submit")');
await page.waitForLoadState('networkidle').catch(() => {});
await caption('6 · Hair color needs staff approval, so it’s held as pending');
await pause(2600);

// 4. Owner side
await go('/login', '7 · The owner signs in to the dashboard');
await page.type('#email', 'owner@lumina.test', { delay: 30 });
await page.type('#password', 'password', { delay: 30 });
await page.click('button:has-text("Sign in")');
await page.waitForLoadState('networkidle').catch(() => {});
await caption('8 · Today at a glance — bookings, orders and AI handoffs');
await pause(2400);
await go('/app/appointments?status=pending_approval', '9 · The new booking is waiting for approval');
await pause(1600);
const card = page.locator('main a.card', { hasText: 'Rina Flores' }).first();
await (await card.count() ? card : page.locator('main a.card').first()).click();
await page.waitForLoadState('networkidle').catch(() => {});
await caption('10 · One click to approve — the time slot is locked in');
await pause(1600);
await page.locator('button[value=confirmed]').scrollIntoViewIfNeeded();
await pause(500);
await page.click('button[value=confirmed]');
await page.waitForLoadState('networkidle').catch(() => {});
await caption('10 · Approved — the customer can track it with a secure link');
await pause(2200);
await go('/app/conversations', '11 · Every AI conversation is saved for the team');
await pause(1600);
await page.locator('main a.card', { hasText: 'Hair Color' }).first().click();
await page.waitForLoadState('networkidle').catch(() => {});
await caption('12 · The full chat and draft — staff can take over at any time');
await pause(2600);

const video = page.video();
await ctx.close();
await video.saveAs(`${OUT}/raw.webm`);
await browser.close();
fs.writeFileSync(`${OUT}/page-errors.txt`, errors.join('\n') || 'none');
console.log('JS errors:', errors.length ? errors : 'none');
