// Mapbox GL, loaded only when the map nears the viewport.
const load = (tag, attrs) => new Promise((resolve, reject) => {
  const el = Object.assign(document.createElement(tag), attrs, { onload: resolve, onerror: reject });
  document.head.append(el);
});

export function init(el) {
  const cfg = JSON.parse(el.dataset.jsMap);
  const io = new IntersectionObserver(async ([entry]) => {
    if (!entry.isIntersecting) return;
    io.disconnect();
    try {
      if (!window.mapboxgl) {
        await Promise.all([load('link', { rel: 'stylesheet', href: cfg.css }), load('script', { src: cfg.js })]);
      }
      const mapboxgl = window.mapboxgl;
      mapboxgl.accessToken = cfg.token;
      const bounds = new mapboxgl.LngLatBounds();
      cfg.pins.forEach((p) => bounds.extend([p.lng, p.lat]));
      const map = new mapboxgl.Map({
        container: el,
        style: cfg.style,
        bounds,
        fitBoundsOptions: { padding: { top: 80, bottom: 80, left: 80, right: 160 } },
        cooperativeGestures: true,
      });
      map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
      cfg.pins.forEach((p) => {
        const a = document.createElement('a');
        a.href = p.href;
        a.className = 'map-pin';
        a.setAttribute('aria-label', p.name);
        a.textContent = p.label;
        new mapboxgl.Marker({ element: a, anchor: 'bottom-left' }).setLngLat([p.lng, p.lat]).addTo(map);
      });
    } catch {
      // The church list beside the map carries the same information.
    }
  }, { rootMargin: '400px' });
  io.observe(el);
}
