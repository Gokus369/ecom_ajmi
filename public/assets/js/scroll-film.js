// Scrubs the current RUA brand film forwards and backwards with the page.
(() => {
  // The wordmark is baked into the frames: its share of their width, and when it leaves.
  const WORDMARK_WIDTH = 0.42;
  const ZOOM_IN_FROM = 0.33;
  const ZOOM_IN_TO = 0.48;
  // How gently the film catches up with the scroll, in milliseconds. Higher is smoother.
  const GLIDE_MS = 160;

  const film = document.querySelector('[data-film]');
  const root = document.documentElement;
  if (!film || !root.classList.contains('has-film')) return;

  const fallback = () => root.classList.remove('has-film');
  const canvas = film.querySelector('canvas');
  const context = canvas.getContext('2d');
  if (!context) return fallback();

  // Phones in either orientation get the lighter set, because 4K costs them 34 MB.
  const phone = window.matchMedia('(max-width: 768px), (max-height: 500px)').matches;

  let set;
  try {
    const sets = JSON.parse(film.dataset.frames);
    set = sets[phone ? 'mobile' : 'desktop'] ?? Object.values(sets)[0];
    if (!set || set.count < 1) return fallback();
  } catch {
    return fallback();
  }

  const { count, url, version, width, height } = set;
  const stride = phone ? 2 : 1;
  const scroll = film.querySelector('.film-scroll');
  const stage = film.querySelector('.film-stage');
  const bar = film.querySelector('.film-bar i');
  const hint = film.querySelector('.film-hint');
  const loading = film.querySelector('.film-loading');
  const frames = new Array(count);
  let target = 0;
  let current = 0;
  let drawn = -1;
  let ticking = 0;
  let previousTime = 0;
  let openingZoom = 1;

  canvas.width = width;
  canvas.height = height;
  const clamp = (value) => Math.min(1, Math.max(0, value));
  const frameUrl = (index) => `${url}/frame_${String(index + 1).padStart(4, '0')}.webp?v=${version}`;

  const draw = () => {
    const wanted = Math.round(current);
    for (let gap = 0; gap < count; gap++) {
      const index = frames[wanted - gap] ? wanted - gap : frames[wanted + gap] ? wanted + gap : -1;
      if (index < 0) continue;
      if (index !== drawn) {
        context.drawImage(frames[index], 0, 0);
        drawn = index;
      }
      break;
    }
    const played = count > 1 ? current / (count - 1) : 1;
    // While frames load, an earlier one stays on screen and its wordmark must stay whole.
    const shown = current - drawn > stride ? drawn / Math.max(1, count - 1) : played;
    const zoomIn = clamp((shown - ZOOM_IN_FROM) / (ZOOM_IN_TO - ZOOM_IN_FROM));
    canvas.style.setProperty('--film-zoom', openingZoom + (1 - openingZoom) * zoomIn * zoomIn * (3 - 2 * zoomIn));
    bar.style.transform = `scaleX(${played})`;
  };

  // Time-based easing keeps wheel and touch scrubbing consistent across refresh rates.
  const tick = (time) => {
    ticking = 0;
    const elapsed = previousTime ? Math.min(time - previousTime, 64) : 16.67;
    previousTime = time;
    current += (target - current) * (1 - Math.exp(-elapsed / GLIDE_MS));
    if (Math.abs(target - current) < 0.02) current = target;
    draw();
    if (current !== target) ticking = requestAnimationFrame(tick);
    else previousTime = 0;
  };

  const update = () => {
    const distance = Math.max(1, scroll.offsetHeight - stage.offsetHeight);
    const progress = clamp(-scroll.getBoundingClientRect().top / distance);
    target = progress * (count - 1);
    openingZoom = Math.min(1, stage.offsetWidth / (WORDMARK_WIDTH * stage.offsetHeight * width / height));
    hint.style.opacity = progress > 0.02 ? 0 : 1;
    if (!ticking) ticking = requestAnimationFrame(tick);
  };

  const preload = async () => {
    // Endpoints first, then broad coverage, then fill in the gaps.
    const order = new Set([0, count - 1]);
    for (let step = 64; step >= stride; step /= 2) {
      for (let index = 0; index < count; index += step) order.add(index);
    }
    const queue = [...order];
    const total = queue.length;
    let finished = 0;
    let loaded = 0;
    const worker = async () => {
      while (queue.length) {
        const index = queue.shift();
        const image = new Image();
        image.src = frameUrl(index);
        try {
          await image.decode();
          frames[index] = image;
          loaded++;
          draw();
        } catch {
          // A missing frame uses its nearest loaded neighbour.
        }
        finished++;
        loading.textContent = `Loading film ${Math.round(finished / total * 100)}%`;
      }
    };
    await Promise.all(Array.from({ length: 4 }, worker));
    loading.style.opacity = 0;
    if (!loaded) {
      window.removeEventListener('scroll', update);
      window.removeEventListener('resize', update);
      cancelAnimationFrame(ticking);
      fallback();
    }
  };

  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
  current = target;
  preload();
})();
