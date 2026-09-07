/* PROMIX — карусель отзывов.

   Тексты выгружены с карточки компании в 2ГИС (firm/70000001060590384),
   приведены дословно вместе с ответами магазина. Оценка у всех 5:
   в карточке 2ГИС рейтинг 4,9 при 53 оценках.

   В WordPress массив уходит в ACF-репитер или в /wp-json/promix/v1/reviews —
   формат объекта тот же, код ниже не меняется.
*/

var PROMIX_REVIEWS = [
  {
    name: 'Ленар Гамора',
    date: 'май 2025',
    rating: 5,
    text: 'Занимаюсь малярными работами. Раньше работал по старинке леруашными материалами. Узнал об этой фирме два года назад, и до сих пор ни дня не пожалел, а наоборот. На первой же встрече всё доходчиво объяснили про технологию, про материалы. При возникновении каких-либо вопросов в рабочем моменте всегда можно позвонить и поинтересоваться. В офисе Pro Mix представлено много качественных инструментов топовых брендов, которые очень облегчают труд и экономят время и нервы. У ребят ещё проходят мастер-классы, я считаю это очень круто.',
    reply: 'Здравствуйте, Ленар! Огромное спасибо за ваш отзыв и оценку 🔥 Были рады знакомству ❤'
  },
  {
    name: 'Эдем Османов',
    date: 'июнь 2025',
    rating: 5,
    text: 'Уже 4 год работаем материалами от Промикс, и менять пирог своих стен не собираемся! А после открытия малярного центра стало намного удобнее добираться, весь ассортимент перед глазами, и самое главное всё в наличии!!! Также персонал всегда отзывчивый, и в случае возникновения вопросов консультируют!!! Также большим преимуществом является то, что в малярном центре появился учебный класс, где желающих знакомят с продукцией компании!!',
    reply: 'Эдем! Благодарим за отзыв, нам очень приятно 🤝'
  },
  {
    name: 'Талгат Файзуллин',
    date: 'май 2025',
    rating: 5,
    text: 'Очень профессиональный продавец. Опытные сотрудники помогли подобрать необходимое оборудование. Теперь закупаюсь только тут. Рекомендую всем, не пожалеете. Оборудование показало себя в работе на отлично.',
    reply: 'Здравствуйте, Талгат. Огромное спасибо, что уделили время и написали отзыв 🔥 Будем ждать вас снова'
  },
  {
    name: 'Мария Кувшинова',
    date: 'июнь 2025',
    rating: 5,
    text: 'В данном малярном центре есть всё необходимое. И краски, и инструменты. Удобное расположение и парковка!! Девушки-специалисты Лиля и Лейсан всё объяснят и подскажут, на каком выборе остановиться. Доставка быстрая!! Цены не кусаются!!! Рекомендую!!!',
    reply: 'Здравствуйте, Мария, спасибо большое за добрые слова 🤗 Ждём снова ❤'
  },
  {
    name: 'Расул Бек',
    date: 'июль 2025',
    rating: 5,
    text: 'Я выбираю Малярный центр ProMIX! ProMIX — это огромная палитра качественных, проверенных на деле материалов! Большой ассортимент добротного и надёжного инструмента. И конечно же душа центра — это его персонал. Профессиональные, отзывчивые и всегда готовые предоставить квалифицированную консультацию по всем вопросам, касающимся отделки и не только.',
    reply: 'Здравствуйте, Расул! Спасибо вам за приятный отклик 😌 Очень приятно!'
  },
  {
    name: 'Светлана Петровна',
    date: 'сентябрь 2025',
    rating: 5,
    text: 'Хочется поблагодарить весь коллектив, а особенно Ляйсан, за их чуткость, внимательность и конечно же профессионализм. Ребята рассказали, подсказали, помогли. Всё очень грамотно. Отличный подход, отличное качество, спасибо Вам!',
    reply: 'Светлана, спасибо за ваш отзыв и оценку 🔥 Очень приятно, рады сотрудничать ❤'
  },
  {
    name: 'Тагир Хайруллин',
    date: 'май 2025',
    rating: 5,
    text: 'Отличный магазин. Отдельное спасибо менеджеру Ляйсан за качественную консультацию и подбор всех материалов.',
    reply: 'Тагир, добрый день, благодарим за такой приятный отзыв 😉 Всегда рады помочь 💛'
  },
  {
    name: 'Ильяс Ризванов',
    date: 'июнь 2025',
    rating: 5,
    text: 'Один из лучших малярных магазинов в Казани. Есть всё необходимое для работы: инструменты, материалы. Рекомендую.',
    reply: 'Спасибо вам, что выбираете нас!!! Рады видеть вас снова.'
  },
  {
    name: 'Марк Портнов',
    date: 'май 2025',
    rating: 5,
    text: 'Это было большое открытие, когда нашёл этот магазин. У них профессиональный выбор. Это тот момент, когда после посещения промикс Леруа обходишь стороной. Классные ребята, процветания вам 🫂',
    reply: 'Марк, добрый день. Благодарим вас за высокую оценку 🤗 Обращайтесь 💛'
  }
];

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

  var STAR = 'M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z';

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) {
      node.className = className;
    }
    if (text !== undefined) {
      node.textContent = text;
    }
    return node;
  }

  function stars(rating) {
    var box = el('div', 'stars');
    box.setAttribute('aria-label', 'Оценка: ' + rating + ' из 5');

    for (var i = 0; i < rating; i += 1) {
      box.insertAdjacentHTML('beforeend',
        '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="' + STAR + '"></path></svg>');
    }

    return box;
  }

  /* «Ленар Гамора» → «ЛГ» */
  function initials(name) {
    return name.split(' ').slice(0, 2).map(function (word) {
      return word.charAt(0).toUpperCase();
    }).join('');
  }

  function card(review) {
    var node = el('article', 'review');

    var head = el('div', 'review__head');
    head.appendChild(el('span', 'review__avatar', initials(review.name)));

    var who = el('div', 'review__who');
    who.appendChild(el('p', 'review__name', review.name));
    who.appendChild(el('p', 'review__source', 'Отзыв в 2ГИС'));
    head.appendChild(who);
    head.appendChild(el('span', 'review__date', review.date));
    node.appendChild(head);

    node.appendChild(stars(review.rating));
    node.appendChild(el('p', 'review__text', '«' + review.text + '»'));

    if (review.reply) {
      var reply = el('div', 'review__reply');
      reply.appendChild(el('span', 'review__reply-logo', 'PM'));

      var body = el('div');
      body.appendChild(el('p', 'review__reply-title', 'Ответ PROMIX'));
      body.appendChild(el('p', 'review__reply-text', review.reply));
      reply.appendChild(body);

      node.appendChild(reply);
    }

    return node;
  }

  PROMIX_REVIEWS.forEach(function (review) {
    track.appendChild(card(review));
  });

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
    var max = track.scrollWidth - viewport.clientWidth - 1;

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
