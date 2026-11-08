// Small conveniences on top of plain forms. Everything works without this file.

// On a phone the admin menu swipes sideways, so start with the current page in view.
document.querySelector('.admin-nav nav [aria-current="page"]')?.scrollIntoView({ block: 'nearest', inline: 'center' });

// Close the sort and Shop menus on a click elsewhere or on Escape, as any menu would.
document.querySelectorAll('details.sort, details.nav-menu').forEach((menu) => {
  document.addEventListener('click', (event) => {
    if (! menu.contains(event.target)) {
      menu.open = false;
    }
  });

  menu.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menu.open) {
      menu.open = false;
      menu.querySelector('summary').focus();
    }
  });
});

// Let people check the password they typed.
document.querySelectorAll('.auth-page input[type="password"]').forEach((input) => {
  const wrap = document.createElement('span');
  const toggle = document.createElement('button');

  wrap.className = 'password-wrap';
  toggle.className = 'password-toggle';
  toggle.type = 'button';
  toggle.textContent = 'Show';
  toggle.setAttribute('aria-label', 'Show password');

  toggle.addEventListener('click', () => {
    const hidden = input.type === 'password';

    input.type = hidden ? 'text' : 'password';
    toggle.textContent = hidden ? 'Hide' : 'Show';
    toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
  });

  input.before(wrap);
  wrap.append(input, toggle);
});

// Ask before destructive actions.
document.querySelectorAll('[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm)) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  });
});

// Stop a second click while a form is sending. The server copes with one anyway.
document.querySelectorAll('[data-submit-once]').forEach((form) => {
  form.addEventListener('submit', () => {
    // Deferred so the browser still sends the button's own name and value.
    setTimeout(() => {
      form.querySelectorAll('[type="submit"]').forEach((button) => {
        button.disabled = true;
      });
    });
  });
});

// A page restored by the Back button keeps disabled buttons, so turn them back on.
window.addEventListener('pageshow', (event) => {
  if (event.persisted) {
    document.querySelectorAll('[data-submit-once] [type="submit"]').forEach((button) => {
      button.disabled = false;
    });
  }
});

// Photos chosen for upload, shown in the 4:5 shape the store crops them to. Each pick
// adds to the last, and any photo can be dropped before saving. A post over the
// server's limit is lost whole with a misleading error, so a pick that would go over
// is turned away here, and what was already chosen stays.
document.querySelectorAll('input[data-previews]').forEach((input) => {
  const limit = Number(input.dataset.maxBytes);
  const box = document.getElementById(input.dataset.previews);
  const list = box.querySelector('ul');
  const isSame = (a, b) => a.name === b.name && a.size === b.size && a.lastModified === b.lastModified;
  let chosen = [...input.files];

  const preview = (file) => {
    const item = document.createElement('li');
    const image = document.createElement('img');
    const name = document.createElement('span');
    const remove = document.createElement('button');

    item.className = 'relative';
    image.className = 'mb-1 block h-30 w-24 rounded-lg bg-stone-100 object-cover';
    image.alt = '';
    image.src = URL.createObjectURL(file);
    image.addEventListener('load', () => URL.revokeObjectURL(image.src));
    name.className = 'block w-24 truncate text-xs text-stone-500';
    name.textContent = file.name;
    remove.className = 'absolute top-1 right-1 grid size-7 place-items-center rounded-full bg-white/90 text-base/none text-stone-900 shadow-xs transition-colors hover:bg-white hover:text-red-700';
    remove.type = 'button';
    remove.textContent = '×';
    remove.setAttribute('aria-label', `Remove ${file.name}`);
    remove.addEventListener('click', () => {
      chosen = chosen.filter((photo) => photo !== file);
      show();
      input.focus();
    });
    item.append(image, name, remove);

    return item;
  };

  const show = () => {
    const files = new DataTransfer();

    chosen.forEach((file) => files.items.add(file));
    input.files = files.files;
    list.replaceChildren(...chosen.map(preview));
    box.hidden = chosen.length === 0;
  };

  input.addEventListener('change', () => {
    const added = [...input.files].filter((file) => !chosen.some((photo) => isSame(photo, file)));
    const total = [...chosen, ...added].reduce((sum, file) => sum + file.size, 0);

    if (limit > 0 && total > limit) {
      window.alert('Those photos are too big to send together. Save what you have, then add the rest.');
    } else {
      chosen = [...chosen, ...added];
    }

    show();
  });

  show();
});

// The stock grid on the product form follows the ticked colours and sizes. A piece
// with neither uses its plain stock box, and enquiry pieces have no stock to count.
document.querySelectorAll('[data-variants]').forEach((box) => {
  const form = box.closest('form');
  const grid = box.querySelector('[data-variant-grid]');
  const plainStock = form.querySelector('[data-plain-stock]');
  const enquiry = form.querySelector('[name="is_enquiry_only"]');
  const ticked = (name) => new Set([...box.querySelectorAll(`[name="${name}[]"]:checked`)].map((input) => input.value));

  const update = () => {
    const colours = ticked('colours');
    const sizes = ticked('sizes');
    const hasOptions = colours.size + sizes.size > 0;
    // Row and column 0 stand in when only one of the two lists is in use.
    const shows = (chosen, id) => (chosen.size > 0 ? chosen.has(id) : id === '0');

    grid.hidden = !hasOptions || enquiry.checked;
    plainStock.hidden = hasOptions;
    grid.querySelectorAll('[data-colour]').forEach((row) => {
      row.hidden = !shows(colours, row.dataset.colour);
    });
    grid.querySelectorAll('[data-size]').forEach((cell) => {
      cell.hidden = !shows(sizes, cell.dataset.size);
    });
  };

  box.addEventListener('change', update);
  enquiry.addEventListener('change', update);
  update();
});

// On a product page, picking a colour shows which sizes it has left, and the stock
// line and quantity follow the combination. The cart checks again when adding.
document.querySelectorAll('[data-variant-stock]').forEach((form) => {
  const stock = JSON.parse(form.dataset.variantStock);
  const lowStock = Number(form.dataset.lowStock);
  const status = form.parentElement.querySelector('[data-variant-status]');
  const quantity = form.querySelector('[name="quantity"]');
  const colours = [...form.querySelectorAll('[name="colour"]')];
  const sizes = [...form.querySelectorAll('[name="size"]')];
  // Empty for a piece without that list, and undefined until one is picked.
  const picked = (radios) => (radios.length === 0 ? '' : radios.find((radio) => radio.checked)?.value);
  const left = (colour, size) => stock[colour]?.[size] ?? 0;

  const update = () => {
    const colour = picked(colours);

    if (colour !== undefined) {
      sizes.forEach((radio) => {
        radio.disabled = left(colour, radio.value) < 1;
        radio.checked = radio.checked && !radio.disabled;
      });
    }

    const size = picked(sizes);

    if (colour === undefined || size === undefined) {
      return;
    }

    const units = left(colour, size);

    status.textContent = units <= lowStock ? `Only ${units} left` : 'In stock';
    status.classList.toggle('status-pending', units <= lowStock);
    status.classList.toggle('status-delivered', units > lowStock);
    quantity.max = Math.min(units, 99);
    quantity.value = Math.min(Number(quantity.value) || 1, units);
  };

  form.addEventListener('change', update);
  update();
});

// Product photos swipe and link without this. It glides to a photo instead of
// jumping the page, and marks the one on show.
document.querySelectorAll('[data-gallery]').forEach((gallery) => {
  const track = gallery.querySelector('.gallery-track');
  const thumbs = [...gallery.querySelectorAll('.gallery-thumb')];
  const stayStill = document.documentElement.dataset.motion !== 'always'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  thumbs.forEach((thumb, index) => {
    thumb.addEventListener('click', (event) => {
      event.preventDefault();
      track.scrollTo({ left: index * track.clientWidth, behavior: stayStill ? 'auto' : 'smooth' });
    });
  });

  track.addEventListener('scroll', () => {
    const shown = Math.round(track.scrollLeft / track.clientWidth);

    thumbs.forEach((thumb, index) => {
      if (index === shown) {
        thumb.setAttribute('aria-current', 'true');
      } else {
        thumb.removeAttribute('aria-current');
      }
    });
  }, { passive: true });
});

// Scroll motion. Only things still below the fold are hidden, and only from here,
// so nothing can stay invisible if this file fails to load.
(() => {
  const alwaysMoves = document.documentElement.dataset.motion === 'always';

  if (!alwaysMoves && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const reveal = (element, delay) => {
    // Children have transitions of their own, such as hover zooms, so only count the reveal.
    const finish = (event) => {
      if (event.target === element || event.propertyName === 'clip-path') {
        element.classList.remove('is-revealing');
        element.removeEventListener('transitionend', finish);
      }
    };

    element.addEventListener('transitionend', finish);
    element.style.setProperty('--reveal-delay', `${delay}ms`);
    element.classList.replace('is-waiting', 'is-revealing');
  };

  // Things that arrive together follow each other in, up to a limit.
  const observer = new IntersectionObserver((entries) => {
    entries.filter((entry) => entry.isIntersecting).forEach((entry, index) => {
      observer.unobserve(entry.target);
      reveal(entry.target, Math.min(index, 5) * 80);
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });

  document.querySelectorAll('[data-reveal]').forEach((element) => {
    if (element.getBoundingClientRect().top > window.innerHeight * 0.9) {
      element.classList.add('is-waiting');
      observer.observe(element);
    }
  });
})();
