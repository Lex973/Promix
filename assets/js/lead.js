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
  var submit = form ? form.querySelector('[type="submit"]') : null;

  var defaultTitle = titleEl ? titleEl.textContent : '';
  var lastFocused = null;

  function open(trigger) {
    lastFocused = trigger || document.activeElement;

    var source = trigger ? (trigger.getAttribute('data-lead-open') || trigger.textContent.trim()) : '';
    var title = trigger ? trigger.getAttribute('data-lead-title') : '';

    sourceInput.value = source;
    titleEl.textContent = title || defaultTitle;
    openedInput.value = Math.floor(Date.now() / 1000);

    message.textContent = '';
    message.className = 'lead__note';

    modal.hidden = false;
    document.body.classList.add('is-modal-open');

    var first = form.querySelector('input[name="name"]');
    if (first) {
      first.focus();
    }
  }

  function close() {
    modal.hidden = true;
    document.body.classList.remove('is-modal-open');

    if (lastFocused) {
      lastFocused.focus();
    }
  }

  /* Кнопки помечены data-lead-open, поэтому новые не требуют правок скрипта */
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-lead-open]');

    if (trigger) {
      event.preventDefault();
      open(trigger);
      return;
    }

    if (event.target.closest('[data-lead-close]')) {
      event.preventDefault();
      close();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      close();
    }
  });

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
