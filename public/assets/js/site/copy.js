export function init(button) {
  const label = button.querySelector('span');
  const original = label.textContent;
  button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(button.dataset.jsCopy);
      label.textContent = button.dataset.jsCopied;
      window.setTimeout(() => { label.textContent = original; }, 2000);
    } catch {
      // Clipboard can be blocked; the value stays selectable on the page.
    }
  });
}
