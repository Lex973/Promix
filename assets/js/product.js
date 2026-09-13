/* PROMIX — страница товара: цена считается за количество в корзине.
   Само количество меняет cart.js (счётчик вместо кнопки «В корзину»),
   здесь только пересчёт. */

(function () {
  'use strict';

  var price = document.querySelector('[data-price]');
  var base = price ? parseFloat(price.getAttribute('data-price')) || 0 : 0;

  if (!price || !base) {
    return;
  }

  /* Цена за N штук: формат тот же, что на сервере — пробел между тысячами */
  function showPrice(n) {
    var total = Math.round(base * Math.max(1, n));
    price.textContent = total.toLocaleString('ru-RU').replace(/,/g, ' ') + ' ₽';
  }

  document.addEventListener('promix:cart', function (event) {
    showPrice(event.detail.qty);
  });

  var inCart = document.querySelector('.cart-control--single [data-cart-qty]');

  if (inCart) {
    showPrice(parseInt(inCart.value, 10) || 1);
  }
})();
