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

  /* ===== «В корзину» ===== */

  function addToCart(btn) {
    var id = btn.getAttribute('data-add');
    var form = btn.closest('form');
    var qtyInput = form ? form.querySelector('[data-qty-input]') : null;
    var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
    var label = btn.textContent;

    btn.classList.add('is-busy');
    btn.disabled = true;

    post('add_to_cart', { product_id: id, quantity: qty }).then(function (data) {
      if (data.error) {
        throw new Error('add');
      }

      var f = data.fragments || {};
      setCount(f.promix_count || 0, f.promix_label || '');

      btn.classList.add('is-added');
      btn.textContent = cfg.added;
      showToast(cfg.toast);

      setTimeout(function () {
        btn.classList.remove('is-added');
        btn.textContent = label;
      }, 1600);
    }).catch(function () {
      showToast(cfg.error, true);
    }).then(function () {
      btn.classList.remove('is-busy');
      btn.disabled = false;
    });
  }

  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-add]');

    if (!btn) {
      return;
    }

    event.preventDefault();
    addToCart(btn);
  });

  /* ===== Страница корзины ===== */

  var cartBox = document.querySelector('[data-cart]');

  if (cartBox) {
    cartBox.classList.add('is-js');

    var pendingTimer = null;

    function update(key, qty, action) {
      cartBox.classList.add('is-loading');

      post('promix_cart', { nonce: cfg.nonce, key: key, qty: qty, do: action || 'update' }).then(function (res) {
        if (!res.success) {
          throw new Error('cart');
        }

        cartBox.innerHTML = res.data.html;
        setCount(res.data.count, res.data.label);
      }).catch(function () {
        window.location.reload();
      }).then(function () {
        cartBox.classList.remove('is-loading');
      });
    }

    function itemOf(el) {
      var item = el.closest('[data-cart-item]');
      return item ? item.getAttribute('data-cart-item') : null;
    }

    function qtyOf(item) {
      var input = item.querySelector('[data-qty-input]');
      var n = parseInt((input.value || '').replace(/\D/g, ''), 10);
      return n > 0 ? n : 1;
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

    checkout.addEventListener('change', function (event) {
      if (event.target.name === 'delivery' && address) {
        address.hidden = event.target.value !== 'delivery';
      }
    });
  }
})();
