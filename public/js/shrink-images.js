/* Shrinks big phone photos in the browser before upload, so they upload fast on mobile data and fit
   the 5 MB limit. Used on <input type="file" data-shrink="MAX_SIDE_PX">. The server still checks and
   re-encodes every file; if anything here fails, the original file is sent unchanged. */
(() => {
  const TARGET_BYTES = 1.2 * 1024 * 1024;
  const fmt = (b) => (b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');

  async function decode(file) {
    if ('createImageBitmap' in window) {
      try { return await createImageBitmap(file, { imageOrientation: 'from-image' }); } catch (e) { /* fall back */ }
    }
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => { resolve(img); URL.revokeObjectURL(img.src); };
      img.onerror = reject;
      img.src = URL.createObjectURL(file);
    });
  }

  const toBlob = (canvas, type, q) => new Promise((r) => canvas.toBlob(r, type, q));

  async function shrink(file, maxSide, jpegOnly) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return file;
    const img = await decode(file);
    const w = img.width, h = img.height;
    const scale = Math.min(1, maxSide / Math.max(w, h));
    if (scale === 1 && file.size <= TARGET_BYTES) return file;
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(w * scale));
    canvas.height = Math.max(1, Math.round(h * scale));
    const ctx = canvas.getContext('2d');
    if (jpegOnly) { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height); } // JPEG has no transparency
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    if (img.close) img.close();
    // WebP keeps transparency (logos); JPEG is the fallback for browsers without WebP encoding.
    let blob = jpegOnly ? null : await toBlob(canvas, 'image/webp', 0.85);
    if (!blob || blob.type !== 'image/webp') blob = file.type === 'image/png' && !jpegOnly ? null : await toBlob(canvas, 'image/jpeg', 0.85);
    if (!blob || blob.size >= file.size) return file;
    const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
    return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.' + ext, { type: blob.type, lastModified: Date.now() });
  }

  async function handle(input) {
    if (!input.files || !input.files.length || typeof DataTransfer === 'undefined') return;
    const max = parseInt(input.dataset.shrink, 10) || 2000;
    const jpegOnly = input.dataset.shrinkJpeg !== undefined;
    const busy = input.dataset.shrinkBusy || 'Preparing picture…';
    const done = input.dataset.shrinkDone || 'Ready to upload: :after (was :before).';
    const form = input.form;
    const submit = form && form.querySelector('[type=submit], button:not([type])');
    let note = input.parentElement.querySelector('.shrink-note');
    if (!note) {
      note = document.createElement('p');
      note.className = 'shrink-note hint mt-1';
      note.setAttribute('aria-live', 'polite');
      input.insertAdjacentElement('afterend', note);
    }
    if (submit) submit.disabled = true;
    note.textContent = busy;
    try {
      const before = [...input.files].reduce((s, f) => s + f.size, 0);
      const out = new DataTransfer();
      for (const f of input.files) {
        let next = f;
        try { next = await shrink(f, max, jpegOnly); } catch (e) { next = f; }
        out.items.add(next);
      }
      input.files = out.files;
      const after = [...input.files].reduce((s, f) => s + f.size, 0);
      note.textContent = after < before ? done.replace(':after', fmt(after)).replace(':before', fmt(before)) : '';
    } finally {
      if (submit) submit.disabled = false;
    }
  }

  document.addEventListener('change', (e) => {
    const t = e.target;
    if (t instanceof HTMLInputElement && t.type === 'file' && t.hasAttribute('data-shrink')) handle(t);
  });
})();
