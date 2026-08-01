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
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      window.requestAnimationFrame(updateProgress);
      ticking = true;
    }
  }, { passive: true });

  updateProgress();
}());
