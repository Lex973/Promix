/* PROMIX — страница товара: цена считается за количество в корзине.
   Само количество меняет cart.js (счётчик вместо кнопки «В корзину»),
   здесь только пересчёт. У товара с вариантами цену и data-price
   меняет variants.js при выборе — пересчёт идёт от цены выбранного. */

(function () {
  'use strict';

  var price = document.querySelector('[data-price]');

  if (!price) {
    return;
  }

  /* «от» и единица — как у promix_product_price(): единица только у цены
     за одну штуку, у суммы за несколько её нет */
  var from = price.getAttribute('data-price-from') ? 'от ' : '';
  var unit = price.getAttribute('data-price-unit') || '';

  /* Цена за N штук: формат тот же, что на сервере — пробел между тысячами */
  function showPrice(n) {
    var base = parseFloat(price.getAttribute('data-price')) || 0;

    /* Без цены текст уже «Цена по запросу» — его не трогаем */
    if (!base) {
      return;
    }

    /* Копейки считаем целыми, чтобы 1074.84 × 3 не дало 3224.5199…;
       показываем их, только если они есть — как promix_price() */
    var kopecks = Math.round(base * 100) * Math.max(1, n);
    var digits = kopecks % 100 ? 2 : 0;
    price.textContent = from + (kopecks / 100).toLocaleString('ru-RU', { minimumFractionDigits: digits, maximumFractionDigits: digits }) + ' ₽' +
      (n <= 1 && unit ? '/' + unit : '');
  }

  /* Счётчик простого товара; у товара с вариантами пересчёт придёт
     следом из variants.js — уже за выбранный вариант */
  document.addEventListener('promix:cart', function (event) {
    if (!document.querySelector('.single__info[data-variants-scope]')) {
      showPrice(event.detail.qty);
    }
  });

  document.addEventListener('promix:variant', function (event) {
    showPrice(event.detail.qty);
  });

  var inCart = document.querySelector('.cart-control--single:not([hidden]) [data-cart-qty]');

  if (inCart) {
    showPrice(parseInt(inCart.value, 10) || 1);
  }
})();
