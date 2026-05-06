/**
 * Global Search Functionality
 * Searches across all modules (Customers, Leads, Deals, Meetings, etc.)
 */

(function() {
    'use strict';

    let resultsContainer = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Wait for DOM to be ready
    function initSearch() {
        const searchInput = document.querySelector('.search-wrapper input');
        if (!searchInput) {
            // Retry if search input not found yet
            setTimeout(initSearch, 100);
            return;
        }

        resultsContainer = createResultsContainer();
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                if (resultsContainer) resultsContainer.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        // Close results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-wrapper') && !e.target.closest('.search-results-dropdown')) {
                if (resultsContainer) resultsContainer.style.display = 'none';
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSearch);
    } else {
        initSearch();
    }

    function createResultsContainer() {
        const searchWrapper = document.querySelector('.search-wrapper');
        if (!searchWrapper) return null;

        // Remove existing container if any
        const existing = searchWrapper.querySelector('.search-results-dropdown');
        if (existing) existing.remove();

        const container = document.createElement('div');
        container.className = 'search-results-dropdown';
        container.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 8px;
            display: none;
        `;
        searchWrapper.style.position = 'relative';
        searchWrapper.appendChild(container);
        return container;
    }

    async function performSearch(query) {
        if (!resultsContainer) return;

        resultsContainer.innerHTML = '<div class="p-3 text-center"><span class="spinner-border spinner-border-sm"></span></div>';
        resultsContainer.style.display = 'block';

        try {
            const results = await Promise.all([
                searchCustomers(query),
                searchLeads(query),
                searchDeals(query),
                searchMeetings(query),
                searchTasks(query),
            ]);

            const allResults = results.flat().filter(r => r !== null);
            console.log('All search results:', allResults);

            if (allResults.length === 0) {
                resultsContainer.innerHTML = '<div class="p-3 text-center text-muted">No results found</div>';
                return;
            }

            renderResults(allResults);
        } catch (error) {
            console.error('Search error:', error);
            resultsContainer.innerHTML = '<div class="p-3 text-center text-danger">Search failed</div>';
        }
    }

    function getHeaders() {
        return {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        };
    }

    async function searchCustomers(query) {
        try {
            const response = await fetch(`/api/customers/search?q=${encodeURIComponent(query)}`, {
                headers: getHeaders()
            });
            if (!response.ok) {
                console.warn('Customer search failed:', response.status);
                return [];
            }
            const data = await response.json();
            console.log('Customer search response:', data);
            
            return (data || []).map(item => ({
                type: 'Customer',
                name: item.name,
                email: item.email,
                url: `/masters/customers?search=${encodeURIComponent(query)}`,
                indexUrl: `/masters/customers`,
                icon: 'fa-user',
                color: 'success'
            }));
        } catch (e) {
            console.error('Customer search error:', e);
            return [];
        }
    }

    async function searchLeads(query) {
        try {
            const response = await fetch(`/api/leads?search=${encodeURIComponent(query)}&limit=5`, {
                headers: getHeaders()
            });
            if (!response.ok) {
                console.warn('Lead search failed:', response.status);
                return [];
            }
            const data = await response.json();
            console.log('Lead search response:', data);
            
            // Handle paginated response
            const items = data.data?.data || data.data || [];
            if (!Array.isArray(items)) return [];
            
            return items.map(item => ({
                type: 'Lead',
                name: item.name,
                email: item.email,
                url: `/leads?search=${encodeURIComponent(query)}`,
                indexUrl: `/leads`,
                icon: 'fa-bullhorn',
                color: 'warning'
            }));
        } catch (e) {
            console.error('Lead search error:', e);
            return [];
        }
    }

    async function searchDeals(query) {
        try {
            const response = await fetch(`/api/deals?search=${encodeURIComponent(query)}&limit=5`, {
                headers: getHeaders()
            });
            if (!response.ok) {
                console.warn('Deal search failed:', response.status);
                return [];
            }
            const data = await response.json();
            console.log('Deal search response:', data);
            
            // Handle paginated response
            const items = data.data?.data || data.data || [];
            if (!Array.isArray(items)) return [];
            
            return items.map(item => ({
                type: 'Deal',
                name: item.title,
                email: item.customer?.name,
                url: `/deals?search=${encodeURIComponent(query)}`,
                indexUrl: `/deals`,
                icon: 'fa-handshake',
                color: 'info'
            }));
        } catch (e) {
            console.error('Deal search error:', e);
            return [];
        }
    }

    async function searchMeetings(query) {
        try {
            const response = await fetch(`/api/meetings?search=${encodeURIComponent(query)}&limit=5`, {
                headers: getHeaders()
            });
            if (!response.ok) {
                console.warn('Meeting search failed:', response.status);
                return [];
            }
            const data = await response.json();
            console.log('Meeting search response:', data);
            
            // Handle paginated response
            const items = data.data?.data || data.data || [];
            if (!Array.isArray(items)) return [];
            
            return items.map(item => ({
                type: 'Meeting',
                name: item.title,
                email: item.customer?.name,
                url: `/meetings?search=${encodeURIComponent(query)}`,
                indexUrl: `/meetings`,
                icon: 'fa-calendar',
                color: 'primary'
            }));
        } catch (e) {
            console.error('Meeting search error:', e);
            return [];
        }
    }

    async function searchTasks(query) {
        try {
            const response = await fetch(`/api/tasks?search=${encodeURIComponent(query)}&limit=5`, {
                headers: getHeaders()
            });
            if (!response.ok) {
                console.warn('Task search failed:', response.status);
                return [];
            }
            const data = await response.json();
            console.log('Task search response:', data);
            
            // Handle paginated response
            const items = data.data?.data || data.data || [];
            if (!Array.isArray(items)) return [];
            
            return items.map(item => ({
                type: 'Task',
                name: item.title,
                email: item.customer?.name,
                url: `/tasks?search=${encodeURIComponent(query)}`,
                indexUrl: `/tasks`,
                icon: 'fa-tasks',
                color: 'secondary'
            }));
        } catch (e) {
            console.error('Task search error:', e);
            return [];
        }
    }

    function renderResults(results) {
        if (!resultsContainer) return;

        const grouped = {};
        results.forEach(result => {
            if (!grouped[result.type]) {
                grouped[result.type] = [];
            }
            grouped[result.type].push(result);
        });

        let html = '';
        Object.entries(grouped).forEach(([type, items]) => {
            const indexUrl = items[0]?.indexUrl;
            html += `<div class="search-result-group">
                <div class="search-result-header px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <small class="text-muted fw-bold">${type}</small>
                    ${indexUrl ? `<a href="${indexUrl}" class="text-muted small text-decoration-none" style="font-size: 0.75rem;">View All</a>` : ''}
                </div>`;
            
            items.slice(0, 3).forEach(item => {
                html += `
                    <a href="${item.url}" class="search-result-item d-flex align-items-center px-3 py-2 text-decoration-none text-dark" style="border-bottom: 1px solid #f1f5f9;">
                        <i class="fa-solid ${item.icon} text-${item.color} me-2" style="width: 20px;"></i>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-500 small">${escapeHtml(item.name)}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">${escapeHtml(item.email || '')}</div>
                        </div>
                    </a>`;
            });

            if (items.length > 3) {
                html += `<a href="${indexUrl}" class="px-3 py-2 text-center text-muted small text-decoration-none d-block" style="border-bottom: 1px solid #f1f5f9;">
                    +${items.length - 3} more ${type.toLowerCase()}s
                </a>`;
            }

            html += '</div>';
        });

        resultsContainer.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
