// ADEPC's PayPal hosted button; the SDK is fetched only once the block is on screen.
export function init(box) {
  const cfg = JSON.parse(box.dataset.jsPaypal);
  const target = box.firstElementChild;
  const say = (text, cls, role) => {
    box.querySelector('p')?.remove();
    if (!text) return;
    const p = document.createElement('p');
    p.className = cls;
    if (role) p.setAttribute('role', role);
    p.textContent = text;
    box.append(p);
  };
  const io = new IntersectionObserver(([entry]) => {
    if (!entry.isIntersecting) return;
    io.disconnect();
    say(cfg.loading, 'text-small text-stone');
    const s = document.createElement('script');
    s.src = cfg.sdk;
    s.async = true;
    s.onload = async () => {
      try {
        await window.paypal.HostedButtons({ hostedButtonId: cfg.button }).render(`#${target.id}`);
        say('');
      } catch {
        say(cfg.error, 'text-small', 'alert');
      }
    };
    s.onerror = () => say(cfg.error, 'text-small', 'alert');
    document.head.append(s);
  }, { threshold: 0.1 });
  io.observe(box);
}
