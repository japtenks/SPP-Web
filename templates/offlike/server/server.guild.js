(function () {
  function applyMeetingLocation(select) {
    if (!select) {
      return;
    }

    var targetId = select.getAttribute('data-guild-meeting-target');
    if (!targetId) {
      return;
    }

    var textarea = document.getElementById(targetId);
    if (!textarea) {
      return;
    }

    var location = (select.value || '').trim();
    if (location === '') {
      return;
    }

    var start = (select.getAttribute('data-meeting-start') || '15:00').trim();
    var end = (select.getAttribute('data-meeting-end') || '18:00').trim();
    var directive = 'Meeting: ' + location + ' ' + start + ' ' + end;
    var current = textarea.value || '';
    var pattern = /^\s*Meeting:\s*(.+?)\s+(\d{1,2}:\d{2}(?:[AaPp][Mm])?)\s+(\d{1,2}:\d{2}(?:[AaPp][Mm])?)\s*$/im;

    if (pattern.test(current)) {
      textarea.value = current.replace(pattern, directive).trim();
      return;
    }

    var lines = current.split(/\r?\n/);
    var kept = [];
    for (var i = 0; i < lines.length; i += 1) {
      var line = lines[i].trim();
      if (line === '') {
        continue;
      }
      if (/^Meeting:\s*/i.test(line)) {
        continue;
      }
      kept.push(lines[i].trimEnd());
    }

    kept.push(directive);
    textarea.value = kept.join('\n');
  }

  function syncGuildColumns() {
    document.querySelectorAll('[data-guild-shell]').forEach(function (shell) {
      var sideStack = shell.querySelector('[data-guild-side-stack]');
      var rosterPanel = shell.querySelector('[data-guild-roster-panel]');
      var rosterScroll = shell.querySelector('[data-guild-roster-scroll]');
      if (!sideStack || !rosterPanel || !rosterScroll) {
        return;
      }

      sideStack.style.maxHeight = '';
      sideStack.style.height = '';
      sideStack.classList.remove('is-scroll-bound');
      rosterPanel.style.maxHeight = '';
      rosterPanel.style.height = '';
      rosterScroll.style.maxHeight = '';
      rosterScroll.style.height = '';
      rosterScroll.classList.remove('is-scroll-bound');
      rosterPanel.classList.remove('is-height-bound');

      var rosterRect = rosterPanel.getBoundingClientRect();
      var sideRect = sideStack.getBoundingClientRect();
      var leftHeight = Math.ceil(rosterRect.height);
      var rightHeight = Math.ceil(sideRect.height);
      if (!leftHeight || !rightHeight) {
        return;
      }

      var shorterHeight = Math.min(leftHeight, rightHeight);
      if (!shorterHeight || shorterHeight < 320) {
        return;
      }

      var stickyTop = 88;
      var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      if (viewportHeight > 0) {
        var viewportTarget = Math.max(320, Math.floor(viewportHeight - stickyTop - 20));
        shorterHeight = Math.min(shorterHeight, viewportTarget);
      }

      function computeInnerHeight(panel, scrollChild, targetHeight) {
        var reservedHeight = 0;
        Array.prototype.forEach.call(panel.children, function (child) {
          if (child === scrollChild) {
            return;
          }
          var rect = child.getBoundingClientRect();
          var style = window.getComputedStyle(child);
          reservedHeight += rect.height;
          reservedHeight += parseFloat(style.marginTop || '0') + parseFloat(style.marginBottom || '0');
        });

        var panelStyle = window.getComputedStyle(panel);
        var panelInner = targetHeight - parseFloat(panelStyle.paddingTop || '0') - parseFloat(panelStyle.paddingBottom || '0');

        return Math.floor(panelInner - reservedHeight);
      }

      if (leftHeight > rightHeight) {
        var rosterScrollHeight = computeInnerHeight(rosterPanel, rosterScroll, shorterHeight);
        if (rosterScrollHeight > 280) {
          rosterPanel.style.maxHeight = shorterHeight + 'px';
          rosterPanel.style.height = shorterHeight + 'px';
          rosterPanel.classList.add('is-height-bound');
          rosterScroll.style.maxHeight = rosterScrollHeight + 'px';
          rosterScroll.style.height = rosterScrollHeight + 'px';
          rosterScroll.classList.add('is-scroll-bound');
        }
      } else if (rightHeight > leftHeight) {
        sideStack.style.maxHeight = shorterHeight + 'px';
        sideStack.style.height = shorterHeight + 'px';
        sideStack.classList.add('is-scroll-bound');
      }
    });
  }

  function bindGuildControls() {
    document.querySelectorAll('[data-guild-meeting-select]').forEach(function (select) {
      select.addEventListener('change', function () {
        applyMeetingLocation(select);
      });
    });

    document.querySelectorAll('[data-guild-maxonly-toggle]').forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        var form = checkbox.form;
        if (form) {
          form.submit();
        }
      });
    });
  }

  function init() {
    bindGuildControls();
    syncGuildColumns();
  }

  window.addEventListener('load', init);
  window.addEventListener('resize', syncGuildColumns);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(syncGuildColumns);
  }
})();
