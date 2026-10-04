// Law 25: Google Analytics (cookies) loads only after "Allow".
const KEY = 'adepc.consent';
const read = () => { try { const v = localStorage.getItem(KEY); return v === 'granted' || v === 'denied' ? v : 'unset'; } catch { return 'unset'; } };
const write = (v) => { try { localStorage.setItem(KEY, v); } catch {} };

let gaLoaded = false;
function loadGa(id) {
  if (gaLoaded) return;
  gaLoaded = true;
  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() { window.dataLayer.push(arguments); };
  window.gtag('js', new Date());
  window.gtag('config', id);
  const s = document.createElement('script');
  s.async = true;
  s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
  document.head.append(s);
}

export function init(template) {
  const id = template.dataset.jsGaId;
  let bar = null;
  const close = () => { bar?.remove(); bar = null; };
  const open = () => {
    if (bar) return;
    bar = template.content.firstElementChild.cloneNode(true);
    bar.querySelectorAll('[data-js-consent-choice]').forEach((b) => b.addEventListener('click', () => {
      write(b.dataset.jsConsentChoice);
      close();
      if (b.dataset.jsConsentChoice === 'granted') loadGa(id);
    }));
    template.before(bar);
  };
  if (read() === 'granted') loadGa(id);
  if (read() === 'unset') open();
  document.querySelectorAll('[data-js-consent-reopen]').forEach((b) => b.addEventListener('click', open));
  window.addEventListener('storage', () => { if (read() !== 'unset') close(); if (read() === 'granted') loadGa(id); });
}
