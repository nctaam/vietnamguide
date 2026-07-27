(function () {
  'use strict';

  document.documentElement.classList.add('vg-js');

  var header = document.querySelector('[data-vg-header]');
  var menuToggle = document.querySelector('[data-vg-menu-toggle]');
  var navigation = document.querySelector('[data-vg-navigation]');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function closeMenu() {
    if (menuToggle) {
      menuToggle.setAttribute('aria-expanded', 'false');
    }

    if (navigation) {
      navigation.classList.remove('is-open');
    }

    document.body.classList.remove('vg-menu-open');
  }

  if (menuToggle && navigation) {
    menuToggle.addEventListener('click', function () {
      var isOpen = menuToggle.getAttribute('aria-expanded') === 'true';

      menuToggle.setAttribute('aria-expanded', String(!isOpen));
      navigation.classList.toggle('is-open', !isOpen);
      document.body.classList.toggle('vg-menu-open', !isOpen);
    });

    navigation.querySelectorAll('a').forEach(function (anchor) {
      anchor.addEventListener('click', closeMenu);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeMenu();

      if (menuToggle) {
        menuToggle.focus();
      }
    }
  });

  function updateHeader() {
    if (header) {
      header.classList.toggle('is-scrolled', window.scrollY > 32);
    }
  }

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  var revealItems = document.querySelectorAll('[data-vg-reveal]');

  if (reducedMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach(function (item) {
      item.classList.add('is-visible');
    });
    return;
  }

  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.16 });

  revealItems.forEach(function (item) {
    revealObserver.observe(item);
  });
}());
