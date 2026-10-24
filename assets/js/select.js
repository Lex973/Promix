/* PROMIX — выпадающий список в стиле сайта (template-parts/ui/select.php).

   Источник правды — спрятанный <select>: кнопка показывает его значение,
   выбор в меню пишет в него и шлёт change, как родной список. Меню
   собирается из <option> при каждом открытии — так в нём всегда свежие
   строки и метки. Слушатели — на document, поэтому списки в разметке,
   подменённой аяксом, работают без повторной настройки.
*/

(function () {
  'use strict';

  var open = null;
  var uid = 0;

  function parts(box) {
    return {
      native: box.querySelector('.select__native'),
      btn: box.querySelector('.select__btn'),
      value: box.querySelector('.select__value'),
      list: box.querySelector('.select__list')
    };
  }

  function textOf(option) {
    return option.getAttribute('data-text') || option.textContent.trim();
  }

  /* Надпись на кнопке — по значению системного списка */
  function render(box) {
    var el = parts(box);
    var option = el.native.options[el.native.selectedIndex];

    if (option) {
      el.value.textContent = textOf(option);
    }

    el.btn.classList.toggle('is-placeholder', !option || !option.value);
  }

  function items(box) {
    return Array.prototype.slice.call(box.querySelectorAll('.select__option'));
  }

  function build(box) {
    var el = parts(box);
    var html = document.createDocumentFragment();

    Array.prototype.forEach.call(el.native.options, function (option) {
      if (option.disabled && !option.value) {
        return;
      }

      var li = document.createElement('li');
      var text = document.createElement('span');

      li.className = 'select__option' + (option.className ? ' ' + option.className : '');
      li.id = 'select-opt-' + (++uid);
      li.setAttribute('role', 'option');
      li.setAttribute('tabindex', '-1');
      li.setAttribute('aria-selected', option.selected ? 'true' : 'false');
      li.setAttribute('aria-label', option.textContent.trim());
      li.setAttribute('data-value', option.value);

      text.className = 'select__text';
      text.textContent = textOf(option);
      li.appendChild(text);

      if (option.getAttribute('data-hint')) {
        var hint = document.createElement('span');
        hint.className = 'select__hint';
        hint.textContent = option.getAttribute('data-hint');
        li.appendChild(hint);
      }

      html.appendChild(li);
    });

    el.list.replaceChildren(html);
  }

  /* Меню не должно уходить за край экрана: справа прижимаем к правому
     краю поля; снизу не помещается, а над полем (под липкой шапкой)
     места хватает — открываем вверх */
  function place(box) {
    var list = parts(box).list;
    var header = document.querySelector('[data-header]');
    var limit = Math.max(header ? header.getBoundingClientRect().bottom : 0, 0) + 8;

    box.classList.remove('select--right', 'select--up');

    var rect = list.getBoundingClientRect();
    var field = list.parentNode.getBoundingClientRect();

    if (rect.right > document.documentElement.clientWidth - 8) {
      box.classList.add('select--right');
    }

    if (rect.bottom > window.innerHeight - 8 && field.top - rect.height - 6 >= limit) {
      box.classList.add('select--up');
    }
  }

  function focusItem(box, item) {
    items(box).forEach(function (el) {
      el.classList.toggle('is-active', el === item);
    });

    if (!item) {
      return;
    }

    item.focus({ preventScroll: true });

    /* Прокручиваем только меню: scrollIntoView потянул бы и страницу */
    var list = item.parentNode;

    if (item.offsetTop < list.scrollTop) {
      list.scrollTop = item.offsetTop;
    } else if (item.offsetTop + item.offsetHeight > list.scrollTop + list.clientHeight) {
      list.scrollTop = item.offsetTop + item.offsetHeight - list.clientHeight;
    }
  }

  function show(box) {
    if (open && open !== box) {
      hide(open);
    }

    var el = parts(box);

    clearTimeout(box.hideTimer);
    build(box);
    el.list.hidden = false;
    place(box);
    el.btn.setAttribute('aria-expanded', 'true');
    open = box;

    /* Кадр между показом и классом — иначе появление не анимируется */
    requestAnimationFrame(function () {
      el.list.classList.add('is-open');
    });

    focusItem(box, el.list.querySelector('[aria-selected="true"]') || items(box)[0]);
  }

  function hide(box, focusBtn) {
    var el = parts(box);

    el.list.classList.remove('is-open');
    el.btn.setAttribute('aria-expanded', 'false');

    if (focusBtn) {
      el.btn.focus();
    }

    if (open === box) {
      open = null;
    }

    /* Прячем после анимации, чтобы меню не исчезало рывком */
    clearTimeout(box.hideTimer);
    box.hideTimer = setTimeout(function () {
      if (open !== box) {
        el.list.hidden = true;
      }
    }, 150);
  }

  function choose(box, item) {
    var native = parts(box).native;

    if (native.value !== item.getAttribute('data-value')) {
      native.value = item.getAttribute('data-value');
      native.dispatchEvent(new Event('change', { bubbles: true }));
    }

    hide(box, true);
  }

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('.select__btn');
    var item = event.target.closest('.select__option');

    if (btn) {
      var box = btn.closest('.select');

      if (open === box) {
        hide(box);
      } else {
        show(box);
      }
      return;
    }

    if (item) {
      choose(item.closest('.select'), item);
      return;
    }

    if (open && !open.contains(event.target)) {
      hide(open);
    }
  });

  /* Клавиатура как у родного списка: стрелки открывают и ведут по меню,
     Home/End — в начало и конец, Enter и пробел выбирают, Esc и Tab закрывают */
  document.addEventListener('keydown', function (event) {
    var box = event.target.closest && event.target.closest('.select');

    if (!box || event.target.matches('.select__native')) {
      return;
    }

    var key = event.key;

    if (open !== box) {
      if (key === 'ArrowDown' || key === 'ArrowUp') {
        event.preventDefault();
        show(box);
      }
      return;
    }

    var list = items(box);
    var current = list.indexOf(document.activeElement);

    if (key === 'Escape') {
      event.preventDefault();
      hide(box, true);
    } else if (key === 'Tab') {
      hide(box);
    } else if (key === 'ArrowDown' || key === 'ArrowUp') {
      event.preventDefault();
      var next = current + (key === 'ArrowDown' ? 1 : -1);
      focusItem(box, list[(next + list.length) % list.length]);
    } else if (key === 'Home' || key === 'End') {
      event.preventDefault();
      focusItem(box, list[key === 'Home' ? 0 : list.length - 1]);
    } else if ((key === 'Enter' || key === ' ') && current !== -1) {
      event.preventDefault();
      choose(box, list[current]);
    }
  });

  document.addEventListener('change', function (event) {
    if (event.target.matches && event.target.matches('.select__native')) {
      render(event.target.closest('.select'));
    }
  });

  /* Плавная прокрутка двигает страницу и после клика — меню
     пересчитывает сторону раз в кадр, пока открыто */
  var ticking = false;

  window.addEventListener('scroll', function () {
    if (!open || ticking) {
      return;
    }

    ticking = true;
    requestAnimationFrame(function () {
      ticking = false;

      if (open) {
        place(open);
      }
    });
  }, { passive: true });

  /* Браузер возвращает выбор после «Назад» — надпись догоняет его */
  window.addEventListener('pageshow', function () {
    Array.prototype.forEach.call(document.querySelectorAll('.select'), render);
  });
})();
