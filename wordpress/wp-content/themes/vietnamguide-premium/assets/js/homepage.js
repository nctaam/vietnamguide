(function () {
  'use strict';

  document.documentElement.classList.add('vg-js');

  var header = document.querySelector('[data-vg-header]');
  var menuToggle = document.querySelector('[data-vg-menu-toggle]');
  var navigation = document.querySelector('[data-vg-navigation]');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var desktopViewport = window.matchMedia('(min-width: 961px)');

  function closeMenu() {
    if (menuToggle) {
      menuToggle.setAttribute('aria-expanded', 'false');
    }

    if (navigation) {
      navigation.classList.remove('is-open');
    }

    document.body.classList.remove('vg-menu-open');
  }

  function handleViewportChange(event) {
    if (event.matches) {
      closeMenu();
    }
  }

  if (desktopViewport.addEventListener) {
    desktopViewport.addEventListener('change', handleViewportChange);
  } else if (desktopViewport.addListener) {
    desktopViewport.addListener(handleViewportChange);
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
    if (
      event.key === 'Escape' &&
      menuToggle &&
      menuToggle.getAttribute('aria-expanded') === 'true'
    ) {
      closeMenu();
      menuToggle.focus();
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

  var backToTop = document.querySelector('[data-vg-back-to-top]');
  if (backToTop) {
    function toggleBackToTop() {
      backToTop.classList.toggle('is-visible', window.scrollY > 480);
    }
    window.addEventListener('scroll', toggleBackToTop, { passive: true });
    toggleBackToTop();
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
    });
  }

  var siteHeader = document.querySelector('.vg-site-header');
  if (siteHeader && !reducedMotion) {
    var progressBar = document.createElement('div');
    progressBar.className = 'vg-reading-progress';
    progressBar.setAttribute('aria-hidden', 'true');
    siteHeader.appendChild(progressBar);

    var progressTicking = false;
    function updateReadingProgress() {
      var docElem = document.documentElement;
      var maxScroll = docElem.scrollHeight - window.innerHeight;
      var percent = maxScroll > 0 ? (window.scrollY / maxScroll) * 100 : 0;
      progressBar.style.width = Math.min(100, Math.max(0, percent)) + '%';
      progressTicking = false;
    }

    window.addEventListener('scroll', function () {
      if (!progressTicking) {
        window.requestAnimationFrame(updateReadingProgress);
        progressTicking = true;
      }
    }, { passive: true });
    updateReadingProgress();
  }

  document.addEventListener('click', function (e) {
    var target = e.target;
    var copyBtn = target && target.closest ? target.closest('[data-vg-copy-link]') : null;
    if (!copyBtn) {
      return;
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(window.location.href).then(function () {
        var textSpan = copyBtn.querySelector('.vg-copy-link__text');
        if (textSpan) {
          var originalText = textSpan.textContent;
          textSpan.textContent = 'Link copied!';
          copyBtn.classList.add('is-copied');
          setTimeout(function () {
            textSpan.textContent = originalText;
            copyBtn.classList.remove('is-copied');
          }, 2400);
        }
      });
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
      var searchInput = document.querySelector('.search-field, .wp-block-search__input, input[type="search"]');
      if (searchInput) {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
      }
    }
  });
}());
