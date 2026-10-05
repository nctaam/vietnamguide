(function () {
  'use strict';

  var guide = document.querySelector('[data-vg-guide]');
  if (!guide) {
    return;
  }

  var legacyTables = guide.querySelectorAll('table.vg-decision-table');
  Array.prototype.forEach.call(legacyTables, function (table) {
    if (
      table.parentElement &&
      table.parentElement.classList.contains('vg-decision-table__scroll')
    ) {
      return;
    }

    if (!table.parentNode) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.className = 'vg-decision-table__scroll';
    wrapper.setAttribute('tabindex', '0');
    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);
  });

  var scrollContainers = guide.querySelectorAll('.vg-decision-table__scroll, .wp-block-table');
  Array.prototype.forEach.call(scrollContainers, function (scrollContainer) {
    if (!scrollContainer.hasAttribute('tabindex')) {
      scrollContainer.setAttribute('tabindex', '0');
    }
  });

  var links = Array.prototype.slice.call(
    guide.querySelectorAll('.vg-guide-toc a[href^="#"], .vg-guide-jump a[href^="#"]')
  );
  var targets = links.map(function (link) {
    var hash = link.getAttribute('href');
    if (!hash || hash.length < 2) {
      return null;
    }

    var id;
    try {
      id = decodeURIComponent(hash.slice(1));
    } catch (error) {
      return null;
    }

    var section = document.getElementById(id);
    if (!section) {
      return null;
    }

    return { link: link, section: section };
  }).filter(Boolean);
  var sections = targets.map(function (target) {
    return target.section;
  }).filter(function (section, index, allSections) {
    return allSections.indexOf(section) === index;
  });
  var activeId = null;
  var lastSection = sections.length > 0 ? sections[sections.length - 1] : null;
  var nearGuideEnd = false;

  function setActive(id) {
    if (id === activeId) {
      return;
    }

    activeId = id;
    targets.forEach(function (target) {
      var isActive = target.section.id === id;
      target.link.classList.toggle('is-active', isActive);
      if (isActive) {
        target.link.setAttribute('aria-current', 'location');
        if (typeof target.link.closest === 'function') {
          var jumpRail = target.link.closest('.vg-guide-jump');
          if (jumpRail && typeof jumpRail.scrollTo === 'function') {
            var offset = target.link.offsetLeft - (jumpRail.clientWidth / 2) + (target.link.offsetWidth / 2);
            jumpRail.scrollTo({ left: Math.max(0, offset), behavior: 'smooth' });
          }
        }
      } else {
        target.link.removeAttribute('aria-current');
      }
    });
  }

  if ('IntersectionObserver' in window && sections.length > 0) {
    var observer = new IntersectionObserver(function (entries) {
      if (nearGuideEnd && lastSection) {
        setActive(lastSection.id);
        return;
      }

      var visible = entries.filter(function (entry) {
        return entry.isIntersecting;
      });

      if (visible.length > 0) {
        visible.sort(function (a, b) {
          return a.boundingClientRect.top - b.boundingClientRect.top;
        });
        setActive(visible[0].target.id);
      }
    }, { rootMargin: '-20% 0px -68% 0px', threshold: [0, 1] });

    sections.forEach(function (section) {
      observer.observe(section);
    });
  }

  var reducedMotion = typeof window.matchMedia === 'function' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var progressBar = document.getElementById('vg-reading-progress');
  var dock = document.getElementById('vg-floating-dock');
  var dockPct = document.getElementById('vg-dock-pct');
  var topBtn = document.getElementById('vg-dock-top');
  var tocBtn = document.getElementById('vg-dock-toc');
  var toolsBtn = document.getElementById('vg-dock-tools');
  var popover = document.getElementById('vg-dock-popover');
  var closeBtn = document.getElementById('vg-dock-close');

  var ticking = false;

  function updateProgress() {
    var rect = guide.getBoundingClientRect();
    var total = Math.max(1, rect.height - window.innerHeight);
    var progress = Math.max(0, Math.min(100, (-rect.top / total) * 100));

    nearGuideEnd = Boolean(lastSection) && (
      progress >= 99.5 ||
      (rect.top < 0 && rect.bottom <= window.innerHeight * 1.15)
    );
    if (nearGuideEnd) {
      setActive(lastSection.id);
    }

    guide.style.setProperty('--vg-guide-progress', progress.toFixed(2));

    var docElem = document.documentElement;
    var maxScroll = docElem ? docElem.scrollHeight - window.innerHeight : 0;
    var scrollY = typeof window.scrollY === 'number' ? window.scrollY : 0;
    var pct = maxScroll > 0 ? (scrollY / maxScroll) * 100 : progress;
    var clamped = Math.min(100, Math.max(0, Math.round(pct)));

    if (progressBar) {
      progressBar.style.width = clamped + '%';
      progressBar.setAttribute('aria-valuenow', String(clamped));
    }
    if (dockPct) {
      dockPct.textContent = clamped + '%';
    }
    if (dock) {
      dock.style.display = scrollY > 280 ? 'block' : 'none';
      var isMobile = typeof window.innerWidth === 'number' && window.innerWidth < 640;
      var banner = typeof document.querySelector === 'function' ? document.querySelector('.vg-cookie-banner, .vg-pwa-banner') : null;
      var bannerVisible = banner && banner.style && banner.style.display !== 'none';
      if (isMobile && bannerVisible) {
        dock.style.bottom = '96px';
      } else if (dock.style && dock.style.bottom === '96px') {
        dock.style.bottom = '';
      }
    }

    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      window.requestAnimationFrame(updateProgress);
      ticking = true;
    }
  }, { passive: true });

  updateProgress();

  if (topBtn) {
    topBtn.addEventListener('click', function () {
      if (typeof window.scrollTo === 'function') {
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
      }
    });
  }

  if (tocBtn) {
    tocBtn.addEventListener('click', function () {
      var toc = typeof document.querySelector === 'function' ? document.querySelector('.vg-guide-spine__toc, .vg-guide-jump') : null;
      if (toc && typeof toc.scrollIntoView === 'function') {
        toc.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
      }
    });
  }

  function toggleTools(forceState) {
    if (!popover || !toolsBtn) return;
    var isOpen = forceState !== undefined ? forceState : toolsBtn.getAttribute('aria-expanded') !== 'true';
    toolsBtn.setAttribute('aria-expanded', String(isOpen));
    popover.style.display = isOpen ? 'block' : 'none';
  }

  if (toolsBtn) {
    toolsBtn.addEventListener('click', function (e) {
      if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
      toggleTools();
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
      toggleTools(false);
    });
  }

  if (typeof document.addEventListener === 'function') {
    document.addEventListener('click', function (e) {
      if (popover && popover.style && popover.style.display === 'block') {
        if (!popover.contains(e.target) && (!toolsBtn || !toolsBtn.contains(e.target))) {
          toggleTools(false);
        }
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && popover && popover.style && popover.style.display === 'block') {
        toggleTools(false);
        if (toolsBtn && typeof toolsBtn.focus === 'function') toolsBtn.focus();
      }
    });
  }
}());
