document.addEventListener('DOMContentLoaded', function () {
  const qualityFilter = document.getElementById('ah-quality-filter');
  if (qualityFilter) {
    const qualityClasses = ['quality-any', 'quality-0', 'quality-1', 'quality-2', 'quality-3', 'quality-4'];
    const syncQualityFilterClass = function () {
      qualityFilter.classList.remove.apply(qualityFilter.classList, qualityClasses);
      const value = qualityFilter.value;
      qualityFilter.classList.add(value === '-1' ? 'quality-any' : 'quality-' + value);
    };

    qualityFilter.addEventListener('change', syncQualityFilterClass);
    syncQualityFilterClass();
  }

  if (window.sppItemTooltips) {
    window.sppItemTooltips.bindSelector('.js-ah-tooltip', {
      itemIdAttribute: 'itemId',
      realmIdAttribute: 'realmId',
      loadingMessage: 'Loading item tooltip...',
      errorMessage: 'Unable to load item tooltip.'
    });
  }
});
