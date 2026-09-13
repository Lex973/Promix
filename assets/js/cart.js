/* PROMIX — корзина.

   Три вещи: «В корзину» без перезагрузки откуда угодно (каталог, товар),
   счётчик на иконке в шапке и правка количества на странице корзины.
   Добавление идёт через штатный wc-ajax Woo, правка — через свой
   обработчик, который отдаёт перерисованный список. Без скрипта всё
   это работает обычными формами.
*/

(function () {
  'use strict';

  if (typeof window.PROMIX_CART === 'undefined' || !window.fetch) {
    return;
  }

  var cfg = window.PROMIX_CART;

  function endpoint(name) {
    return cfg.endpoint.replace('%%endpoint%%', name);
  }

  function post(name, data) {
    var body = new URLSearchParams();

    Object.keys(data).forEach(function (key) {
      body.append(key, data[key]);
    });

    return fetch(endpoint(name), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (response) {
      if (!response.ok) {
        throw new Error(response.status);
      }
      return response.json();
    });
  }

  /* ===== Счётчик в шапке ===== */

  function setCount(count, label) {
    Array.prototype.forEach.call(document.querySelectorAll('[data-cart-link]'), function (link) {
      link.setAttribute('data-count', String(count));

      var badge = link.querySelector('.icon-btn__count');

      if (badge) {
        badge.textContent = String(count);
      }

      if (label) {
        link.setAttribute('aria-label', label);
      }
    });
  }

  /* ===== Плашка «Товар в корзине» ===== */

  var toast = null;
  var toastTimer = null;

  function showToast(text, isError) {
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'toast';
      toast.setAttribute('role', 'status');
      document.body.appendChild(toast);
    }

    toast.innerHTML = '';
    toast.classList.toggle('toast--error', !!isError);

    var span = document.createElement('span');
    span.textContent = text;
    toast.appendChild(span);

    if (!isError) {
      var link = document.createElement('a');
      link.className = 'toast__link';
      link.href = cfg.cartUrl;
      link.textContent = cfg.open;
      toast.appendChild(link);
    }

    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3200);
  }

  /* ===== «В корзину» → счётчик =====
     Товар добавляется один раз: кнопка сменяется счётчиком «− N +»,
     дальше меняется только количество. Разметку счётчика и кнопки
     отдаёт сервер — скрипт лишь подменяет блок [data-cart-control]. */

  function controlOf(el) {
    return el.closest('[data-cart-control]');
  }

  function swapControl(control, html) {
    if (!control || !html) {
      return control;
    }

    var tmp = document.createElement('div');
    tmp.innerHTML = html;

    var next = tmp.firstElementChild;

    if (!next) {
      return control;
    }

    control.replaceWith(next);

    /* Страница товара пересчитывает цену по количеству в корзине */
    var input = next.querySelector('[data-cart-qty]');
    document.dispatchEvent(new CustomEvent('promix:cart', { detail: { qty: input ? parseInt(input.value, 10) || 0 : 0 } }));

    return next;
  }

  function addToCart(btn) {
    var control = controlOf(btn);
    var id = btn.getAttribute('data-add');
    var variant = control ? control.getAttribute('data-variant') : 'card';

    /* Повторное нажатие (в том числе с клавиатуры), пока идёт запрос, добавило бы товар дважды */
    if (btn.classList.contains('is-busy')) {
      return;
    }

    btn.classList.add('is-busy');

    post('add_to_cart', { product_id: id, quantity: 1, promix_variant: variant }).then(function (data) {
      if (data.error) {
        throw new Error('add');
      }

      var f = data.fragments || {};
      setCount(f.promix_count || 0, f.promix_label || '');
      swapControl(control, f.promix_control);
      showToast(cfg.toast);
    }).catch(function () {
      btn.classList.remove('is-busy');
      showToast(cfg.error, true);
    });
  }

  /* Изменение количества из карточки или со страницы товара.

     На один товар в полёте только один запрос: пока сервер отвечает,
     следующие нажатия меняют число на экране, а уходит одно итоговое
     значение. Без очереди два быстрых «+» могли ответить в обратном
     порядке — на сервере и на экране осталось бы старое число. */
  var stepTimers = {};
  var stepQueue = {};

  function stepCart(control, qty) {
    if (!control.querySelector('[data-cart-key]')) {
      return;
    }

    var id = control.getAttribute('data-cart-control');
    var state = stepQueue[id] || (stepQueue[id] = { control: control, busy: false, next: null });
    var input = control.querySelector('[data-cart-qty]');

    state.control = control;

    if (input && qty > 0) {
      input.value = String(qty);
    }

    if (state.busy) {
      state.next = qty;
      return;
    }

    stepSend(state, qty);
  }

  function stepSend(state, qty) {
    var control = state.control;
    var box = control.querySelector('[data-cart-key]');

    state.busy = true;
    state.next = null;
    control.classList.add('is-busy');

    post('promix_cart', {
      nonce: cfg.nonce,
      key: box.getAttribute('data-cart-key'),
      qty: qty,
      do: qty > 0 ? 'update' : 'remove',
      product_id: box.getAttribute('data-product'),
      promix_variant: control.getAttribute('data-variant') || 'card'
    }).then(function (res) {
      if (!res.success) {
        throw new Error('cart');
      }

      /* Товар удалён — ключа в корзине больше нет, дальнейшие правки некуда слать */
      if (qty === 0) {
        state.next = null;
      }

      /* Пока ждали ответ, число поменяли ещё раз: экран не трогаем,
         ответ уже устарел — сейчас уйдёт итоговое значение */
      if (state.next !== null) {
        return;
      }

      setCount(res.data.count, res.data.label);
      state.control = swapControl(control, res.data.control);
    }).catch(function () {
      state.next = null;
      control.classList.remove('is-busy');
      showToast(cfg.error, true);
    }).then(function () {
      state.busy = false;

      if (state.next !== null) {
        stepSend(state, state.next);
      }
    });
  }

  document.addEventListener('click', function (event) {
    var add = event.target.closest('[data-add]');

    if (add) {
      event.preventDefault();
      addToCart(add);
      return;
    }

    var step = event.target.closest('[data-cart-step]');

    if (step) {
      var control = controlOf(step);
      var input = control.querySelector('[data-cart-qty]');
      var current = parseInt((input.value || '').replace(/\D/g, ''), 10) || 1;

      stepCart(control, Math.max(0, current + parseInt(step.getAttribute('data-cart-step'), 10)));
    }
  });

  document.addEventListener('input', function (event) {
    var input = event.target.closest('[data-cart-qty]');

    if (!input) {
      return;
    }

    var clean = input.value.replace(/\D/g, '');

    if (clean !== input.value) {
      input.value = clean;
    }

    var control = controlOf(input);
    var id = control.getAttribute('data-cart-control');

    clearTimeout(stepTimers[id]);
    stepTimers[id] = setTimeout(function () {
      stepCart(control, Math.max(0, parseInt(clean, 10) || 0));
    }, 600);
  });

  /* ===== Страница корзины ===== */

  var cartBox = document.querySelector('[data-cart]');

  if (cartBox) {
    cartBox.classList.add('is-js');

    var pendingTimer = null;

    /* Список перерисовывается целиком, и фокус с поля количества или
       кнопки «+» пропадал. Запоминаем, где он был, и возвращаем. */
    function focusOf() {
      var el = document.activeElement;
      var item = el && el.closest('[data-cart-item]');

      if (!item) {
        return null;
      }

      return { key: item.getAttribute('data-cart-item'), input: el.hasAttribute('data-qty-input'), plus: el.hasAttribute('data-qty-plus'), minus: el.hasAttribute('data-qty-minus') };
    }

    function restoreFocus(saved) {
      if (!saved) {
        return;
      }

      var item = cartBox.querySelector('[data-cart-item="' + saved.key + '"]');
      var target = item && item.querySelector(saved.input ? '[data-qty-input]' : saved.plus ? '[data-qty-plus]' : saved.minus ? '[data-qty-minus]' : null);

      if (!target) {
        return;
      }

      target.focus();

      if (saved.input) {
        target.setSelectionRange(target.value.length, target.value.length);
      }
    }

    /* Та же очередь, что у счётчиков в карточках: один запрос в полёте,
       остальные правки копятся по ключу позиции и уходят следом.
       Ответ на устаревший запрос список не перерисовывает. */
    var cartBusy = false;
    var cartNext = {};

    function update(key, qty, action) {
      if (cartBusy) {
        cartNext[key] = { qty: qty, action: action };
        return;
      }

      var saved = focusOf();

      cartBusy = true;
      cartBox.classList.add('is-loading');

      post('promix_cart', { nonce: cfg.nonce, key: key, qty: qty, do: action || 'update' }).then(function (res) {
        if (!res.success) {
          throw new Error('cart');
        }

        if (Object.keys(cartNext).length) {
          return;
        }

        cartBox.innerHTML = res.data.html;
        setCount(res.data.count, res.data.label);
        restoreFocus(saved);
      }).catch(function () {
        window.location.reload();
      }).then(function () {
        cartBusy = false;

        var keys = Object.keys(cartNext);

        if (keys.length) {
          var pending = cartNext[keys[0]];
          delete cartNext[keys[0]];
          update(keys[0], pending.qty, pending.action);
        } else {
          cartBox.classList.remove('is-loading');
        }
      });
    }

    function itemOf(el) {
      var item = el.closest('[data-cart-item]');
      return item ? item.getAttribute('data-cart-item') : null;
    }

    function qtyOf(item) {
      var input = item.querySelector('[data-qty-input]');
      var n = parseInt((input.value || '').replace(/\D/g, ''), 10);
      return Math.min(n > 0 ? n : 1, 9999);
    }

    cartBox.addEventListener('click', function (event) {
      var remove = event.target.closest('[data-cart-remove]');

      if (remove) {
        event.preventDefault();
        update(itemOf(remove), 0, 'remove');
        return;
      }

      var minus = event.target.closest('[data-qty-minus]');
      var plus = event.target.closest('[data-qty-plus]');

      if (minus || plus) {
        var item = (minus || plus).closest('[data-cart-item]');
        var qty = qtyOf(item) + (plus ? 1 : -1);

        if (qty < 1) {
          update(itemOf(item), 0, 'remove');
        } else {
          item.querySelector('[data-qty-input]').value = String(qty);
          update(itemOf(item), qty);
        }
      }
    });

    /* Ввод руками: ждём, пока допечатают, и только потом пересчитываем */
    cartBox.addEventListener('input', function (event) {
      var input = event.target.closest('[data-qty-input]');

      if (!input) {
        return;
      }

      var clean = input.value.replace(/\D/g, '');

      if (clean !== input.value) {
        input.value = clean;
      }

      clearTimeout(pendingTimer);
      pendingTimer = setTimeout(function () {
        var item = input.closest('[data-cart-item]');
        update(itemOf(item), qtyOf(item));
      }, 600);
    });

    /* Enter в поле количества — пересчитать сразу, а не отправлять форму */
    cartBox.addEventListener('submit', function (event) {
      event.preventDefault();
      clearTimeout(pendingTimer);

      var active = document.activeElement;
      var item = active && active.closest('[data-cart-item]');

      if (item) {
        update(itemOf(item), qtyOf(item));
      }
    });
  }

  /* ===== Оформление: адрес нужен только для доставки ===== */

  var checkout = document.querySelector('[data-checkout-form]');

  if (checkout) {
    var address = checkout.querySelector('[data-address]');
    var submitBtn = checkout.querySelector('[data-checkout-submit]');

    checkout.addEventListener('change', function (event) {
      if (event.target.name === 'delivery' && address) {
        address.hidden = event.target.value !== 'delivery';
      }
    });

    /* Второй клик по «Отправить заказ» создавал второй заказ */
    checkout.addEventListener('submit', function () {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('is-busy');
      }
    });
  }
})();
