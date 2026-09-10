/* PROMIX — каталог: поиск, фильтры и сортировка.

   Пока всё считается на клиенте по данным в разметке: товаров немного,
   и так вёрстку видно живьём. С WooCommerce фильтрация уедет на сервер,
   а разметка и классы останутся теми же.

   Поиск и сортировка срабатывают сразу, фильтры — по кнопке «Применить»:
   отмечать три категории и ждать перерисовку после каждой галочки незачем.
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
  var counter = document.querySelector('[data-catalog-count]');
  var empty = document.querySelector('[data-products-empty]');
  var minInput = document.querySelector('[data-filter-min]');
  var maxInput = document.querySelector('[data-filter-max]');
  var applyBtn = document.querySelector('[data-filters-apply]');

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

    if (applyBtn) {
      applyBtn.classList.remove('is-waiting');
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

  /* Фильтры изменили, но не применили — подсвечиваем кнопку */
  function pending() {
    if (applyBtn) {
      applyBtn.classList.add('is-waiting');
    }
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
    el.addEventListener('change', pending);
  });

  [minInput, maxInput].forEach(function (el) {
    if (el) {
      el.addEventListener('input', pending);
    }
  });

  /* ===== Сортировка ===== */

  var sortBox = document.querySelector('[data-sort]');

  if (sortBox) {
    var sortBtn = sortBox.querySelector('[data-sort-toggle]');
    var sortList = sortBox.querySelector('[data-sort-list]');
    var sortValue = sortBox.querySelector('[data-sort-value]');
    var options = Array.prototype.slice.call(sortBox.querySelectorAll('[data-sort-option]'));
    var hideTimer = null;

    var openSort = function () {
      clearTimeout(hideTimer);
      sortList.hidden = false;

      /* Кадр между показом и классом — иначе появление не анимируется */
      requestAnimationFrame(function () {
        sortList.classList.add('is-open');
      });

      sortBtn.setAttribute('aria-expanded', 'true');
    };

    var closeSort = function () {
      sortList.classList.remove('is-open');
      sortBtn.setAttribute('aria-expanded', 'false');

      options.forEach(function (o) { o.classList.remove('is-active'); });

      /* Прячем после анимации, чтобы список не исчезал рывком */
      clearTimeout(hideTimer);
      hideTimer = setTimeout(function () { sortList.hidden = true; }, 180);
    };

    var isOpen = function () {
      return sortBtn.getAttribute('aria-expanded') === 'true';
    };

    var choose = function (option) {
      options.forEach(function (o) {
        o.setAttribute('aria-selected', o === option ? 'true' : 'false');
      });

      sortValue.textContent = option.textContent.trim();
      sort(option.getAttribute('data-sort-option'));
      closeSort();
      sortBtn.focus();
    };

    sortBtn.addEventListener('click', function () {
      if (isOpen()) {
        closeSort();
      } else {
        openSort();
      }
    });

    options.forEach(function (option) {
      option.addEventListener('click', function () { choose(option); });
    });

    /* Клавиатура: стрелки ведут по списку, Enter выбирает, Esc закрывает */
    sortBox.addEventListener('keydown', function (event) {
      var current = options.indexOf(document.activeElement);

      if (event.key === 'Escape' && isOpen()) {
        closeSort();
        sortBtn.focus();
        return;
      }

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();

        if (!isOpen()) {
          openSort();
        }

        var next = event.key === 'ArrowDown' ? current + 1 : current - 1;

        if (next < 0) {
          next = options.length - 1;
        }
        if (next >= options.length) {
          next = 0;
        }

        options.forEach(function (o) { o.classList.remove('is-active'); });
        options[next].classList.add('is-active');
        options[next].focus();
        return;
      }

      if ((event.key === 'Enter' || event.key === ' ') && current !== -1) {
        event.preventDefault();
        choose(options[current]);
      }
    });

    document.addEventListener('click', function (event) {
      if (isOpen() && !sortBox.contains(event.target)) {
        closeSort();
      }
    });
  }

  /* ===== Кнопки «Применить» и «Сбросить» ===== */

  if (applyBtn) {
    applyBtn.addEventListener('click', apply);
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

      apply();
    });
  }

  /* ===== Списки, подрезанные до нескольких пунктов ===== */

  document.querySelectorAll('[data-filter-more]').forEach(function (btn) {
    var list = btn.parentNode.querySelector('[data-filter-more-list]');

    if (!list) {
      return;
    }

    btn.addEventListener('click', function () {
      var open = list.classList.toggle('is-open');

      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.textContent = open ? 'Свернуть' : 'Показать все';
    });
  });

  /* ===== Категория из ссылки: /katalog/?cat=Валики и ручки ===== */

  var fromUrl = new URLSearchParams(window.location.search).get('cat');

  if (fromUrl) {
    var target = document.querySelector('[data-filter-cat][value="' + fromUrl.replace(/"/g, '\\"') + '"]');

    if (target) {
      target.checked = true;

      /* Отмеченный пункт может быть в скрытой части списка — раскрываем её */
      var list = target.closest('[data-filter-more-list]');

      if (list && !list.classList.contains('is-open')) {
        var moreBtn = list.parentNode.querySelector('[data-filter-more]');

        if (moreBtn) {
          moreBtn.click();
        }
      }

      apply();
    }
  }

  /* ===== Артикул по клику копируется ===== */

  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var value = btn.getAttribute('data-copy');
      var label = btn.querySelector('span');

      var done = function () {
        if (!label) {
          return;
        }

        var was = label.textContent;

        btn.classList.add('is-copied');
        label.textContent = 'скопирован';

        setTimeout(function () {
          btn.classList.remove('is-copied');
          label.textContent = was;
        }, 1400);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(done, function () {});
        return;
      }

      /* Без защищённого соединения clipboard недоступен — старый способ */
      var tmp = document.createElement('textarea');
      tmp.value = value;
      tmp.setAttribute('readonly', '');
      tmp.style.position = 'absolute';
      tmp.style.left = '-9999px';
      document.body.appendChild(tmp);
      tmp.select();

      try {
        document.execCommand('copy');
        done();
      } catch (e) {
        /* Не скопировалось — артикул всё равно виден на карточке */
      }

      document.body.removeChild(tmp);
    });
  });

  /* ===== Отложенные товары =====
     Пока живут в браузере: список избранного появится вместе с личным
     кабинетом WooCommerce. */

  var FAV_KEY = 'promix-favourites';

  function readFavourites() {
    try {
      return JSON.parse(localStorage.getItem(FAV_KEY)) || [];
    } catch (e) {
      return [];
    }
  }

  var favourites = readFavourites();

  document.querySelectorAll('[data-fav]').forEach(function (btn) {
    var sku = btn.getAttribute('data-fav');

    if (favourites.indexOf(sku) !== -1) {
      btn.setAttribute('aria-pressed', 'true');
    }

    btn.addEventListener('click', function () {
      var on = btn.getAttribute('aria-pressed') !== 'true';

      btn.setAttribute('aria-pressed', on ? 'true' : 'false');

      var index = favourites.indexOf(sku);

      if (on && index === -1) {
        favourites.push(sku);
      } else if (!on && index !== -1) {
        favourites.splice(index, 1);
      }

      try {
        localStorage.setItem(FAV_KEY, JSON.stringify(favourites));
      } catch (e) {
        /* Приватное окно — отметка живёт до перезагрузки */
      }
    });
  });

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

  var isPanel = function () {
    return window.matchMedia('(max-width: 980px)').matches;
  };

  openBtn.addEventListener('click', openFilters);
  overlay.addEventListener('click', closeFilters);

  document.querySelectorAll('[data-filters-close]').forEach(function (el) {
    el.addEventListener('click', closeFilters);
  });

  /* В выехавшей панели «Применить» заодно её закрывает */
  if (applyBtn) {
    applyBtn.addEventListener('click', function () {
      if (isPanel()) {
        closeFilters();
      }
    });
  }

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
