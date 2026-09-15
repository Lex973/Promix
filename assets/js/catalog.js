/* PROMIX — каталог: поиск, фильтры и сортировка.

   Товары выбирает сервер по параметрам адреса. Скрипт собирает адрес
   из состояния формы, забирает по нему страницу и подменяет на месте
   только результаты и заголовок — без перезагрузки и потери фокуса
   в поиске. Адрес при этом попадает в историю: его можно скопировать,
   а «назад» возвращает прошлую выдачу. Без скрипта форма работает сама.

   Поиск и сортировка срабатывают сразу, фильтры — по кнопке «Применить»:
   отмечать три категории и ждать перерисовку после каждой галочки незачем.
*/

(function () {
  'use strict';

  var section = document.querySelector('[data-catalog]');

  if (!section) {
    return;
  }

  var form = section.querySelector('[data-catalog-form]');
  var shopUrl = section.getAttribute('data-catalog-url') || window.location.pathname;
  var search = section.querySelector('[data-catalog-search]');
  var clearBtn = section.querySelector('[data-search-clear]');
  var minInput = section.querySelector('[data-filter-min]');
  var maxInput = section.querySelector('[data-filter-max]');
  var applyBtn = section.querySelector('[data-filters-apply]');
  var sortInput = section.querySelector('[data-sort-input]');

  function checkedInputs(selector) {
    return Array.prototype.slice.call(section.querySelectorAll(selector + ':checked'));
  }

  function values(inputs) {
    return inputs.map(function (el) { return el.value; });
  }

  /* ===== Адрес из состояния формы =====
     Один раздел — его собственный адрес вида /catalog/valiki-i-ruchki/,
     один бренд — /brand/storch/, несколько — общий каталог с параметрами. */
  function buildUrl() {
    var cats = checkedInputs('[data-filter-cat]');
    var brands = checkedInputs('[data-filter-brand]');
    var base = shopUrl;
    var params = new URLSearchParams();

    if (cats.length === 1 && cats[0].getAttribute('data-url')) {
      base = cats[0].getAttribute('data-url');
    } else if (cats.length > 1) {
      params.set('cat', values(cats).join(','));
    }

    /* Один бренд без раздела — его собственная страница /brand/storch/ */
    if (!cats.length && brands.length === 1 && brands[0].getAttribute('data-url')) {
      base = brands[0].getAttribute('data-url');
    } else if (brands.length) {
      params.set('brand', values(brands).join(','));
    }

    var q = (search && search.value || '').trim();
    var min = minInput && minInput.value;
    var max = maxInput && maxInput.value;
    var orderby = sortInput && sortInput.value;

    if (q) {
      params.set('q', q);
    }
    if (min) {
      params.set('min_price', min);
    }
    if (max) {
      params.set('max_price', max);
    }
    if (orderby) {
      params.set('orderby', orderby);
    }

    var query = params.toString().replace(/%2C/g, ',');

    return base + (query ? '?' + query : '');
  }

  /* ===== Загрузка выдачи ===== */

  var controller = null;

  function results() {
    return section.querySelector('[data-catalog-results]');
  }

  function fetchPage(url) {
    if (controller) {
      controller.abort();
    }

    controller = new AbortController();

    return fetch(url, {
      signal: controller.signal,
      headers: { 'X-Requested-With': 'fetch' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error(response.status);
      }
      return response.text();
    }).then(function (html) {
      return new DOMParser().parseFromString(html, 'text/html');
    });
  }

  function swap(selector, doc) {
    var current = section.querySelector(selector);
    var next = doc.querySelector(selector);

    if (current && next) {
      current.replaceWith(next);
    }
  }

  /* При «назад» форма должна показывать то, что было в том адресе */
  function syncForm(doc) {
    var other = doc.querySelector('[data-catalog-form]');

    if (!other || !form) {
      return;
    }

    Array.prototype.forEach.call(form.querySelectorAll('input[type="checkbox"]'), function (el) {
      var twin = other.querySelector('input[name="' + el.name + '"][value="' + el.value + '"]');
      el.checked = !!(twin && twin.checked);
    });

    Array.prototype.forEach.call(form.querySelectorAll('input[type="search"], input[type="text"], input[type="hidden"]'), function (el) {
      var twin = other.querySelector('input[name="' + el.name + '"]');
      el.value = twin ? twin.value : '';
    });

    var label = section.querySelector('[data-sort-value]');
    var otherLabel = other.querySelector('[data-sort-value]');

    if (label && otherLabel) {
      label.textContent = otherLabel.textContent;
    }

    Array.prototype.forEach.call(section.querySelectorAll('[data-sort-option]'), function (o) {
      o.setAttribute('aria-selected', o.getAttribute('data-sort-option') === (sortInput && sortInput.value) ? 'true' : 'false');
    });

    if (clearBtn) {
      clearBtn.hidden = !(search && search.value);
    }
  }

  function load(url, push) {
    var box = results();

    if (box) {
      box.classList.add('is-loading');
    }

    if (applyBtn) {
      applyBtn.classList.remove('is-waiting');
    }

    fetchPage(url).then(function (doc) {
      swap('[data-catalog-results]', doc);
      swap('[data-catalog-count]', doc);
      swap('[data-catalog-head]', doc);
      document.title = doc.title;
      applyView(currentView());

      if (push === 'replace') {
        /* Поиск: каждая буква — не отдельная страница в истории */
        window.history.replaceState({ promixCatalog: true }, '', url);
      } else if (push) {
        window.history.pushState({ promixCatalog: true }, '', url);
      } else {
        syncForm(doc);
      }
    }).catch(function (error) {
      /* Сеть отвалилась или отменили сами — при ошибке идём обычным переходом */
      if (error.name !== 'AbortError') {
        window.location.href = url;
      }
    });
  }

  function refresh(mode) {
    load(buildUrl(), mode || true);
  }

  /* Скрипт живёт только на страницах каталога (разделы, бренды, поиск),
     поэтому любое «назад/вперёд» — это возврат к другому состоянию выдачи */
  window.addEventListener('popstate', function () {
    load(window.location.href, false);
  });

  /* ===== Форма: «Применить», Enter в поиске ===== */

  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      refresh();
    });
  }

  /* Фильтры изменили, но не применили — подсвечиваем кнопку */
  function pending() {
    if (applyBtn) {
      applyBtn.classList.add('is-waiting');
    }
  }

  section.addEventListener('change', function (event) {
    if (event.target.matches('[data-filter-cat], [data-filter-brand]')) {
      pending();
    }
  });

  /* Поле цены текстовое, а не number: number отбрасывает значение
     со случайным пробелом целиком и просит «введите число». Здесь
     всё, кроме цифр, просто убирается. */
  [minInput, maxInput].forEach(function (el) {
    if (el) {
      el.addEventListener('input', function () {
        var clean = el.value.replace(/\D/g, '');

        if (clean !== el.value) {
          el.value = clean;
        }

        pending();
      });
    }
  });

  /* ===== Поиск: с задержкой, чтобы не дёргать сервер на каждую букву ===== */

  var searchTimer = null;

  if (search) {
    search.addEventListener('input', function () {
      if (clearBtn) {
        clearBtn.hidden = !search.value;
      }

      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { refresh('replace'); }, 350);
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      search.value = '';
      search.focus();
      clearBtn.hidden = true;
      clearTimeout(searchTimer);
      refresh();
    });
  }

  /* ===== Сброс ===== */

  var reset = section.querySelector('[data-filters-reset]');

  if (reset) {
    reset.addEventListener('click', function (event) {
      event.preventDefault();

      Array.prototype.forEach.call(section.querySelectorAll('[data-filter-cat], [data-filter-brand]'), function (el) {
        el.checked = false;
      });

      [minInput, maxInput, search, sortInput].forEach(function (el) {
        if (el) {
          el.value = '';
        }
      });

      var label = section.querySelector('[data-sort-value]');
      var first = section.querySelector('[data-sort-option]');

      if (label && first) {
        label.textContent = first.textContent.trim();
      }

      if (clearBtn) {
        clearBtn.hidden = true;
      }

      load(shopUrl, true);
    });
  }

  /* ===== «Показать ещё»: следующая страница доклеивается снизу ===== */

  section.addEventListener('click', function (event) {
    var more = event.target.closest('[data-more]');

    if (!more) {
      return;
    }

    event.preventDefault();
    more.classList.add('is-busy');
    more.setAttribute('aria-disabled', 'true');

    fetchPage(more.href).then(function (doc) {
      var grid = section.querySelector('[data-products]');
      var nextGrid = doc.querySelector('[data-products]');
      var nextMore = doc.querySelector('.catalog__more');
      var cards = null;

      if (grid && nextGrid) {
        /* children — живая коллекция: при переносе узлов она сжимается,
           и перебор пропускал бы каждую вторую карточку. Берём снимок. */
        cards = Array.prototype.slice.call(nextGrid.children);
        var frag = document.createDocumentFragment();

        cards.forEach(function (card) {
          frag.appendChild(card);
        });

        grid.appendChild(frag);
      }

      var block = more.closest('.catalog__more');

      if (nextMore) {
        block.replaceWith(nextMore);
      } else {
        block.remove();
      }

      /* Кнопка, на которой был фокус, заменена — с клавиатуры продолжаем с первой новой карточки */
      var first = cards && cards[0] ? cards[0].querySelector('a') : null;

      if (first) {
        first.focus({ preventScroll: true });
      }
    }).catch(function (error) {
      if (error.name !== 'AbortError') {
        window.location.href = more.href;
      }
    });
  });

  /* ===== Сортировка ===== */

  var sortBox = section.querySelector('[data-sort]');

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

      if (sortInput) {
        sortInput.value = option.getAttribute('data-sort-option');
      }

      closeSort();
      sortBtn.focus();
      refresh();
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

  /* ===== Списки, подрезанные до нескольких пунктов ===== */

  Array.prototype.forEach.call(section.querySelectorAll('[data-filter-more]'), function (btn) {
    var list = btn.parentNode.querySelector('[data-filter-more-list]');

    if (!list) {
      return;
    }

    btn.addEventListener('click', function () {
      var open = list.classList.toggle('is-open');

      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.textContent = btn.getAttribute(open ? 'data-label-open' : 'data-label-closed') || '';
    });
  });

  /* ===== Сеткой или списком =====
     Выбор живёт в браузере: кто смотрит списком, тот и в следующий раз
     хочет списком. */

  var VIEW_KEY = 'promix-catalog-view';
  var viewBtns = Array.prototype.slice.call(section.querySelectorAll('[data-view]'));

  function applyView(view) {
    var grid = section.querySelector('[data-products]');

    if (grid) {
      grid.classList.toggle('products--list', view === 'list');
    }

    viewBtns.forEach(function (btn) {
      btn.setAttribute('aria-pressed', btn.getAttribute('data-view') === view ? 'true' : 'false');
    });
  }

  function currentView() {
    try {
      return localStorage.getItem(VIEW_KEY) === 'list' ? 'list' : 'grid';
    } catch (e) {
      return 'grid';
    }
  }

  viewBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var view = btn.getAttribute('data-view');

      try {
        localStorage.setItem(VIEW_KEY, view);
      } catch (e) {
        /* Приватное окно — выбор живёт до перезагрузки */
      }

      applyView(view);
    });
  });

  applyView(currentView());

  /* ===== Панель фильтров на узких экранах ===== */

  var panel = section.querySelector('[data-filters]');
  var overlay = section.querySelector('[data-filters-overlay]');
  var openBtn = section.querySelector('[data-filters-open]');

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

  Array.prototype.forEach.call(section.querySelectorAll('[data-filters-close]'), function (el) {
    el.addEventListener('click', closeFilters);
  });

  /* В выехавшей панели «Применить» и «Сбросить» заодно её закрывают */
  [applyBtn, reset].forEach(function (btn) {
    if (btn) {
      btn.addEventListener('click', function () {
        if (isPanel()) {
          closeFilters();
        }
      });
    }
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
