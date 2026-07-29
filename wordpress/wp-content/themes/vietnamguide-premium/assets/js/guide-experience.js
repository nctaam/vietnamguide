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

  function setActive(id) {
    targets.forEach(function (target) {
      target.link.classList.toggle('is-active', target.section.id === id);
    });
  }

  if ('IntersectionObserver' in window && sections.length > 0) {
    var observer = new IntersectionObserver(function (entries) {
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
