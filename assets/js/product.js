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
    /* Копейки считаем целыми, чтобы 1074.84 × 3 не дало 3224.5199…;
       показываем их, только если они есть — как promix_price() */
    var kopecks = Math.round(base * 100) * Math.max(1, n);
    var digits = kopecks % 100 ? 2 : 0;
    price.textContent = (kopecks / 100).toLocaleString('ru-RU', { minimumFractionDigits: digits, maximumFractionDigits: digits }) + ' ₽';
  }

  document.addEventListener('promix:cart', function (event) {
    showPrice(event.detail.qty);
  });

  var inCart = document.querySelector('.cart-control--single [data-cart-qty]');

  if (inCart) {
    showPrice(parseInt(inCart.value, 10) || 1);
  }
})();
