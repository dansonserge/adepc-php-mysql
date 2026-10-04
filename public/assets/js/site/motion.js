// The single place scroll motion lives (same as MotionLayer): markup opts in with
//   data-reveal="up"     fade + rise
//   data-reveal="lines"  headline lines rise out of a mask (SplitText)
//   data-reveal="clip"   image wipes up, media settles from 1.12×
// GSAP loads after the page; under reduced motion nothing is hidden or moved.
const base = new URL('../../vendor/gsap/', import.meta.url);
const load = (file) => new Promise((resolve, reject) => {
  const s = Object.assign(document.createElement('script'), { src: new URL(file, base).href, onload: resolve, onerror: reject });
  document.head.append(s);
});

export async function init() {
  await load('gsap.min.js');
  await Promise.all([load('ScrollTrigger.min.js'), load('SplitText.min.js')]);
  const { gsap, ScrollTrigger, SplitText } = window;
  gsap.registerPlugin(ScrollTrigger, SplitText);

  const mm = gsap.matchMedia();
  mm.add('(prefers-reduced-motion: no-preference)', () => {
    const start = 'top 90%';
    gsap.utils.toArray('[data-reveal]').forEach((el) => {
      const kind = el.dataset.reveal;
      if (kind === 'lines') {
        gsap.set(el, { opacity: 1 });
        SplitText.create(el, {
          type: 'lines',
          mask: 'lines',
          autoSplit: true,
          onSplit: (self) => gsap.from(self.lines, {
            yPercent: 110, duration: 1, ease: 'expo.out', stagger: 0.08,
            scrollTrigger: { trigger: el, start, once: true },
          }),
        });
      } else if (kind === 'clip') {
        const media = el.querySelector('[data-reveal-media]');
        const tl = gsap.timeline({ scrollTrigger: { trigger: el, start, once: true } });
        tl.fromTo(el, { clipPath: 'inset(100% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.2, ease: 'expo.out' });
        if (media) tl.fromTo(media, { scale: 1.12 }, { scale: 1, duration: 1.6, ease: 'expo.out' }, 0);
      } else {
        gsap.fromTo(el, { opacity: 0, y: 28 }, {
          opacity: 1, y: 0, duration: 0.9, ease: 'expo.out',
          scrollTrigger: { trigger: el, start, once: true },
        });
      }
    });
  });

  document.documentElement.classList.add('motion-ready');
  ScrollTrigger.refresh();
}
