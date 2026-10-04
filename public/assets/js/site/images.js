// Like next/image: drop the blurred placeholder once the real image has loaded.
const clear = (img) => {
  ['background-image', 'background-size', 'background-position', 'background-repeat'].forEach((p) => img.style.removeProperty(p));
};
export function initImages() {
  document.querySelectorAll('img[data-nimg]').forEach((img) => {
    if (!img.style.backgroundImage) return;
    if (img.complete && img.naturalWidth) clear(img);
    else img.addEventListener('load', () => clear(img), { once: true });
  });
}
