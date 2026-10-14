/**
 * VietnamGuide Interactive Shortcode Controllers
 *
 * Conditionally enqueued only on pages rendering [vg_interactive_map]
 * or [vg_photo_dispatch] (including the Homepage Field Desk showcase).
 */
(function () {
  'use strict';

  function initInteractiveMap() {
    if (window.__vgInteractiveMapInit) {
      return;
    }
    var mapDesks = document.querySelectorAll('[data-vg-interactive-map]');
    if (!mapDesks.length) {
      return;
    }
    window.__vgInteractiveMapInit = true;

    Array.prototype.forEach.call(mapDesks, function (mapDesk) {
      var tabBtns = mapDesk.querySelectorAll('[data-corridor-target]');
      var panels = mapDesk.querySelectorAll('[data-corridor-panel]');
      var pinGroups = mapDesk.querySelectorAll('[data-corridor-pin]');

      function activate(id) {
        Array.prototype.forEach.call(tabBtns, function (btn) {
          var isActive = btn.getAttribute('data-corridor-target') === id;
          btn.classList.toggle('is-active', isActive);
          btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        Array.prototype.forEach.call(panels, function (panel) {
          var isActive = panel.getAttribute('data-corridor-panel') === id;
          panel.classList.toggle('is-active', isActive);
          if (isActive) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', '');
          }
        });
        Array.prototype.forEach.call(pinGroups, function (pin) {
          var isActive = pin.getAttribute('data-corridor-pin') === id;
          pin.classList.toggle('is-active', isActive);
          var circle = pin.querySelector('.vg-map-pin');
          if (circle) {
            circle.setAttribute('fill', isActive ? '#C99446' : '#0F2537');
          }
        });
        var statusEl = mapDesk.querySelector('[role="status"]');
        if (statusEl) {
          var activeBtn = mapDesk.querySelector('[data-corridor-target="' + id + '"]');
          var corridorName = activeBtn ? activeBtn.textContent.trim() : id;
          statusEl.textContent = 'Active transit corridor: ' + corridorName;
        }
      }

      Array.prototype.forEach.call(tabBtns, function (btn) {
        btn.addEventListener('click', function () {
          var targetId = this.getAttribute('data-corridor-target');
          if (targetId) {
            activate(targetId);
          }
        });
      });

      Array.prototype.forEach.call(pinGroups, function (pin) {
        pin.addEventListener('click', function () {
          var targetId = this.getAttribute('data-corridor-pin');
          if (targetId) {
            activate(targetId);
          }
        });
        pin.setAttribute('tabindex', '0');
        pin.setAttribute('role', 'button');
        pin.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            var targetId = this.getAttribute('data-corridor-pin');
            if (targetId) {
              activate(targetId);
            }
          }
        });
      });
    });
  }

  function initPhotoDispatch() {
    if (window.__vgDispatchInit) {
      return;
    }
    var modal = document.getElementById('vg-dispatch-modal');
    var suiteContainers = document.querySelectorAll('[data-vg-photo-dispatch-suite]');
    if (!modal && !suiteContainers.length) {
      return;
    }
    window.__vgDispatchInit = true;

    suiteContainers.forEach(function (suite) {
      var tabBtns = suite.querySelectorAll('[data-dispatch-target]');
      var panels = suite.querySelectorAll('[data-dispatch-panel]');

      function switchDispatch(targetId) {
        tabBtns.forEach(function (btn) {
          var match = btn.getAttribute('data-dispatch-target') === targetId;
          btn.classList.toggle('is-active', match);
          btn.setAttribute('aria-selected', match ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
          var match = panel.getAttribute('data-dispatch-panel') === targetId;
          panel.classList.toggle('is-active', match);
          if (match) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', '');
          }
        });
        var statusEl = suite.querySelector('[role="status"]');
        if (statusEl) {
          var activeBtn = suite.querySelector('[data-dispatch-target="' + targetId + '"]');
          var locName = activeBtn ? activeBtn.textContent.trim() : targetId;
          statusEl.textContent = 'Active visual dispatch: ' + locName;
        }
      }

      tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var targetId = btn.getAttribute('data-dispatch-target');
          if (targetId) {
            switchDispatch(targetId);
          }
        });
      });
    });

    if (!modal) {
      return;
    }

    var modalImg = document.getElementById('vg-lightbox-img');
    var modalCaption = document.getElementById('vg-lightbox-caption');
    var prevFocus = null;

    function openModal(img) {
      if (!img) {
        return;
      }
      prevFocus = document.activeElement;
      if (modalImg) {
        modalImg.src = img.getAttribute('data-full-src') || img.src;
        modalImg.alt = img.alt || '';
      }
      if (modalCaption) {
        modalCaption.textContent = img.getAttribute('data-caption') || '';
      }
      modal.removeAttribute('hidden');
      modal.classList.add('is-active');
      document.body.style.overflow = 'hidden';

      var closeBtn = modal.querySelector('.vg-lightbox-close');
      if (closeBtn) {
        closeBtn.focus();
      }
    }

    function closeModal() {
      modal.classList.remove('is-active');
      modal.setAttribute('hidden', '');
      document.body.style.overflow = '';
      if (prevFocus && typeof prevFocus.focus === 'function') {
        prevFocus.focus();
      }
    }

    document.querySelectorAll('.vg-photo-dispatch .vg-dispatch-card').forEach(function (card) {
      var img = card.querySelector('.vg-dispatch-img');
      var btn = card.querySelector('.vg-dispatch-expand-btn');

      if (img) {
        img.addEventListener('click', function () {
          openModal(img);
        });
        img.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openModal(img);
          }
        });
      }
      if (btn && img) {
        btn.addEventListener('click', function () {
          openModal(img);
        });
      }
    });

    modal.querySelectorAll('[data-vg-close-modal]').forEach(function (el) {
      el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
      if (!modal.hasAttribute('hidden') && e.key === 'Escape') {
        closeModal();
      }
    });
  }

  function bootstrapInteractiveShortcodes() {
    initInteractiveMap();
    initPhotoDispatch();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapInteractiveShortcodes);
  } else {
    bootstrapInteractiveShortcodes();
  }
})();
