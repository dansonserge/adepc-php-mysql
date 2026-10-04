// One big PLAY button over a poster; pressing it swaps in the real player.
export function init(box) {
  const cfg = JSON.parse(box.dataset.jsFilm);
  box.querySelector('[data-js-film-play]')?.addEventListener('click', () => {
    let player;
    if (cfg.kind === 'youtube') {
      player = document.createElement('iframe');
      player.className = 'absolute inset-0 h-full w-full';
      player.src = cfg.src;
      player.title = cfg.title;
      player.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      player.allowFullscreen = true;
    } else {
      player = document.createElement('video');
      player.className = 'absolute inset-0 h-full w-full bg-black object-contain';
      Object.assign(player, { controls: true, autoplay: true, playsInline: true, preload: 'auto' });
      player.setAttribute('aria-label', cfg.title);
      if (cfg.webm) player.append(Object.assign(document.createElement('source'), { src: cfg.webm, type: 'video/webm' }));
      player.append(Object.assign(document.createElement('source'), { src: cfg.mp4, type: 'video/mp4' }));
    }
    box.replaceChildren(player);
    if (player.play) player.play().catch(() => {});
  });
}
