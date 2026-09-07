/* PROMIX — две бегущие строки с брендами.

   Плитки приходят из разметки: в теме их печатает PHP из полей админки,
   в статике они лежат в index.html. Скрипт только повторяет ленту до
   ширины экрана, дублирует её для бесшовного цикла и задаёт скорость.
*/

(function () {
  'use strict';

  var section = document.querySelector('[data-brands]');
  if (!section) {
    return;
  }

  var tracks = section.querySelectorAll('[data-brands-track]');
  if (!tracks.length) {
    return;
  }

  /* Скорость общая для обеих строк: длительность считается от реальной
     ширины ленты, поэтому не зависит от числа плиток */
  var SPEED = 42;
  var MAX_COPIES = 8;

  function clone(node) {
    var copy = node.cloneNode(true);
    copy.setAttribute('aria-hidden', 'true');

    if (copy.tagName === 'A') {
      copy.tabIndex = -1;
    }

    return copy;
  }

  function fill(track) {
    var original = Array.prototype.slice.call(track.querySelectorAll('.brand'));

    if (!original.length) {
      return;
    }

    /* Список повторяется, пока лента не перекроет экран: короткая лента
       оставила бы пустоту на широком мониторе */
    var copies = 1;
    while (track.scrollWidth < window.innerWidth && copies < MAX_COPIES) {
      original.forEach(function (node) {
        track.appendChild(clone(node));
      });
      copies += 1;
    }

    /* Дубль всей ленты: дойдя до половины, анимация уходит на новый круг без стыка */
    Array.prototype.slice.call(track.children).forEach(function (node) {
      track.appendChild(clone(node));
    });

    /* Ширина меряется после дубля: половина дорожки — ровно один проход */
    var half = track.getBoundingClientRect().width / 2;
    track.style.setProperty('--brands-duration', Math.max(20, Math.round(half / SPEED)) + 's');
  }

  /* Разметка — источник правды: перед пересборкой лишние копии удаляются */
  var source = [];

  Array.prototype.forEach.call(tracks, function (track) {
    source.push(Array.prototype.slice.call(track.children).map(function (node) {
      return node.cloneNode(true);
    }));
  });

  function build() {
    Array.prototype.forEach.call(tracks, function (track, index) {
      track.textContent = '';

      source[index].forEach(function (node) {
        track.appendChild(node.cloneNode(true));
      });

      fill(track);
    });
  }

  build();

  /* На смену ширины экрана ленту пересобираем: меняется и размер плиток, и длина */
  var resizeTimer = null;
  var lastWidth = window.innerWidth;

  window.addEventListener('resize', function () {
    if (window.innerWidth === lastWidth) {
      return;
    }

    lastWidth = window.innerWidth;
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(build, 200);
  });
})();
