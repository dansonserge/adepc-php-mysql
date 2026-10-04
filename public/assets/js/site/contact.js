// Progressive enhancement of the contact form: same behaviour as the original
// (submits in place, "Sending…", inline errors, success message, mailto fallback).
const FIELD_ERROR = 'mt-2 text-small font-semibold text-ember';

export function init(form) {
  const cfg = JSON.parse(form.dataset.jsContact);
  const purpose = form.querySelector('#cf-purpose');
  const message = form.querySelector('#cf-message');
  const submit = form.querySelector('button[type=submit]');
  const label = submit.querySelector('span');
  const status = form.querySelector('[data-js-contact-status]');
  const confidential = form.querySelector('[data-js-confidential]');

  const syncPurpose = () => {
    const prayer = cfg.prayer.includes(purpose.value);
    if (prayer) message.setAttribute('placeholder', cfg.placeholder);
    else message.removeAttribute('placeholder');
    if (confidential) confidential.hidden = !prayer;
  };
  purpose.addEventListener('change', syncPurpose);
  syncPurpose();

  const setError = (name, text) => {
    const input = form.querySelector(`#cf-${name}`);
    const id = `cf-${name}-err`;
    form.querySelector(`#${id}`)?.remove();
    input.setAttribute('aria-invalid', text ? 'true' : 'false');
    if (!text) { input.removeAttribute('aria-describedby'); return; }
    input.setAttribute('aria-describedby', id);
    const p = Object.assign(document.createElement('p'), { id, className: FIELD_ERROR, textContent: text });
    input.after(p);
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    submit.disabled = true;
    label.textContent = cfg.sending;
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const state = await res.json();
      ['name', 'email', 'message'].forEach((f) => setError(f, state.errorText?.[f] ?? ''));
      status.replaceChildren();
      if (state.status === 'sent') {
        const p = Object.assign(document.createElement('p'), { className: 'border-l-4 border-gold py-2 pl-5 text-h3 leading-snug font-semibold', textContent: state.message });
        p.setAttribute('role', 'status');
        form.replaceWith(p);
        return;
      }
      if (state.status === 'fallback') {
        status.append(Object.assign(document.createElement('p'), { className: 'mb-6 text-body', textContent: state.message }));
        if (state.mailto) window.location.href = state.mailto;
      } else if (state.status === 'error') {
        const p = Object.assign(document.createElement('p'), { className: 'mb-6 text-body font-semibold text-ember', textContent: state.message });
        p.setAttribute('role', 'alert');
        status.append(p);
      }
    } catch {
      form.submit();
      return;
    }
    submit.disabled = false;
    label.textContent = cfg.submit;
  });
}
