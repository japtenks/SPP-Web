(function () {
  function initSetsTooltips() {
    if (!window.sppItemTooltips) {
      return;
    }

    const realmId = parseInt(document.body.getAttribute('data-spp-sets-realm') || '0', 10);
    const tip = document.createElement('div');
    tip.className = 'talent-tt';
    tip.style.display = 'none';
    document.body.appendChild(tip);

    let anchor = null;

    function place(el) {
      const pad = 8;
      const r = el.getBoundingClientRect();
      tip.style.visibility = 'hidden';
      tip.style.display = 'block';
      const t = tip.getBoundingClientRect();
      let left = Math.max(6, Math.min(r.left + (r.width - t.width) / 2, innerWidth - t.width - 6));
      let top = Math.max(6, r.top - t.height - pad);
      tip.style.left = left + 'px';
      tip.style.top = top + 'px';
      tip.style.visibility = 'visible';
    }

    function decodeHtml(raw) {
      const ta = document.createElement('textarea');
      ta.innerHTML = raw;
      return ta.value;
    }

    function showHtml(el, html) {
      anchor = el;
      tip.innerHTML = html;
      place(el);
    }

    function show(el) {
      const itemId = parseInt(el.getAttribute('data-item-id') || '0', 10);
      if (itemId > 0) {
        showHtml(el, '<div class="tt-item"><h5>Loading...</h5></div>');
        window.sppItemTooltips.fetchTooltipHtml(itemId, realmId, 0, {
          errorMessage: 'Item unavailable'
        })
          .then(function (html) {
            if (anchor === el) {
              showHtml(el, html);
            }
          })
          .catch(function () {
            if (anchor === el) {
              showHtml(el, '<div class="tt-item"><h5>Item unavailable</h5></div>');
            }
          });
        return;
      }

      showHtml(el, decodeHtml(el.getAttribute('data-tip-html') || ''));
    }

    function hide() {
      tip.style.display = 'none';
      anchor = null;
    }

    function nudge() {
      if (anchor && tip.style.display !== 'none') place(anchor);
    }

    document.addEventListener('mouseover', function (e) {
      const el = e.target.closest('.js-set-tip, .js-item-tip');
      if (el) show(el);
    });
    document.addEventListener('mouseout', function (e) {
      const el = e.target.closest('.js-set-tip, .js-item-tip');
      if (el && !(e.relatedTarget && el.contains(e.relatedTarget))) hide();
    });
    addEventListener('scroll', nudge, { passive: true });
    addEventListener('resize', nudge);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSetsTooltips, { once: true });
  } else {
    initSetsTooltips();
  }
})();
