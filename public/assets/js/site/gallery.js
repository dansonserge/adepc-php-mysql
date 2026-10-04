// Photo filter by church: aria-pressed buttons, polite count.
export function init(root) {
  const counts = JSON.parse(root.dataset.jsGallery);
  const buttons = [...root.querySelectorAll('[data-js-filter]')];
  const items = [...root.querySelectorAll('[data-js-church]')];
  const count = root.querySelector('[data-js-count]');
  buttons.forEach((button) => button.addEventListener('click', () => {
    const value = button.dataset.jsFilter;
    buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
    items.forEach((li) => { li.hidden = value !== 'all' && li.dataset.jsChurch !== value; });
    if (count) count.textContent = counts[value] ?? '';
  }));
}
