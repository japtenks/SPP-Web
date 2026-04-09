(function () {
  const professionDetailCache = new Map();
  let activeSearchToken = 0;

  function iconBaseUrl() {
    return window.marketplaceConfig && typeof window.marketplaceConfig.iconBaseUrl === 'string'
      ? window.marketplaceConfig.iconBaseUrl
      : (document.documentElement.getAttribute('data-marketplace-icon-base-url') || '/');
  }

  function professionUrl(name, realmId, tab) {
    let url = 'index.php?n=server&sub=character&realm=' + encodeURIComponent(realmId) + '&character=' + encodeURIComponent(name);
    if (tab) {
      url += '&tab=' + encodeURIComponent(tab);
    }
    return url;
  }

  function itemUrl(itemId, realmId) {
    return 'index.php?n=server&sub=item&realm=' + encodeURIComponent(realmId) + '&item=' + encodeURIComponent(itemId);
  }

  function recipeMarkup(craft, realmId) {
    const itemName = craft.item_name ? 'Creates ' + escapeHtml(craft.item_name) + ' - ' : '';
    const inner = '<img src="' + escapeAttribute(craft.icon) + '" alt="' + escapeAttribute(craft.spell_name) + '">' +
      '<span><strong style="color: ' + escapeAttribute(qualityColor(craft.quality)) + ';">' + escapeHtml(craft.spell_name) + '</strong>' +
      '<small>' + itemName + Number(craft.required_rank || 0) + ' skill</small></span>';

    if (Number(craft.item_entry || 0) > 0) {
      return '<a class="marketplace-recipe" href="' + escapeAttribute(itemUrl(craft.item_entry, realmId)) + '" data-tooltip-item="' + Number(craft.item_entry) + '">' + inner + '</a>';
    }
    return '<div class="marketplace-recipe">' + inner + '</div>';
  }

  function botCardMarkup(bot, realmId) {
    const specialRecipes = Array.isArray(bot.special_crafts) ? bot.special_crafts : [];
    const knownRecipes = Array.isArray(bot.crafts) ? bot.crafts : [];
    const specialMarkup = specialRecipes.length
      ? specialRecipes.map(function (craft) { return recipeMarkup(craft, realmId); }).join('')
      : '<div class="marketplace-bot-empty marketplace-recipe-body collapse-card__body">No special non-trainer recipes were recorded for this crafter yet.</div>';
    const knownMarkup = knownRecipes.length
      ? knownRecipes.map(function (craft) { return recipeMarkup(craft, realmId); }).join('')
      : '<div class="marketplace-bot-empty marketplace-recipe-body collapse-card__body">No craftable items were found for this crafter yet.</div>';

    return '<article class="marketplace-bot">' +
      '<div class="marketplace-bot-head">' +
      '<a class="marketplace-bot-link" href="' + escapeAttribute(professionUrl(bot.name, realmId, '')) + '">' +
      '<span class="marketplace-bot-avatars">' +
      '<img src="' + escapeAttribute(raceIcon(bot.race, bot.gender)) + '" alt="">' +
      '<img src="' + escapeAttribute(classIcon(bot.class)) + '" alt="">' +
      '</span><span><strong class="marketplace-bot-name">' + escapeHtml(bot.name) + '</strong>' +
      '<span class="marketplace-bot-meta">Level ' + Number(bot.level) + ' - ' + escapeHtml(bot.tier) + '</span></span></a>' +
      '<span class="marketplace-bot-rank">' + Number(bot.value) + '/' + Number(bot.max) + '</span></div>' +
      '<div class="marketplace-crafter-meta"><span class="marketplace-online-pill ' + (bot.online ? 'is-online' : 'is-offline') + '">' +
      '<span class="marketplace-online-pill__dot" aria-hidden="true"></span>' + (bot.online ? 'Online' : 'Offline') + '</span></div>' +
      '<div class="marketplace-progress"><div class="marketplace-progress-track"><div class="marketplace-progress-fill" style="width: ' +
      progressWidth(bot.value, bot.max) + '%"></div></div><div class="marketplace-progress-copy"><span>Profession skill</span><span>' +
      Number(bot.special_craft_count || 0) + ' special</span></div></div>' +
      '<div class="marketplace-bot-summary">' +
      statMarkup(bot.value, 'Skill Rank') +
      statMarkup(bot.special_craft_count || 0, 'Special Recipes') +
      statMarkup(bot.craft_count || 0, 'Known Recipes') +
      '</div>' +
      detailBoxMarkup('Special Recipes', bot.special_craft_count || 0, specialMarkup) +
      detailBoxMarkup('Known Recipes', bot.craft_count || 0, knownMarkup) +
      '</article>';
  }

  function statMarkup(value, label) {
    return '<div class="marketplace-bot-stat"><strong>' + Number(value) + '</strong><span>' + escapeHtml(label) + '</span></div>';
  }

  function detailBoxMarkup(title, count, bodyMarkup) {
    return '<details class="marketplace-recipe-box collapse-card"><summary class="marketplace-recipe-summary collapse-card__summary">' +
      '<span class="collapse-card__copy"><strong class="collapse-card__title">' + escapeHtml(title) + '</strong>' +
      '<span class="collapse-card__meta">' + Number(count) + ' listed</span></span>' +
      '<span class="collapse-card__caret" aria-hidden="true"></span></summary>' +
      '<div class="marketplace-recipe-list marketplace-recipe-body collapse-card__body">' + bodyMarkup + '</div></details>';
  }

  function renderProfessionDetail(payload) {
    const profession = payload && payload.profession ? payload.profession : null;
    if (!profession) {
      return '<div class="marketplace-bot-empty">Unable to load profession details.</div>';
    }

    const tierOrder = Array.isArray(payload.tierOrder) ? payload.tierOrder : [];
    let holderMarkup = '';
    if (Array.isArray(profession.special_holders) && profession.special_holders.length) {
      holderMarkup = '<p class="marketplace-profession-note">Special recipe holders: ' + profession.special_holders.map(function (holder) {
        return '<a class="marketplace-profession-holder-link" href="' + escapeAttribute(professionUrl(holder.name, payload.realmId, 'professions')) + '">' +
          escapeHtml(holder.name) + ' (' + Number(holder.count) + ')</a>';
      }).join(', ') + '</p>';
    }

    let tiersMarkup = '';
    tierOrder.forEach(function (tierName) {
      const bots = profession.tiers && profession.tiers[tierName] ? profession.tiers[tierName] : [];
      if (!bots.length) {
        return;
      }
      tiersMarkup += '<section class="marketplace-tier-panel"><div class="marketplace-tier-head">' +
        '<h3 class="marketplace-tier-title">' + escapeHtml(tierName) + '</h3>' +
        '<span class="marketplace-tier-copy">' + bots.length + ' crafters</span></div>' +
        '<div class="marketplace-bot-grid">' + bots.map(function (bot) { return botCardMarkup(bot, payload.realmId); }).join('') + '</div></section>';
    });

    if (!tiersMarkup) {
      tiersMarkup = '<div class="marketplace-bot-empty">No crafters were available for this profession.</div>';
    }

    return holderMarkup + '<div class="marketplace-profession-detail-shell">' + tiersMarkup + '</div>';
  }

  function renderSearchResults(payload) {
    const results = payload && payload.results ? payload.results : null;
    const matches = results && Array.isArray(results.matches) ? results.matches : [];
    if (!matches.length) {
      return '<div class="marketplace-search-empty feature-panel">No crafters matched that item search.</div>';
    }

    return '<div class="marketplace-search-results-list">' + matches.map(function (match) {
      const itemMarkup = Number(match.item_entry || 0) > 0
        ? '<a class="marketplace-search-result-item" href="' + escapeAttribute(itemUrl(match.item_entry, payload.realmId)) + '" data-tooltip-item="' + Number(match.item_entry) + '">' +
          '<img src="' + escapeAttribute(match.icon) + '" alt="' + escapeAttribute(match.spell_name) + '">' +
          '<span><strong style="color: ' + escapeAttribute(qualityColor(match.quality)) + ';">' + escapeHtml(match.item_name || match.spell_name) + '</strong>' +
          '<span class="marketplace-search-result-copy">' + Number(match.required_rank || 0) + ' skill required</span></span></a>'
        : '';

      return '<article class="marketplace-search-result feature-panel"><div class="marketplace-search-result-head">' +
        '<div class="marketplace-search-result-profession"><img class="marketplace-profession-icon" src="' + escapeAttribute(match.profession_icon) + '" alt="' + escapeAttribute(match.profession_name) + '">' +
        '<div><strong>' + escapeHtml(match.spell_name) + '</strong><div class="marketplace-search-result-copy">' +
        escapeHtml(match.profession_name) + ' - ' + escapeHtml(match.tier) + '</div></div></div>' +
        '<span class="marketplace-search-result-tag' + (match.is_special ? ' is-special' : '') + '">' + (match.is_special ? 'Special' : 'Known') + '</span></div>' +
        '<div class="marketplace-search-result-body"><a class="marketplace-bot-link" href="' + escapeAttribute(professionUrl(match.bot_name, payload.realmId, '')) + '">' +
        '<span class="marketplace-bot-avatars"><img src="' + escapeAttribute(raceIcon(match.bot_race, match.bot_gender)) + '" alt="">' +
        '<img src="' + escapeAttribute(classIcon(match.bot_class)) + '" alt=""></span><span><strong class="marketplace-bot-name">' +
        escapeHtml(match.bot_name) + '</strong><span class="marketplace-bot-meta">Level ' + Number(match.bot_level) + ' - ' +
        Number(match.bot_value) + '/' + Number(match.bot_max) + '</span></span></a>' + itemMarkup + '</div></article>';
    }).join('') + '</div>';
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
  }

  function qualityColor(quality) {
    switch (Number(quality)) {
      case 0: return '#9d9d9d';
      case 1: return '#ffffff';
      case 2: return '#1eff00';
      case 3: return '#0070dd';
      case 4: return '#a335ee';
      case 5: return '#ff8000';
      default: return '#e6cc80';
    }
  }

  function raceIcon(raceId, gender) {
    const suffix = Number(gender) === 1 ? 'female' : 'male';
    const icons = {
      1: 'achievement_character_human_' + suffix,
      2: 'achievement_character_orc_' + suffix,
      3: 'achievement_character_dwarf_' + suffix,
      4: 'achievement_character_nightelf_' + suffix,
      5: 'achievement_character_undead_' + suffix,
      6: 'achievement_character_tauren_' + suffix,
      7: 'achievement_character_gnome_' + suffix,
      8: 'achievement_character_troll_' + suffix,
      10: 'achievement_character_bloodelf_' + suffix,
      11: 'achievement_character_draenei_' + suffix
    };
    return iconBaseUrl() + (icons[raceId] || '404.png');
  }

  function classIcon(classId) {
    const filenames = {
      1: 'class-1.jpg',
      2: 'class-2.jpg',
      3: 'class-3.jpg',
      4: 'class-4.jpg',
      5: 'class-5.jpg',
      6: 'class-6.gif',
      7: 'class-7.jpg',
      8: 'class-8.jpg',
      9: 'class-9.jpg',
      11: 'class-11.jpg'
    };
    return iconBaseUrl() + (filenames[classId] || '404.png');
  }

  function progressWidth(value, max) {
    const current = Number(value || 0);
    const total = Number(max || 0);
    if (total <= 0) {
      return 0;
    }
    return Math.min(100, Math.max(0, Math.round((current / total) * 100)));
  }

  function bindTooltipDelegation(root) {
    if (!root || !window.sppItemTooltips) return;
    window.sppItemTooltips.bindDelegation(root, '[data-tooltip-item]', {
      realmId: window.marketplaceConfig.realmId,
      loadingMessage: 'Loading item tooltip...',
      errorMessage: 'Unable to load item tooltip.'
    });
  }

  function initProfessionDetails() {
    const detailCards = document.querySelectorAll('.marketplace-profession-detail-toggle[data-skill-id]');
    detailCards.forEach(function (card) {
      card.addEventListener('toggle', function () {
        if (!card.open) return;
        const skillId = Number(card.getAttribute('data-skill-id'));
        const mount = card.querySelector('.marketplace-lazy-detail');
        if (!skillId || !mount || mount.getAttribute('data-loaded') === '1') {
          return;
        }

        if (professionDetailCache.has(skillId)) {
          mount.innerHTML = professionDetailCache.get(skillId);
          mount.setAttribute('data-loaded', '1');
          return;
        }

        mount.innerHTML = '<div class="marketplace-loading feature-panel">Loading profession details...</div>';
        window.sppAsync.getJson(window.marketplaceConfig.apiUrl + '&action=profession&skill=' + encodeURIComponent(skillId), {
          errorMessage: 'Unable to load profession details right now.'
        })
          .then(function (payload) {
            const markup = payload && payload.error
              ? '<div class="marketplace-bot-empty">' + escapeHtml(payload.error) + '</div>'
              : renderProfessionDetail(payload);
            professionDetailCache.set(skillId, markup);
            mount.innerHTML = markup;
            mount.setAttribute('data-loaded', '1');
          })
          .catch(function () {
            mount.innerHTML = '<div class="marketplace-bot-empty">Unable to load profession details right now.</div>';
          });
      });
    });
  }

  function initMarketplaceSearch() {
    const input = document.getElementById('marketplace-craft-search');
    const grid = document.getElementById('marketplace-profession-grid');
    const results = document.getElementById('marketplace-search-results');
    if (!input || !grid || !results) {
      return;
    }

    let debounceTimer = null;

    function applySearch() {
      const query = input.value.trim();
      if (!query) {
        results.hidden = true;
        results.innerHTML = '';
        grid.hidden = false;
        return;
      }

      activeSearchToken += 1;
      const token = activeSearchToken;
      results.hidden = false;
      grid.hidden = true;
      results.innerHTML = '<div class="marketplace-loading feature-panel">Searching marketplace...</div>';

      window.sppAsync.getJson(window.marketplaceConfig.apiUrl + '&action=search&q=' + encodeURIComponent(query), {
        errorMessage: 'Search is unavailable right now.'
      })
        .then(function (payload) {
          if (token !== activeSearchToken) {
            return;
          }
          results.innerHTML = renderSearchResults(payload);
        })
        .catch(function () {
          if (token !== activeSearchToken) {
            return;
          }
          results.innerHTML = '<div class="marketplace-search-empty feature-panel">Search is unavailable right now.</div>';
        });
    }

    input.addEventListener('input', function () {
      if (debounceTimer) {
        window.clearTimeout(debounceTimer);
      }
      debounceTimer = window.setTimeout(applySearch, 220);
    });
  }

  function init() {
    bindTooltipDelegation(document.body);
    initProfessionDetails();
    initMarketplaceSearch();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
