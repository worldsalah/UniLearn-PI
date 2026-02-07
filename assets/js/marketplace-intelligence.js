// Marketplace Intelligence APIs - Frontend Integration
class MarketplaceIntelligence {
    constructor() {
        this.baseURL = '/api';
        this.init();
    }

    init() {
        this.setupAutocomplete();
        this.setupRecommendations();
        this.setupPriceIntelligence();
        this.setupReputationBadges();
    }

    // ==================== DISCOVERY & RECOMMENDATION API ====================

    setupAutocomplete() {
        const searchInputs = document.querySelectorAll('[data-autocomplete]');
        
        searchInputs.forEach(input => {
            let timeout;
            
            input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    this.hideAutocompleteResults(input);
                    return;
                }
                
                timeout = setTimeout(() => {
                    this.fetchAutocompleteSuggestions(query, input);
                }, 300);
            });
            
            input.addEventListener('focus', (e) => {
                if (e.target.value.trim().length >= 2) {
                    this.fetchAutocompleteSuggestions(e.target.value.trim(), e.target);
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.autocomplete-container')) {
                    this.hideAutocompleteResults();
                }
            });
        });
    }

    async fetchAutocompleteSuggestions(query, input) {
        try {
            const type = input.dataset.type || 'all';
            const response = await fetch(`${this.baseURL}/discovery/autocomplete?q=${encodeURIComponent(query)}&type=${type}`);
            const data = await response.json();
            
            if (data.success) {
                this.showAutocompleteResults(data.data.suggestions, input);
            }
        } catch (error) {
            console.error('Autocomplete error:', error);
        }
    }

    showAutocompleteResults(suggestions, input) {
        this.hideAutocompleteResults();
        
        if (suggestions.length === 0) return;
        
        const container = document.createElement('div');
        container.className = 'autocomplete-container position-absolute w-100 bg-white border rounded shadow-lg mt-1';
        container.style.zIndex = '1000';
        container.style.maxHeight = '300px';
        container.style.overflowY = 'auto';
        
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item p-3 border-bottom hover:bg-gray-50 cursor-pointer';
            
            const icon = this.getSuggestionIcon(suggestion.type);
            const rating = suggestion.rating ? `<span class="text-warning">★ ${suggestion.rating}</span>` : '';
            const price = suggestion.price ? `<span class="text-primary fw-bold">$${suggestion.price}</span>` : '';
            const budget = suggestion.budget ? `<span class="text-success fw-bold">$${suggestion.budget}</span>` : '';
            
            item.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="me-3">${icon}</div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${suggestion.title}</div>
                        <div class="small text-muted">
                            ${suggestion.category} • ${price || budget || ''}
                            ${rating ? ` • ${rating}` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            item.addEventListener('click', () => {
                window.location.href = suggestion.url;
            });
            
            container.appendChild(item);
        });
        
        // Position the container
        const rect = input.getBoundingClientRect();
        container.style.top = `${rect.bottom + window.scrollY}px`;
        container.style.left = `${rect.left + window.scrollX}px`;
        container.style.width = `${rect.width}px`;
        
        document.body.appendChild(container);
    }

    hideAutocompleteResults(input = null) {
        const containers = document.querySelectorAll('.autocomplete-container');
        containers.forEach(container => container.remove());
    }

    getSuggestionIcon(type) {
        const icons = {
            'product': '<i class="fas fa-box text-primary"></i>',
            'job': '<i class="fas fa-briefcase text-warning"></i>'
        };
        return icons[type] || '<i class="fas fa-search text-muted"></i>';
    }

    setupRecommendations() {
        this.loadPersonalizedRecommendations();
        this.loadTrendingServices();
    }

    async loadPersonalizedRecommendations() {
        try {
            const response = await fetch(`${this.baseURL}/discovery/recommendations?limit=8`);
            const data = await response.json();
            
            if (data.success) {
                this.renderRecommendations(data.data.recommendations, 'personalized');
            }
        } catch (error) {
            console.error('Recommendations error:', error);
        }
    }

    async loadTrendingServices() {
        try {
            const response = await fetch(`${this.baseURL}/discovery/trending?limit=8`);
            const data = await response.json();
            
            if (data.success) {
                this.renderRecommendations(data.data.trending, 'trending');
            }
        } catch (error) {
            console.error('Trending error:', error);
        }
    }

    renderRecommendations(recommendations, type) {
        const container = document.querySelector(`[data-recommendations="${type}"]`);
        if (!container) return;
        
        container.innerHTML = '';
        
        recommendations.forEach(item => {
            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-3 mb-4';
            
            const badge = item.reason ? `<span class="badge bg-info">${item.reason}</span>` : '';
            const trendScore = item.trend_score ? `<div class="small text-success">🔥 Trending score: ${item.trend_score}</div>` : '';
            
            card.innerHTML = `
                <div class="card h-100 shadow-sm hover-lift">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-secondary">${item.category}</span>
                            ${badge}
                        </div>
                        <h6 class="card-title">${item.title}</h6>
                        <div class="text-primary fw-bold mb-2">
                            ${item.price ? `$${item.price}` : item.budget ? `$${item.budget} budget` : ''}
                        </div>
                        ${item.rating ? `<div class="text-warning small mb-2">★ ${item.rating}</div>` : ''}
                        ${trendScore}
                        <a href="${item.url}" class="btn btn-outline-primary btn-sm mt-2">View Details</a>
                    </div>
                </div>
            `;
            
            container.appendChild(card);
        });
    }

    // ==================== SELLER REPUTATION API ====================

    setupReputationBadges() {
        this.loadSellerReputations();
    }

    async loadSellerReputations() {
        const sellerElements = document.querySelectorAll('[data-seller-id]');
        
        sellerElements.forEach(async (element) => {
            const sellerId = element.dataset.sellerId;
            
            try {
                const response = await fetch(`${this.baseURL}/reputation/seller/${sellerId}`);
                const data = await response.json();
                
                if (data.success) {
                    this.renderReputationBadge(element, data.data.reputation);
                }
            } catch (error) {
                console.error('Reputation error:', error);
            }
        });
    }

    renderReputationBadge(element, reputation) {
        const badge = reputation.badge;
        const score = reputation.overall_score;
        
        const badgeHTML = `
            <div class="reputation-badge d-inline-flex align-items-center" style="background: ${badge.color}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <span class="me-1">${badge.icon}</span>
                <span>${badge.name}</span>
                <span class="ms-2">${score}/100</span>
            </div>
            <div class="small text-muted mt-1">
                ${reputation.stats.completed_orders} orders • ${reputation.stats.avg_rating}★
            </div>
        `;
        
        element.innerHTML = badgeHTML;
    }

    // ==================== PRICE INTELLIGENCE API ====================

    setupPriceIntelligence() {
        this.setupPriceAnalysis();
        this.setupPriceCalculator();
    }

    setupPriceAnalysis() {
        const priceElements = document.querySelectorAll('[data-price-analysis]');
        
        priceElements.forEach(async (element) => {
            const productId = element.dataset.priceAnalysis;
            
            try {
                const response = await fetch(`${this.baseURL}/pricing/analyze/${productId}`);
                const data = await response.json();
                
                if (data.success) {
                    this.renderPriceAnalysis(element, data.data.analysis);
                }
            } catch (error) {
                console.error('Price analysis error:', error);
            }
        });
    }

    renderPriceAnalysis(element, analysis) {
        const label = analysis.price_label;
        const position = analysis.market_position;
        
        const analysisHTML = `
            <div class="price-analysis p-3 bg-light rounded">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge" style="background: ${label.color}; color: white;">
                        ${label.icon} ${label.label}
                    </span>
                    <span class="ms-auto small text-muted">
                        Position: ${position.percentile}th percentile
                    </span>
                </div>
                <div class="small text-muted">
                    ${label.description}
                </div>
                ${analysis.recommendations.length > 0 ? `
                    <div class="mt-2">
                        <div class="small fw-semibold">Recommendations:</div>
                        <div class="small text-muted">
                            ${analysis.recommendations[0].description}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        element.innerHTML = analysisHTML;
    }

    setupPriceCalculator() {
        const calculatorForm = document.querySelector('[data-price-calculator]');
        
        if (calculatorForm) {
            calculatorForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.calculateOptimalPrice(calculatorForm);
            });
        }
    }

    async calculateOptimalPrice(form) {
        const formData = new FormData(form);
        const data = {
            category: formData.get('category'),
            target_revenue: parseFloat(formData.get('target_revenue')) || null,
            expected_orders: parseInt(formData.get('expected_orders')) || null,
            costs: parseFloat(formData.get('costs')) || 0
        };
        
        try {
            const response = await fetch(`${this.baseURL}/pricing/calculator`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.renderPricingScenarios(result.data.scenarios, result.data.recommendation);
            }
        } catch (error) {
            console.error('Price calculator error:', error);
        }
    }

    renderPricingScenarios(scenarios, recommendation) {
        const container = document.querySelector('[data-pricing-scenarios]');
        if (!container) return;
        
        let html = '<div class="row">';
        
        scenarios.forEach((scenario, index) => {
            const isRecommended = scenario.name === recommendation.name;
            
            html += `
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card ${isRecommended ? 'border-primary border-2' : ''}">
                        <div class="card-header ${isRecommended ? 'bg-primary text-white' : 'bg-light'}">
                            <div class="fw-semibold">${scenario.name}</div>
                            ${isRecommended ? '<div class="small">Recommended</div>' : ''}
                        </div>
                        <div class="card-body">
                            <div class="h4 text-primary">$${scenario.price}</div>
                            <div class="small text-muted">
                                ${scenario.expected_orders} orders expected
                            </div>
                            <div class="small">
                                Revenue: $${scenario.expected_revenue}
                            </div>
                            <div class="small">
                                Margin: ${scenario.profit_margin}%
                            </div>
                            <div class="badge bg-secondary mt-2">
                                ${scenario.market_position}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
}

// Initialize the marketplace intelligence system
document.addEventListener('DOMContentLoaded', () => {
    new MarketplaceIntelligence();
});

// CSS for the components
const style = document.createElement('style');
style.textContent = `
    .autocomplete-container {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .autocomplete-item:hover {
        background-color: #f8f9fa;
    }
    
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .reputation-badge {
        transition: all 0.2s ease;
    }
    
    .reputation-badge:hover {
        transform: scale(1.05);
    }
    
    .price-analysis {
        border-left: 4px solid #007bff;
    }
`;
document.head.appendChild(style);
