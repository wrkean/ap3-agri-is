// live-search.js — Live search with auto-suggest for Agriculture Information System
(function () {
    'use strict';

    // Determine module from URL path
    function getModule() {
        var path = window.location.pathname;
        if (path.indexOf('/farmers/') !== -1) return 'farmers';
        if (path.indexOf('/farms/') !== -1) return 'farms';
        if (path.indexOf('/crops/') !== -1) return 'crops';
        if (path.indexOf('/planting/') !== -1) return 'planting';
        if (path.indexOf('/harvest/') !== -1) return 'harvest';
        return null;
    }

    var module = getModule();
    if (!module) return;

    var searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    // Create suggestion dropdown
    var dropdown = document.createElement('div');
    dropdown.id = 'searchSuggestions';
    dropdown.className = 'absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden';
    searchInput.parentElement.style.position = 'relative';
    searchInput.parentElement.appendChild(dropdown);

    var debounceTimer = null;

    function fetchSuggestions(query) {
        if (query.length < 2) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            return;
        }
        fetch('/api/search.php?module=' + encodeURIComponent(module) + '&q=' + encodeURIComponent(query) + '&limit=8')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.results || data.results.length === 0) {
                    dropdown.classList.add('hidden');
                    dropdown.innerHTML = '';
                    return;
                }
                dropdown.innerHTML = data.results.map(function (r) {
                    return '<div class="px-4 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0" data-id="' + r.id + '" data-label="' + (r.label || '').replace(/"/g, '&quot;') + '">' +
                        '<div class="text-sm font-medium text-gray-900">' + (r.label || '') + '</div>' +
                        (r.sub ? '<div class="text-xs text-gray-500">' + r.sub + '</div>' : '') +
                        '</div>';
                }).join('');
                dropdown.classList.remove('hidden');

                // Click handler for suggestions
                dropdown.querySelectorAll('[data-id]').forEach(function (item) {
                    item.addEventListener('click', function () {
                        var label = item.getAttribute('data-label');
                        searchInput.value = label;
                        dropdown.classList.add('hidden');
                        // Submit the form
                        var form = searchInput.closest('form');
                        if (form) form.submit();
                    });
                });
            })
            .catch(function () { dropdown.classList.add('hidden'); });
    }

    // Live filter table rows on current page
    function filterTableRows(query) {
        var table = document.querySelector('table tbody');
        if (!table) return;
        var rows = table.querySelectorAll('tr');
        var q = query.toLowerCase();
        rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            row.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', function () {
        var query = searchInput.value.trim();
        filterTableRows(query);

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            fetchSuggestions(query);
        }, 300);
    });

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Close on Escape
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });
})();
