/* PROMIX — уведомление про cookie */

(function () {
  'use strict';

  var KEY = 'promix-cookie-notice';
  var notice = document.querySelector('[data-cookie]');

  if (!notice) {
    return;
  }

  var accepted = false;

  /* В приватном окне обращение к хранилищу падает. Тогда считаем,
     что посетитель уже видел плашку: показывать её каждый раз хуже,
     чем не показать вовсе */
  try {
    accepted = !!localStorage.getItem(KEY);
  } catch (e) {
    accepted = true;
  }

  if (accepted) {
    return;
  }

  notice.hidden = false;

  /* Кадр между показом и классом — иначе появление не анимируется */
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      notice.classList.add('is-visible');
    });
  });

  var button = notice.querySelector('[data-cookie-accept]');

  if (!button) {
    return;
  }

  button.addEventListener('click', function () {
    try {
      localStorage.setItem(KEY, '1');
    } catch (e) {
      /* Не записалось — плашка всё равно уходит до конца сессии */
    }

    notice.classList.remove('is-visible');

    var hide = function () {
      notice.hidden = true;
    };

    notice.addEventListener('transitionend', hide, { once: true });

    /* Страховка: при prefers-reduced-motion переход почти мгновенный
       и события можно не дождаться */
    setTimeout(hide, 500);
  });
})();
