/* PROMIX — карусель отзывов.

   Карточки приходят из разметки: в теме их печатает PHP из полей админки,
   в статике они лежат в index.html. Скрипт отвечает только за листание —
   стрелками, свайпом и перетаскиванием мышью.
*/

(function () {
  'use strict';

  var section = document.querySelector('[data-reviews]');
  if (!section) {
    return;
  }

  var viewport = section.querySelector('[data-reviews-viewport]');
  var track = section.querySelector('[data-reviews-track]');
  var prevBtn = section.querySelector('[data-reviews-prev]');
  var nextBtn = section.querySelector('[data-reviews-next]');

  if (!viewport || !track) {
    return;
  }

  function stepWidth() {
    var first = track.querySelector('.review');
    if (!first) {
      return viewport.clientWidth;
    }

    var gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
    return first.getBoundingClientRect().width + gap;
  }

  /* Кнопка гаснет, когда лента упёрлась в край */
  function syncButtons() {
    var max = viewport.scrollWidth - viewport.clientWidth - 1;

    if (prevBtn) {
      prevBtn.disabled = viewport.scrollLeft <= 0;
    }
    if (nextBtn) {
      nextBtn.disabled = viewport.scrollLeft >= max;
    }
  }

  function step(direction) {
    viewport.scrollBy({ left: direction * stepWidth(), behavior: 'smooth' });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () { step(-1); });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () { step(1); });
  }

  viewport.addEventListener('scroll', syncButtons, { passive: true });
  window.addEventListener('resize', syncButtons);
  syncButtons();

  /* Перетаскивание мышью; на тач-устройствах работает нативный свайп */
  var dragging = false;
  var startX = 0;
  var startScroll = 0;

  viewport.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'touch') {
      return;
    }

    dragging = true;
    startX = event.clientX;
    startScroll = viewport.scrollLeft;
    viewport.setPointerCapture(event.pointerId);
    viewport.classList.add('is-dragging');
  });

  viewport.addEventListener('pointermove', function (event) {
    if (dragging) {
      viewport.scrollLeft = startScroll - (event.clientX - startX);
    }
  });

  function endDrag(event) {
    if (!dragging) {
      return;
    }

    dragging = false;
    viewport.classList.remove('is-dragging');

    if (event && event.pointerId !== undefined && viewport.hasPointerCapture(event.pointerId)) {
      viewport.releasePointerCapture(event.pointerId);
    }
  }

  viewport.addEventListener('pointerup', endDrag);
  viewport.addEventListener('pointercancel', endDrag);
})();
