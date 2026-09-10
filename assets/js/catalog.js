/* PROMIX — каталог: поиск, фильтры и сортировка.

   Пока всё считается на клиенте по данным в разметке: товаров немного,
   и так вёрстку видно живьём. С WooCommerce фильтрация уедет на сервер,
   а разметка и классы останутся теми же.
*/

(function () {
  'use strict';

  var root = document.querySelector('[data-products]');

  if (!root) {
    return;
  }

  var items = Array.prototype.slice.call(root.querySelectorAll('[data-product]'));
  var order = items.slice();
  var search = document.querySelector('[data-catalog-search]');
  var clearBtn = document.querySelector('[data-search-clear]');
  var sortSelect = document.querySelector('[data-catalog-sort]');
  var counter = document.querySelector('[data-catalog-count]');
  var empty = document.querySelector('[data-products-empty]');
  var minInput = document.querySelector('[data-filter-min]');
  var maxInput = document.querySelector('[data-filter-max]');

  function checkedValues(selector) {
    return Array.prototype.slice
      .call(document.querySelectorAll(selector + ':checked'))
      .map(function (el) { return el.value; });
  }

  function plural(n) {
    var ten = n % 10;
    var hundred = n % 100;

    if (ten === 1 && hundred !== 11) {
      return 'товар';
    }
    if (ten >= 2 && ten <= 4 && (hundred < 10 || hundred >= 20)) {
      return 'товара';
    }
    return 'товаров';
  }

  function apply() {
    var query = (search && search.value || '').trim().toLowerCase();
    var cats = checkedValues('[data-filter-cat]');
    var brands = checkedValues('[data-filter-brand]');
    var min = parseFloat(minInput && minInput.value) || 0;
    var max = parseFloat(maxInput && maxInput.value) || Infinity;
    var shown = 0;

    items.forEach(function (item) {
      var price = parseFloat(item.getAttribute('data-price')) || 0;
      var ok =
        (!query || item.getAttribute('data-search').indexOf(query) !== -1) &&
        (!cats.length || cats.indexOf(item.getAttribute('data-cat')) !== -1) &&
        (!brands.length || brands.indexOf(item.getAttribute('data-brand')) !== -1) &&
        price >= min && price <= max;

      item.hidden = !ok;

      if (ok) {
        shown += 1;
      }
    });

    if (counter) {
      counter.textContent = 'Показано ' + shown + ' ' + plural(shown);
    }

    if (empty) {
      empty.hidden = shown !== 0;
    }

    if (clearBtn) {
      clearBtn.hidden = !query;
    }
  }

  function sort(mode) {
    var sorted = order.slice();

    if (mode === 'price-asc' || mode === 'price-desc') {
      sorted.sort(function (a, b) {
        var diff = parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
        return mode === 'price-asc' ? diff : -diff;
      });
    } else if (mode === 'name') {
      sorted.sort(function (a, b) {
        return a.getAttribute('data-search').localeCompare(b.getAttribute('data-search'), 'ru');
      });
    }

    /* Переставляем разом, чтобы браузер не пересчитывал сетку на каждой карточке */
    var frag = document.createDocumentFragment();
    sorted.forEach(function (el) { frag.appendChild(el); });
    root.appendChild(frag);
  }

  if (search) {
    search.addEventListener('input', apply);
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      search.value = '';
      search.focus();
      apply();
    });
  }

  document.querySelectorAll('[data-filter-cat], [data-filter-brand]').forEach(function (el) {
    el.addEventListener('change', apply);
  });

  [minInput, maxInput].forEach(function (el) {
    if (el) {
      el.addEventListener('input', apply);
    }
  });

  if (sortSelect) {
    sortSelect.addEventListener('change', function () {
      sort(sortSelect.value);
    });
  }

  var reset = document.querySelector('[data-filters-reset]');

  if (reset) {
    reset.addEventListener('click', function () {
      document.querySelectorAll('[data-filter-cat], [data-filter-brand]').forEach(function (el) {
        el.checked = false;
      });

      [minInput, maxInput].forEach(function (el) {
        if (el) {
          el.value = '';
        }
      });

      if (search) {
        search.value = '';
      }

      if (sortSelect) {
        sortSelect.value = 'default';
        sort('default');
      }

      apply();
    });
  }

  /* ===== Длинный список брендов ===== */

  var more = document.querySelector('[data-filter-more]');
  var moreList = document.querySelector('[data-filter-more-list]');

  if (more && moreList) {
    more.addEventListener('click', function () {
      var open = moreList.classList.toggle('is-open');

      more.setAttribute('aria-expanded', open ? 'true' : 'false');
      more.textContent = open ? 'Свернуть' : 'Показать все';
    });
  }

  /* ===== Панель фильтров на узких экранах ===== */

  var panel = document.querySelector('[data-filters]');
  var overlay = document.querySelector('[data-filters-overlay]');
  var openBtn = document.querySelector('[data-filters-open]');

  if (!panel || !overlay || !openBtn) {
    return;
  }

  var releaseTrap = null;
  var lastFocused = null;

  function openFilters() {
    lastFocused = document.activeElement;
    overlay.hidden = false;

    /* Кадр между показом и классом — иначе подложка не проявляется */
    requestAnimationFrame(function () {
      panel.classList.add('is-open');
      overlay.classList.add('is-open');
    });

    openBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('is-menu-open');

    if (window.promixLenis) {
      window.promixLenis.stop();
    }

    if (window.promixTrap) {
      releaseTrap = window.promixTrap(panel, overlay);
    }
  }

  function closeFilters() {
    panel.classList.remove('is-open');
    overlay.classList.remove('is-open');
    openBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('is-menu-open');

    var hide = function () { overlay.hidden = true; };
    overlay.addEventListener('transitionend', hide, { once: true });
    setTimeout(hide, 500);

    if (releaseTrap) {
      releaseTrap();
      releaseTrap = null;
    }

    if (window.promixLenis) {
      window.promixLenis.start();
    }

    if (lastFocused) {
      lastFocused.focus();
    }
  }

  openBtn.addEventListener('click', openFilters);
  overlay.addEventListener('click', closeFilters);

  document.querySelectorAll('[data-filters-close]').forEach(function (el) {
    el.addEventListener('click', closeFilters);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && panel.classList.contains('is-open')) {
      closeFilters();
    }
  });

  /* Панель нужна только узким экранам: на широких колонка видна всегда */
  var wide = window.matchMedia('(min-width: 981px)');

  var onWide = function (event) {
    if (event.matches && panel.classList.contains('is-open')) {
      closeFilters();
    }
  };

  if (wide.addEventListener) {
    wide.addEventListener('change', onWide);
  } else {
    wide.addListener(onWide);
  }
})();
