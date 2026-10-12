// Admin panel behaviour. Everything still works without it (plain forms).
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// Mobile navigation toggle.
$$('[data-admin-nav-toggle]').forEach((button) => {
  const nav = document.querySelector('[data-admin-nav]');
  button.addEventListener('click', () => {
    const open = nav.classList.toggle('hidden') === false;
    button.setAttribute('aria-expanded', String(open));
  });
});

// Fields shown only for some values of another field (data-show-if="f[name]").
function controlValue(form, name) {
  const boxes = $$(`input[type=checkbox][name="${CSS.escape(name)}"]`, form);
  if (boxes.length) return boxes[0].checked ? '1' : '0';
  const field = form.querySelector(`[name="${CSS.escape(name)}"]`);
  return field ? field.value : '';
}
function syncShowIf(form) {
  $$('[data-show-if]', form).forEach((el) => {
    const values = el.dataset.showValues.split(',');
    el.hidden = !values.includes(controlValue(form, el.dataset.showIf));
  });
}
$$('form').forEach((form) => {
  if (!form.querySelector('[data-show-if]')) return;
  form.addEventListener('change', () => syncShowIf(form));
  syncShowIf(form);
});

// Repeatable rows: add from a template, move up/down.
let added = 0;
document.addEventListener('click', (event) => {
  const add = event.target.closest('[data-list-add]');
  if (add) {
    const list = add.closest('[data-list]');
    const tpl = list.querySelector('template[data-list-template]');
    const html = tpl.innerHTML.replaceAll('__NEW__', `new${Date.now()}${added++}`);
    list.querySelector('[data-list-items]').insertAdjacentHTML('beforeend', html);
    const row = list.querySelector('[data-list-items]').lastElementChild;
    initPickers(row);
    row.querySelector('input, textarea, select')?.focus();
    return;
  }
  const move = event.target.closest('[data-move]');
  if (move) {
    const row = move.closest('[data-list-row]');
    if (move.dataset.move === 'up' && row.previousElementSibling) row.previousElementSibling.before(row);
    if (move.dataset.move === 'down' && row.nextElementSibling) row.nextElementSibling.after(row);
    move.focus();
  }
});

// Media picker: thumbnail preview and a visual "browse" grid.
function initPickers(root = document) {
  $$('[data-media-picker]', root).forEach((picker) => {
    if (picker.dataset.ready) return;
    picker.dataset.ready = '1';
    const select = picker.querySelector('[data-media-select]');
    const preview = picker.querySelector('[data-media-preview]');
    const kind = picker.dataset.mediaPicker;
    const render = () => {
      const opt = select.selectedOptions[0];
      preview.replaceChildren();
      if (!opt || !opt.value) return;
      if (kind === 'image' && opt.dataset.thumb) {
        preview.append(Object.assign(document.createElement('img'), { src: opt.dataset.thumb, alt: '', className: 'h-full w-full object-cover' }));
      } else {
        preview.append(Object.assign(document.createElement('span'), { className: 'block p-2 text-caption', textContent: opt.textContent }));
      }
    };
    select.addEventListener('change', render);
    picker.querySelector('[data-media-browse]')?.addEventListener('click', () => {
      const dialog = document.createElement('dialog');
      dialog.className = 'm-auto max-h-[85svh] w-[min(64rem,calc(100vw-2rem))] overflow-y-auto bg-paper p-6 backdrop:bg-ink/60';
      const grid = document.createElement('div');
      grid.className = 'grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6';
      [...select.options].forEach((opt) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = `border-2 bg-white text-left ${opt.selected ? 'border-gold' : 'border-transparent'} hover:border-blue`;
        if (opt.dataset.thumb) b.append(Object.assign(document.createElement('img'), { src: opt.dataset.thumb, alt: '', loading: 'lazy', className: 'aspect-[4/3] w-full object-cover' }));
        b.append(Object.assign(document.createElement('span'), { className: 'block p-2 text-caption', textContent: opt.textContent }));
        b.addEventListener('click', () => { select.value = opt.value; render(); dialog.close(); });
        grid.append(b);
      });
      const close = Object.assign(document.createElement('button'), { type: 'button', className: 'mb-4 text-small font-semibold underline', textContent: '×' });
      close.setAttribute('aria-label', picker.dataset.closeLabel);
      close.addEventListener('click', () => dialog.close());
      dialog.append(close, grid);
      dialog.addEventListener('close', () => dialog.remove());
      document.body.append(dialog);
      dialog.showModal();
    });
  });
}
initPickers();

// Gallery picker: several photos and videos, in order. Rows move with ↑/↓ (above);
// the browse dialog adds many at once, in the order they were picked.
function galleryThumb(opt, className) {
  if (opt.dataset.kind === 'video') return Object.assign(document.createElement('video'), { src: opt.dataset.src, muted: true, preload: 'metadata', className });
  return Object.assign(document.createElement('img'), { src: opt.dataset.thumb, alt: '', loading: 'lazy', className });
}
$$('[data-gallery-picker]').forEach((picker) => {
  const select = picker.querySelector('[data-gallery-add]');
  const list = picker.querySelector('[data-list-items]');
  const browse = picker.querySelector('[data-gallery-browse]');
  picker.querySelector('[data-gallery-fallback]').hidden = true;
  browse.hidden = false;

  const add = (opt) => {
    const row = picker.querySelector('template[data-gallery-template]').content.firstElementChild.cloneNode(true);
    row.querySelector('[data-gallery-thumb]').append(galleryThumb(opt, 'h-full w-full object-cover'));
    row.querySelector('[data-gallery-label]').textContent = opt.dataset.name;
    row.querySelector('[data-gallery-noalt]').hidden = opt.dataset.noalt !== '1';
    row.querySelector('[data-gallery-input]').value = opt.value;
    row.querySelector('[data-gallery-remove]').value = opt.value;
    list.append(row);
    opt.remove();
    picker.querySelector('[data-gallery-empty]').hidden = true;
  };

  browse.addEventListener('click', () => {
    const dialog = picker.querySelector('template[data-gallery-dialog]').content.firstElementChild.cloneNode(true);
    const grid = dialog.querySelector('[data-gallery-grid]');
    const confirm = dialog.querySelector('[data-gallery-confirm]');
    const tabs = $$('[data-gallery-kind]', dialog);
    const chosen = new Set();
    [...select.options].forEach((opt) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.dataset.kind = opt.dataset.kind;
      b.className = 'border-4 border-transparent bg-white text-left aria-pressed:border-gold aria-[pressed=false]:hover:border-blue';
      b.setAttribute('aria-pressed', 'false');
      b.append(galleryThumb(opt, 'aspect-[4/3] w-full object-cover'), Object.assign(document.createElement('span'), { className: 'block p-2 text-caption', textContent: opt.textContent }));
      b.addEventListener('click', () => {
        const on = !chosen.has(opt);
        if (on) chosen.add(opt);
        else chosen.delete(opt);
        b.setAttribute('aria-pressed', String(on));
        confirm.disabled = chosen.size === 0;
      });
      grid.append(b);
    });
    const show = (kind) => {
      tabs.forEach((tab) => {
        const on = tab.dataset.galleryKind === kind;
        tab.setAttribute('aria-pressed', String(on));
        tab.classList.toggle('bg-ink', on);
        tab.classList.toggle('text-white', on);
      });
      [...grid.children].forEach((b) => { b.hidden = b.dataset.kind !== kind; });
    };
    tabs.forEach((tab) => tab.addEventListener('click', () => show(tab.dataset.galleryKind)));
    show('image');
    confirm.addEventListener('click', () => {
      chosen.forEach(add);
      dialog.close();
    });
    dialog.querySelector('[data-gallery-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => dialog.remove());
    document.body.append(dialog);
    dialog.showModal();
  });
});

// Focus point: click the photo to choose what stays in frame when it is cropped.
$$('[data-focus-picker]').forEach((box) => {
  const input = document.querySelector('[data-focus-input]');
  const dot = box.querySelector('[data-focus-dot]');
  const place = () => {
    const m = /^(\d+(?:\.\d+)?)% (\d+(?:\.\d+)?)%$/.exec(input.value.trim()) ?? [null, 50, 50];
    dot.style.left = `${m[1]}%`;
    dot.style.top = `${m[2]}%`;
  };
  box.addEventListener('click', (event) => {
    const r = box.getBoundingClientRect();
    const x = Math.round(((event.clientX - r.left) / r.width) * 100);
    const y = Math.round(((event.clientY - r.top) / r.height) * 100);
    input.value = `${Math.min(100, Math.max(0, x))}% ${Math.min(100, Math.max(0, y))}%`;
    place();
  });
  input.addEventListener('input', place);
  place();
});

// Confirm destructive actions.
$$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
  if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
