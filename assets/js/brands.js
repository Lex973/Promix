/* PROMIX — две бегущие строки с брендами.

   Одна строка массива — один бренд. color используется только при наведении.
   Список и цвета сейчас тестовые, заменяются на реальные.

   В WordPress массив уходит в ACF-репитер или в /wp-json/promix/v1/brands —
   формат объекта тот же, код ниже не меняется.
*/

var PROMIX_BRANDS = [
  { name: 'Tikkurila', color: '#E4002B', url: '#catalog' },
  { name: 'Caparol',   color: '#0090D7', url: '#catalog' },
  { name: 'STORCH',    color: '#E2001A', url: '#catalog' },
  { name: 'Anza',      color: '#FF7A00', url: '#catalog' },
  { name: 'Mirka',     color: '#F2B705', url: '#catalog' },
  { name: 'Graco',     color: '#0057A8', url: '#catalog' },
  { name: 'Eskaro',    color: '#00539B', url: '#catalog' },
  { name: 'Dulux',     color: '#00843D', url: '#catalog' },
  { name: 'Kiilto',    color: '#F07F00', url: '#catalog' },
  { name: 'Ceresit',   color: '#C8102E', url: '#catalog' },
  { name: 'Knauf',     color: '#006EB7', url: '#catalog' },
  { name: 'Osmo',      color: '#6E8B3D', url: '#catalog' }
];

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

  /* Скорость общая для обеих строк: длительность анимации считается
     от реальной ширины ленты, поэтому не зависит от числа плиток */
  var SPEED = 42;
  var MAX_COPIES = 8;

  function tile(brand, clone) {
    var el = document.createElement('a');
    el.className = 'brand';
    el.href = brand.url || '#catalog';
    el.style.setProperty('--brand-color', brand.color);
    el.textContent = brand.name;

    if (clone) {
      el.setAttribute('aria-hidden', 'true');
      el.tabIndex = -1;
    }

    return el;
  }

  /* Первая строка — начало списка, вторая — остаток */
  var middle = Math.ceil(PROMIX_BRANDS.length / 2);
  var lists = [PROMIX_BRANDS.slice(0, middle), PROMIX_BRANDS.slice(middle)];

  function fill(track, list) {
    track.textContent = '';

    if (!list.length) {
      return;
    }

    /* Список повторяется, пока лента не перекроет экран: короткая лента
       оставила бы пустоту на широком мониторе */
    var copies = 0;
    do {
      list.forEach(function (brand) {
        track.appendChild(tile(brand, copies > 0));
      });
      copies += 1;
    } while (track.scrollWidth < window.innerWidth && copies < MAX_COPIES);

    /* Дубль всей ленты: дойдя до половины, анимация уходит на новый круг без стыка */
    var original = Array.prototype.slice.call(track.children);
    original.forEach(function (node) {
      var copy = node.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      copy.tabIndex = -1;
      track.appendChild(copy);
    });

    /* Ширина меряется после дубля: половина дорожки — ровно один проход */
    var half = track.getBoundingClientRect().width / 2;
    track.style.setProperty('--brands-duration', Math.max(20, Math.round(half / SPEED)) + 's');
  }

  function build() {
    Array.prototype.forEach.call(tracks, function (track, index) {
      fill(track, lists[index] || PROMIX_BRANDS);
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
