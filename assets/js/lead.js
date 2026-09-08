/* PROMIX — модальное окно заявки.

   Одно окно на все кнопки страницы: кнопка сообщает, откуда пришёл клиент,
   и подставляет свой заголовок. Отправка идёт без перезагрузки.
*/

(function () {
  'use strict';

  var modal = document.querySelector('[data-lead-modal]');
  if (!modal || typeof window.PROMIX_LEAD === 'undefined') {
    return;
  }

  var form = modal.querySelector('[data-lead-form]');
  var message = modal.querySelector('[data-lead-message]');
  var sourceInput = modal.querySelector('[data-lead-source]');
  var openedInput = modal.querySelector('[data-lead-opened]');
  var titleEl = modal.querySelector('[data-lead-title]');
  var phoneInput = modal.querySelector('input[name="phone"]');
  var submit = form ? form.querySelector('[type="submit"]') : null;

  var defaultTitle = titleEl ? titleEl.textContent : '';
  var lastFocused = null;

  /* ===== Маска телефона ===== */

  var MASK = '+7 (___) ___-__-__';

  function digitsOf(value) {
    var digits = value.replace(/\D/g, '');

    // Номер, начатый с 8 или 7, приводим к одному виду: код страны уже в маске.
    if (digits.charAt(0) === '8' || digits.charAt(0) === '7') {
      digits = digits.slice(1);
    }

    return digits.slice(0, 10);
  }

  function format(digits) {
    if (!digits) {
      return '';
    }

    var out = '';
    var index = 0;

    for (var i = 0; i < MASK.length && index < digits.length; i += 1) {
      out += MASK.charAt(i) === '_' ? digits.charAt(index++) : MASK.charAt(i);
    }

    return out;
  }

  function applyMask() {
    var digits = digitsOf(phoneInput.value);
    /* Курсор всегда в конце: набор идёт слева направо, а правка середины
       ломала бы позицию после переформатирования */
    phoneInput.value = format(digits);
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', applyMask);

    phoneInput.addEventListener('focus', function () {
      if (!phoneInput.value) {
        phoneInput.value = '+7 (';
      }
    });

    phoneInput.addEventListener('blur', function () {
      if (digitsOf(phoneInput.value).length === 0) {
        phoneInput.value = '';
      }
    });

    // Вставка из буфера приходит уже готовой строкой — приводим к маске.
    phoneInput.addEventListener('paste', function () {
      window.setTimeout(applyMask, 0);
    });
  }

  /* ===== Открытие и закрытие ===== */

  function open(trigger) {
    lastFocused = trigger || document.activeElement;

    var source = trigger ? (trigger.getAttribute('data-lead-open') || trigger.textContent.trim()) : '';
    var title = trigger ? trigger.getAttribute('data-lead-title') : '';

    sourceInput.value = source;
    titleEl.textContent = title || defaultTitle;
    openedInput.value = Math.floor(Date.now() / 1000);

    message.textContent = '';
    message.className = 'lead__note';

    // Ширина полосы прокрутки уходит в CSS: страница не дёргается вбок
    document.documentElement.style.setProperty(
      '--scrollbar-width',
      (window.innerWidth - document.documentElement.clientWidth) + 'px'
    );

    modal.hidden = false;
    document.body.classList.add('is-modal-open');

    if (window.promixLenis) {
      window.promixLenis.stop();
    }

    var first = form.querySelector('input[name="name"]');
    if (first) {
      // preventScroll: иначе браузер подтягивает страницу к полю под окном
      first.focus({ preventScroll: true });
    }
  }

  function close() {
    modal.hidden = true;
    document.body.classList.remove('is-modal-open');

    if (window.promixLenis) {
      window.promixLenis.start();
    }

    if (lastFocused) {
      lastFocused.focus({ preventScroll: true });
    }
  }

  /* Клик перехватывается на погружении: у кнопок href вида #contacts,
     и плавный скролл успел бы увезти страницу до нашего обработчика */
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-lead-open]');

    if (trigger) {
      event.preventDefault();
      event.stopPropagation();
      open(trigger);
      return;
    }

    if (event.target.closest('[data-lead-close]')) {
      event.preventDefault();
      event.stopPropagation();
      close();
    }
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      close();
    }
  });

  /* ===== Отправка ===== */

  if (!form) {
    return;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var data = new FormData(form);
    data.append('action', 'promix_lead');

    submit.disabled = true;
    message.className = 'lead__note';
    message.textContent = window.PROMIX_LEAD.sending;

    window.fetch(window.PROMIX_LEAD.url, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        var ok = result && result.success;
        var text = result && result.data && result.data.message;

        message.className = 'lead__note ' + (ok ? 'is-ok' : 'is-error');
        message.textContent = text || (ok ? window.PROMIX_LEAD.done : window.PROMIX_LEAD.error);

        if (ok) {
          form.reset();
          /* Окно закрывается не сразу: человек должен успеть прочитать ответ */
          window.setTimeout(close, 2500);
        }
      })
      .catch(function () {
        message.className = 'lead__note is-error';
        message.textContent = window.PROMIX_LEAD.error;
      })
      .then(function () {
        submit.disabled = false;
      });
  });
})();
