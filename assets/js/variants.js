/* PROMIX — выбор варианта товара: длина, объём.

   Варианты — радиокнопки или, когда их много, выпадающий список
   (template-parts/cart/variants.php). Выбор меняет
   цену, артикул и показывает кнопку или счётчик этого варианта; остальные
   кнопки спрятаны. Без выбора «В корзину» показывает подсказку. Добавляет
   в корзину по-прежнему cart.js — здесь только выбор и метки «в корзине».
*/

(function () {
  'use strict';

  function scopeOf(el) {
    return el.closest('[data-variants-scope]');
  }

  function checkedOption(scope) {
    var list = scope.querySelector('[data-variants-select]');

    if (list) {
      var picked = list.options[list.selectedIndex];
      return picked && picked.value ? picked : null;
    }

    var input = scope.querySelector('[data-variant-option] input:checked');
    return input ? input.closest('[data-variant-option]') : null;
  }

  /* В списке метку не нарисовать — она приписывается к строке */
  function markInCart(option, inCart) {
    option.classList.toggle('is-in-cart', inCart);

    if (option.tagName === 'OPTION') {
      option.textContent = option.getAttribute('data-line') + (inCart ? ' · в корзине' : '');
    }
  }

  function controlFor(scope, option) {
    var id = option ? option.getAttribute('data-variation') : '';
    return id ? scope.querySelector('[data-cart-control="' + id + '"]') : null;
  }

  /* Видна одна кнопка: выбранного варианта, а без выбора — общая */
  function showControl(scope, option) {
    var current = controlFor(scope, option);
    var none = scope.querySelector('[data-variants-none]');

    Array.prototype.forEach.call(scope.querySelectorAll('[data-cart-control]'), function (control) {
      control.hidden = control !== current;
    });

    if (none) {
      none.hidden = !!current;
    }

    /* У варианта без цены «Уточнить» открывает ту же форму,
       что «Спросить у технолога», — вторую кнопку прячем */
    var ask = scope.querySelector('[data-variants-ask]');

    if (ask) {
      ask.hidden = !!(current && current.querySelector('[data-lead-open]'));
    }

    return current;
  }

  function setSku(scope, sku) {
    var box = scope.querySelector('[data-variants-sku]');

    if (!box) {
      return;
    }

    var btn = box.matches('[data-copy]') ? box : box.querySelector('[data-copy]');
    var label = btn && btn.querySelector('[data-copy-label]');

    box.hidden = !sku;

    if (btn) {
      btn.setAttribute('data-copy', sku);
      btn.setAttribute('title', sku);
      btn.setAttribute('aria-label', 'Скопировать артикул ' + sku);
    }

    if (label) {
      label.textContent = sku;
    }
  }

  /* «Спросить у технолога» уходит с выбранным вариантом и его артикулом */
  function setLeadSource(scope, option) {
    var ask = scope.querySelector('[data-variants-ask]');

    if (!ask) {
      return;
    }

    if (!ask.hasAttribute('data-lead-base')) {
      ask.setAttribute('data-lead-base', ask.getAttribute('data-lead-open') || '');
    }

    /* Подпись без цены и без скрытого «в корзине» */
    var text = option.querySelector('.variants__text');
    var label = (option.getAttribute('data-label') || (text ? text.textContent : '')).replace(/\s+/g, ' ').trim();
    var sku = option.getAttribute('data-sku');

    ask.setAttribute('data-lead-open', ask.getAttribute('data-lead-base') + ' — ' + label + (sku ? ' (арт. ' + sku + ')' : ''));
  }

  function select(scope, option) {
    Array.prototype.forEach.call(scope.querySelectorAll('[data-variant-option]'), function (el) {
      el.classList.toggle('is-checked', el === option);
    });

    var control = showControl(scope, option);
    var price = scope.querySelector('[data-variants-price]');

    if (price) {
      price.textContent = option.getAttribute('data-price-text');
      price.setAttribute('data-price', option.getAttribute('data-price'));
    }

    setSku(scope, option.getAttribute('data-sku') || '');
    setLeadSource(scope, option);
    hideError(scope);

    /* Страница товара пересчитает цену за количество этого варианта */
    var qty = control && control.querySelector('[data-cart-qty]');
    document.dispatchEvent(new CustomEvent('promix:variant', { detail: { qty: qty ? parseInt(qty.value, 10) || 0 : 0 } }));
  }

  function hideError(scope) {
    var box = scope.querySelector('[data-variants]');
    var error = scope.querySelector('[data-variants-error]');

    if (box) {
      box.classList.remove('is-invalid');
    }

    if (error) {
      error.hidden = true;
    }
  }

  function showError(scope) {
    var box = scope.querySelector('[data-variants]');
    var error = scope.querySelector('[data-variants-error]');

    if (box) {
      box.classList.add('is-invalid');
    }

    if (error) {
      error.hidden = false;
    }

    var first = scope.querySelector('.select__btn, [data-variant-option] input');

    if (first) {
      first.focus({ preventScroll: true });
    }
  }

  document.addEventListener('change', function (event) {
    var input = event.target.closest('[data-variant-option] input, [data-variants-select]');

    if (!input) {
      return;
    }

    var scope = scopeOf(input);
    var option = input.matches('[data-variants-select]') ? checkedOption(scope) : input.checked && input.closest('[data-variant-option]');

    if (option) {
      select(scope, option);
    }
  });

  document.addEventListener('click', function (event) {
    var pick = event.target.closest('[data-variants-pick]');

    if (pick) {
      var scope = scopeOf(pick);
      var option = checkedOption(scope);

      event.preventDefault();

      /* Выбор есть, а видна общая кнопка — так бывает, только если
         разметка не успела обновиться; показываем кнопку варианта */
      if (option) {
        select(scope, option);
      } else {
        showError(scope);
      }
    }
  });

  /* cart.js подменил кнопку или счётчик варианта: переставляем метку
     «в корзине» и снова оставляем видимой только кнопку выбранного */
  document.addEventListener('promix:cart', function (event) {
    var control = event.detail && event.detail.control;
    var scope = control && scopeOf(control);

    if (!scope) {
      return;
    }

    var option = scope.querySelector('[data-variant-option][data-variation="' + control.getAttribute('data-cart-control') + '"]');

    if (option) {
      markInCart(option, !!control.querySelector('[data-cart-key]'));
    }

    var current = checkedOption(scope);
    showControl(scope, current);

    var shown = controlFor(scope, current);
    var qty = shown && shown.querySelector('[data-cart-qty]');
    document.dispatchEvent(new CustomEvent('promix:variant', { detail: { qty: qty ? parseInt(qty.value, 10) || 0 : 0 } }));
  });

  /* Браузер может вернуть отмеченную радиокнопку после «Назад» —
     тогда показываем её цену и кнопку. На странице товара вариант
     может прийти в адресе: ссылка из корзины ведёт на выбранную длину. */
  function init() {
    var params = new URLSearchParams(window.location.search);
    var wanted = {};

    params.forEach(function (value, key) {
      if (key.indexOf('attribute_') === 0) {
        wanted[key] = value;
      }
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-variants-scope]'), function (scope) {
      var option = checkedOption(scope);

      if (!option && Object.keys(wanted).length && scope.querySelector('.variants--single')) {
        option = Array.prototype.find.call(scope.querySelectorAll('[data-variant-option]'), function (el) {
          var attrs = JSON.parse(el.getAttribute('data-attrs') || '{}');

          return Object.keys(wanted).every(function (key) {
            return attrs[key] === wanted[key];
          });
        }) || null;

        if (option && option.tagName === 'OPTION') {
          option.selected = true;
        } else if (option) {
          option.querySelector('input').checked = true;
        }
      }

      /* Список оповещаем событием: на него же обновит надпись select.js */
      if (option && option.tagName === 'OPTION') {
        option.parentNode.dispatchEvent(new Event('change', { bubbles: true }));
      } else if (option) {
        select(scope, option);
      }
    });
  }

  init();
})();
