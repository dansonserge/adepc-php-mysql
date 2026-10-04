// Header: transparent over the hero, ink once the page scrolls; native <dialog> menu.
export function initHeader() {
  const header = document.querySelector('[data-js-header]');
  if (!header) return;
  const shade = header.querySelector('[data-js-header-shade]');
  let frame = 0;
  let scrolled = null;
  const update = () => {
    frame = 0;
    const next = window.scrollY > 24;
    if (next === scrolled) return;
    scrolled = next;
    header.classList.toggle('bg-ink', next);
    header.classList.toggle('bg-transparent', !next);
    shade?.classList.toggle('opacity-0', next);
    shade?.classList.toggle('opacity-100', !next);
  };
  update();
  window.addEventListener('scroll', () => { if (!frame) frame = requestAnimationFrame(update); }, { passive: true });

  const dialog = header.querySelector('[data-js-menu]');
  if (!dialog) return;
  header.querySelector('[data-js-menu-open]')?.addEventListener('click', () => dialog.showModal());
  dialog.querySelector('[data-js-menu-close]')?.addEventListener('click', () => dialog.close());
  // Navigating from inside the menu closes it.
  dialog.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => dialog.close()));
}
