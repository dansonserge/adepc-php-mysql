// Entry point: each component's script loads only on pages that have it.
import { initHeader } from './header.js';
import { initImages } from './images.js';

const all = (selector) => [...document.querySelectorAll(selector)];
const when = (selector, load) => {
  const els = all(selector);
  if (els.length) load().then((m) => els.forEach((el) => m.init(el)));
};

initImages();
initHeader();
when('[data-js-hero-video]', () => import('./hero-video.js'));
when('[data-js-film]', () => import('./film.js'));
when('[data-js-gallery]', () => import('./gallery.js'));
when('[data-js-copy]', () => import('./copy.js'));
when('[data-js-map]', () => import('./map.js'));
when('[data-js-paypal]', () => import('./paypal.js'));
when('[data-js-contact]', () => import('./contact.js'));
when('[data-js-consent-template]', () => import('./consent.js'));
import('./motion.js').then((m) => m.init());
