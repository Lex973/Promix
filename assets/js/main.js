/* PROMIX — шапка, мобильное меню, плавный скролл */

(function () {
  'use strict';

  var header = document.querySelector('[data-header]');

  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* Плавный скролл только для мыши и трекпада: на тач-устройствах
     своя системная инерция, а при prefers-reduced-motion он укачивает */
  var lenis = null;
  var isTouch = window.matchMedia('(pointer: coarse)').matches;
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (window.Lenis && !isTouch && !reducedMotion) {
    lenis = new window.Lenis({
      duration: 1.05,
      anchors: { offset: -112 }
    });

    var raf = function (time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    };

    requestAnimationFrame(raf);

    /* Плавный скролл нужен и другим скриптам: модалка останавливает его,
       пока открыта, иначе страница уезжает под окном */
    window.promixLenis = lenis;
  }

  /* ===== Ловушка фокуса ===== */

  var FOCUSABLE = 'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

  /* Пока открыто окно или меню, остальная страница выключена через inert:
     мышь и скринридер до неё не достают, Tab ходит по кругу внутри.
     Возвращает функцию, которая включает страницу обратно.

     keep — элемент, который нужно оставить живым (подложка меню: по клику
     она закрывает панель, а inert съел бы этот клик). */
  window.promixTrap = function (container, keep) {
    var disabled = [];

    Array.prototype.forEach.call(document.body.children, function (el) {
      if (el === container || el === keep || el.tagName === 'SCRIPT' || el.hasAttribute('inert')) {
        return;
      }

      el.setAttribute('inert', '');
      disabled.push(el);
    });

    /* Tab по кругу — для браузеров без inert */
    function onKeydown(event) {
      if (event.key !== 'Tab') {
        return;
      }

      var items = Array.prototype.slice.call(container.querySelectorAll(FOCUSABLE)).filter(function (el) {
        return el.tabIndex >= 0 && el.getClientRects().length > 0;
      });

      if (!items.length) {
        return;
      }

      var first = items[0];
      var last = items[items.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    document.addEventListener('keydown', onKeydown, true);

    return function () {
      disabled.forEach(function (el) {
        el.removeAttribute('inert');
      });

      document.removeEventListener('keydown', onKeydown, true);
    };
  };

  /* ===== Мобильное меню ===== */

  var burger = document.querySelector('[data-menu-open]');
  var menu = document.querySelector('[data-menu]');
  var overlay = document.querySelector('[data-menu-overlay]');
  var closeBtn = document.querySelector('[data-menu-close]');

  if (!burger || !menu || !overlay) {
    return;
  }

  var lastFocused = null;
  var releaseTrap = null;

  function openMenu() {
    lastFocused = document.activeElement;

    menu.classList.add('is-open');
    overlay.classList.add('is-open');
    document.body.classList.add('is-menu-open');
    burger.setAttribute('aria-expanded', 'true');
    menu.removeAttribute('aria-hidden');

    if (lenis) {
      lenis.stop();
    }

    releaseTrap = window.promixTrap(menu, overlay);

    var first = menu.querySelector(FOCUSABLE);
    if (first) {
      first.focus();
    }
  }

  function closeMenu() {
    menu.classList.remove('is-open');
    overlay.classList.remove('is-open');
    document.body.classList.remove('is-menu-open');
    burger.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');

    if (releaseTrap) {
      releaseTrap();
      releaseTrap = null;
    }

    if (lenis) {
      lenis.start();
    }

    if (lastFocused) {
      lastFocused.focus();
    }
  }

  function isOpen() {
    return menu.classList.contains('is-open');
  }

  burger.addEventListener('click', function () {
    if (isOpen()) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  overlay.addEventListener('click', closeMenu);

  if (closeBtn) {
    closeBtn.addEventListener('click', closeMenu);
  }

  menu.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', function (event) {
    if (!isOpen()) {
      return;
    }

    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 980 && isOpen()) {
      closeMenu();
    }
  });
})();
