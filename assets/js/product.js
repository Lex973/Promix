/* PROMIX — страница товара: счётчик количества до добавления в корзину
   и цена, которая считается за выбранное количество.
   Саму кнопку «В корзину» перехватывает cart.js. */

(function () {
  'use strict';

  var price = document.querySelector('[data-price]');
  var base = price ? parseFloat(price.getAttribute('data-price')) || 0 : 0;

  /* Цена за N штук: формат тот же, что на сервере — пробел между тысячами */
  function showPrice(n) {
    if (!price || !base) {
      return;
    }

    var total = Math.round(base * Math.max(1, n));
    price.textContent = total.toLocaleString('ru-RU').replace(/,/g, ' ') + ' ₽';
  }

  /* Товар уже в корзине: количество меняет cart.js, цена идёт за ним */
  document.addEventListener('promix:cart', function (event) {
    showPrice(event.detail.qty);
  });

  var inCart = document.querySelector('.cart-control--single [data-cart-qty]');

  if (inCart) {
    showPrice(parseInt(inCart.value, 10) || 1);
  }

  var box = document.querySelector('[data-qty]');

  if (!box) {
    return;
  }

  var input = box.querySelector('[data-qty-input]');
  var minus = box.querySelector('[data-qty-minus]');
  var plus = box.querySelector('[data-qty-plus]');

  function value() {
    var n = parseInt(input.value.replace(/\D/g, ''), 10);
    return n > 0 ? n : 1;
  }

  function set(n) {
    input.value = String(n);
    showPrice(n);
  }

  minus.addEventListener('click', function () {
    set(Math.max(1, value() - 1));
  });

  plus.addEventListener('click', function () {
    set(value() + 1);
  });

  /* Всё, кроме цифр, убирается сразу; пустое поле при уходе становится единицей */
  input.addEventListener('input', function () {
    var clean = input.value.replace(/\D/g, '');

    if (clean !== input.value) {
      input.value = clean;
    }

    showPrice(value());
  });

  input.addEventListener('blur', function () {
    set(value());
  });
})();
