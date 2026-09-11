/* PROMIX — страница товара: счётчик количества.

   Кнопка «В корзину» пока отправляет обычную форму; на месте её
   перехватит скрипт корзины, когда она появится. */

(function () {
  'use strict';

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
  });

  input.addEventListener('blur', function () {
    set(value());
  });
})();
