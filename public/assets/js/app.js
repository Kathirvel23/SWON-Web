/* SWON Web — small progressive enhancements. No framework, no build step. */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Mobile navigation -----------------------------------------------------
  var toggle = document.querySelector('[data-nav-toggle]');
  var links = document.querySelector('[data-nav-links]');
  if (toggle && links) {
    var setOpen = function (open) {
      links.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', function () {
      setOpen(!links.classList.contains('open'));
    });

    // Close when a destination is chosen.
    links.addEventListener('click', function (event) {
      if (event.target.closest('a')) { setOpen(false); }
    });

    // Close on Escape, returning focus to the trigger.
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && links.classList.contains('open')) {
        setOpen(false);
        toggle.focus();
      }
    });

    // Close when clicking outside the menu.
    document.addEventListener('click', function (event) {
      if (!links.classList.contains('open')) { return; }
      if (!links.contains(event.target) && !toggle.contains(event.target)) { setOpen(false); }
    });
  }

  // Auto-dismiss flash messages -----------------------------------------
  document.querySelectorAll('[data-flash]').forEach(function (el) {
    window.setTimeout(function () {
      if (!reduceMotion) {
        el.style.transition = 'opacity .35s ease, transform .35s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-4px)';
      }
      window.setTimeout(function () { el.remove(); }, reduceMotion ? 0 : 380);
    }, 6000);
  });

  // One-time-code input: digits only, paste friendly ---------------------
  var otp = document.querySelector('[data-otp]');
  if (otp) {
    otp.addEventListener('input', function () {
      otp.value = otp.value.replace(/\D+/g, '').slice(0, 6);
    });
    otp.focus();
  }

  // Test-mode OTP: fill the generated code into the field.
  document.querySelectorAll('[data-fill-otp]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById('code');
      if (input) {
        input.value = btn.getAttribute('data-fill-otp') || '';
        input.focus();
      }
    });
  });

  // Resend countdown -----------------------------------------------------
  var resend = document.querySelector('[data-resend]');
  if (resend) {
    var seconds = parseInt(resend.getAttribute('data-cooldown') || '0', 10);
    var label = resend.querySelector('[data-resend-label]');
    var timer = null;

    var tick = function () {
      if (seconds <= 0) {
        resend.disabled = false;
        resend.classList.remove('is-disabled');
        if (label) { label.textContent = 'Resend code'; }
        if (timer) { window.clearInterval(timer); }
        return;
      }
      resend.disabled = true;
      resend.classList.add('is-disabled');
      if (label) { label.textContent = 'Resend in ' + seconds + 's'; }
      seconds -= 1;
    };

    tick();
    timer = window.setInterval(tick, 1000);
  }

  // Demo account quick-fill ---------------------------------------------
  document.querySelectorAll('[data-fill-phone]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.querySelector('input[name="phone"]');
      if (input) {
        input.value = btn.getAttribute('data-fill-phone');
        input.focus();
      }
    });
  });

  // Confirmation for destructive actions --------------------------------
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  // Prevent double submits ----------------------------------------------
  document.querySelectorAll('form[data-single-submit]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        window.setTimeout(function () {
          btn.disabled = true;
          btn.classList.add('is-disabled', 'is-busy');
          btn.setAttribute('aria-busy', 'true');
          var busy = btn.getAttribute('data-busy');
          if (busy) { btn.textContent = busy; }
        }, 10);
      }
    });
  });
})();
