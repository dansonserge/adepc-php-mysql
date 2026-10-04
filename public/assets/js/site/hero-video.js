// Muted background loop over the poster. Starts after page load, never with
// reduced motion, Save-Data or 2g/3g; pausable (WCAG 2.2.2).
export function init(section) {
  const cfg = JSON.parse(section.dataset.jsHeroVideo);
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const conn = navigator.connection;
  const slow = conn?.saveData || /(^|-)(2g|3g)$/.test(conn?.effectiveType ?? '');
  if (reduce || slow) return;

  const start = () => {
    const video = document.createElement('video');
    video.className = 'absolute inset-0 -z-20 h-full w-full object-cover transition-opacity duration-1000 opacity-0';
    video.muted = true;
    video.defaultMuted = true;
    Object.assign(video, { autoplay: true, loop: true, playsInline: true, preload: 'auto' });
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('aria-hidden', 'true');
    video.tabIndex = -1;
    if (cfg.webm) video.append(Object.assign(document.createElement('source'), { src: cfg.webm, type: 'video/webm' }));
    const mp4 = Object.assign(document.createElement('source'), { src: cfg.mp4, type: 'video/mp4' });
    mp4.addEventListener('error', () => { video.remove(); button?.remove(); });
    video.append(mp4);

    let button = null;
    video.addEventListener('playing', () => {
      video.classList.replace('opacity-0', 'opacity-100');
      if (button) return;
      button = document.createElement('button');
      button.type = 'button';
      button.className = 'absolute right-(--edge) bottom-28 z-20 flex h-11 w-11 items-center justify-center border border-white/50 bg-ink/40 text-white backdrop-blur-sm transition-colors hover:bg-ink md:bottom-32';
      const render = () => {
        button.setAttribute('aria-label', video.paused ? cfg.play : cfg.pause);
        button.innerHTML = video.paused ? cfg.playIcon : cfg.pauseIcon;
      };
      button.addEventListener('click', () => {
        if (video.paused) void video.play(); else video.pause();
        render();
      });
      render();
      video.after(button);
    }, { once: true });

    // Same layer order as before: poster, video, shade, content.
    section.firstElementChild.after(video);
  };

  if (document.readyState === 'complete') window.setTimeout(start, 200);
  else window.addEventListener('load', start, { once: true });
}
