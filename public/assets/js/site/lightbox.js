// Photo viewer: gallery links open a full-screen <dialog>. ←/→, swipe and Esc work;
// without JavaScript the links simply open the large image.
export function init(root) {
  const items = [...root.querySelectorAll('[data-js-lightbox-item]')];
  const dialog = root.querySelector('[data-js-lightbox-dialog]');
  if (!items.length || !dialog) return;
  const image = dialog.querySelector('[data-js-lightbox-image]');
  const stage = dialog.querySelector('[data-js-lightbox-stage]');
  const count = dialog.querySelector('[data-js-lightbox-count]');
  const prev = dialog.querySelector('[data-js-lightbox-prev]');
  const next = dialog.querySelector('[data-js-lightbox-next]');
  prev.hidden = next.hidden = items.length < 2;
  let index = 0;

  const show = (i) => {
    index = (i + items.length) % items.length;
    const item = items[index];
    image.src = item.href;
    image.alt = item.querySelector('img')?.alt ?? '';
    count.textContent = [index + 1, items.length].join(' / ');
  };

  items.forEach((item, i) => item.addEventListener('click', (event) => {
    // Let "open in a new tab" clicks through.
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    show(i);
    dialog.showModal();
  }));
  dialog.querySelector('[data-js-lightbox-close]').addEventListener('click', () => dialog.close());
  prev.addEventListener('click', () => show(index - 1));
  next.addEventListener('click', () => show(index + 1));
  dialog.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') show(index - 1);
    if (event.key === 'ArrowRight') show(index + 1);
  });
  // A click on the dark area around the photo closes the viewer.
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog || event.target === stage) dialog.close();
  });
  let startX = null;
  stage.addEventListener('pointerdown', (event) => { startX = event.clientX; });
  stage.addEventListener('pointerup', (event) => {
    if (startX === null || items.length < 2) return;
    const dx = event.clientX - startX;
    startX = null;
    if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
  });
  dialog.addEventListener('close', () => {
    image.removeAttribute('src');
    items[index].focus();
  });
}
