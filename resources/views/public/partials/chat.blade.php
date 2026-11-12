{{-- AI chat. Separate from page forms: typing in chat can never submit a booking/order (P8). --}}
<div id="vchat" data-history="{{ route('public.chat.history', $business) }}" data-send="{{ route('public.chat.send', $business) }}" data-handoff="{{ route('public.chat.handoff', $business) }}" data-name="{{ $business->name }}">
    <button id="vchat-open" type="button" class="fixed right-4 bottom-4 z-40 flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-300" style="background: var(--accent)" aria-expanded="false" aria-controls="vchat-panel">
        💬 Ask us
    </button>
    <section id="vchat-panel" class="fixed inset-0 z-50 hidden flex-col bg-white sm:inset-auto sm:right-4 sm:bottom-4 sm:h-[34rem] sm:w-96 sm:rounded-2xl sm:border sm:border-gray-200 sm:shadow-2xl" role="dialog" aria-label="Chat with {{ $business->name }}">
        <header class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full text-white" style="background: var(--accent)">✦</span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ $business->name }}</p>
                <p id="vchat-state" class="text-xs text-gray-500">AI assistant · answers from the business’s published info</p>
            </div>
            <button id="vchat-min" type="button" class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100" aria-label="Minimize chat">—</button>
        </header>
        <div id="vchat-log" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-4 text-sm" aria-live="polite">
            <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-3 py-2 shadow-sm">
                {{ $business->greeting ?: 'Hi! I’m '.$business->name.'’s AI assistant. Ask about prices, hours or open times — English, Tagalog or Taglish is fine.' }}
            </div>
        </div>
        <form id="vchat-form" class="border-t border-gray-100 p-3">
            <div class="flex gap-2">
                <label for="vchat-input" class="sr-only">Message</label>
                <input id="vchat-input" name="message" maxlength="1000" autocomplete="off" class="input" placeholder="Type your message…">
                <button class="btn btn-primary" style="background: var(--accent)">Send</button>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <button id="vchat-staff" type="button" class="link">Talk to staff</button>
                <span>Nothing is booked until you confirm a form.</span>
            </div>
        </form>
    </section>
</div>
@push('scripts')
<script>
(() => {
    const root = document.getElementById('vchat'), panel = document.getElementById('vchat-panel'), log = document.getElementById('vchat-log');
    const openBtn = document.getElementById('vchat-open'), form = document.getElementById('vchat-form'), input = document.getElementById('vchat-input');
    const stateEl = document.getElementById('vchat-state');
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    let loaded = false, busy = false, lastId = 0, poll = null;
    const states = { ai_active: 'AI assistant · answers from the business’s published info', waiting_for_staff: 'Waiting for staff · AI paused', staff_active: 'Staff is replying', closed: 'Conversation closed' };

    function bubble(m) {
        const wrap = document.createElement('div');
        const mine = m.from === 'customer';
        wrap.className = mine ? 'flex justify-end' : '';
        const b = document.createElement('div');
        b.className = mine ? 'max-w-[85%] rounded-2xl rounded-tr-sm px-3 py-2 text-white whitespace-pre-line' : (m.from === 'system' ? 'mx-auto max-w-[90%] rounded-lg bg-amber-50 px-3 py-2 text-center text-xs text-amber-900' : 'max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-3 py-2 shadow-sm whitespace-pre-line');
        if (mine) b.style.background = 'var(--accent)';
        if (m.from === 'ai' || m.from === 'staff') {
            const who = document.createElement('p');
            who.className = 'mb-0.5 text-[11px] font-semibold text-gray-500';
            who.textContent = m.from === 'ai' ? 'AI assistant' : (m.name || 'Staff');
            b.appendChild(who);
        }
        b.appendChild(document.createTextNode(m.body));
        if (m.draft) {
            const card = document.createElement('div');
            card.className = 'mt-2 rounded-xl border border-gray-200 bg-gray-50 p-3';
            const t = document.createElement('p'); t.className = 'font-semibold'; t.textContent = m.draft.title; card.appendChild(t);
            (m.draft.lines || []).forEach(l => { const p = document.createElement('p'); p.className = 'text-xs text-gray-600'; p.textContent = l; card.appendChild(p); });
            const a = document.createElement('a'); a.href = m.draft.url; a.className = 'btn btn-primary btn-sm mt-2 w-full'; a.style.background = 'var(--accent)'; a.textContent = 'Review & confirm';
            card.appendChild(a); b.appendChild(card);
        }
        wrap.appendChild(b);
        log.appendChild(wrap);
    }
    function render(data) {
        (data.messages || []).forEach(m => { if (m.id > lastId) { bubble(m); lastId = m.id; } });
        stateEl.textContent = states[data.state] || states.ai_active;
        log.scrollTop = log.scrollHeight;
        clearInterval(poll);
        if (data.state === 'waiting_for_staff' || data.state === 'staff_active') poll = setInterval(refresh, 8000);
    }
    async function refresh() {
        const r = await fetch(root.dataset.history, { headers: { Accept: 'application/json' } });
        if (r.ok) render(await r.json());
    }
    async function post(url, body) {
        const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body || {}) });
        if (r.status === 429) { bubble({ from: 'system', body: 'You’re sending messages quickly. Please wait a moment.' }); return; }
        if (!r.ok) { bubble({ from: 'system', body: 'Message not sent. Please try again or use the forms on this page.' }); return; }
        return await r.json();
    }
    function resetLog() { [...log.children].slice(1).forEach(n => n.remove()); lastId = 0; }
    function open() {
        panel.classList.remove('hidden'); panel.classList.add('flex'); openBtn.classList.add('hidden'); openBtn.setAttribute('aria-expanded', 'true');
        input.focus();
        if (!loaded) { loaded = true; refresh(); }
    }
    function close() { panel.classList.add('hidden'); panel.classList.remove('flex'); openBtn.classList.remove('hidden'); openBtn.setAttribute('aria-expanded', 'false'); openBtn.focus(); }
    openBtn.addEventListener('click', open);
    document.getElementById('vchat-min').addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.classList.contains('hidden')) close(); });
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text || busy) return;
        busy = true; input.value = '';
        bubble({ from: 'customer', body: text });
        const typing = document.createElement('p'); typing.className = 'text-xs text-gray-400'; typing.textContent = 'Typing…'; log.appendChild(typing); log.scrollTop = log.scrollHeight;
        let data = null;
        try { data = await post(root.dataset.send, { message: text }); } finally { typing.remove(); busy = false; }
        if (data) { resetLog(); render(data); }
    });
    document.getElementById('vchat-staff').addEventListener('click', async () => { const d = await post(root.dataset.handoff); if (d) { resetLog(); render(d); } });
})();
</script>
@endpush
