/**
 * TransKwanza - Multi-Currency Calculator
 *
 * Handles real-time currency conversion with Google Finance API
 */

class TransKwanzaCalculator {
    constructor(elementId) {
        this.element = document.getElementById(elementId);
        this.countries = null;
        this.currentRate = null;
        this.updateTimer = null;

        this.init();
    }

    async init() {
        await this.loadCountries();
        this.render();
        this.attachEvents();
        this.startAutoUpdate();
    }

    async loadCountries() {
        try {
            const response = await fetch(tkAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'tk_get_countries'
                })
            });

            const data = await response.json();
            if (data.success) {
                this.countries = data.data;
            }
        } catch (error) {
            console.error('Error loading countries:', error);
        }
    }

    render() {
        if (!this.countries) return;

        const html = `
            <div class="tk-calculator">
                <div class="tk-calculator-header">
                    <h2>Calculadora de Câmbio</h2>
                    <p class="tk-subtitle">Conversão em tempo real com todos os países</p>
                </div>

                <div class="tk-calculator-body">
                    <!-- From Currency -->
                    <div class="tk-currency-group">
                        <label>De (Você envia)</label>
                        <div class="tk-currency-selector">
                            <select id="tk-from-country" class="tk-country-select">
                                ${this.renderCountryOptions()}
                            </select>
                            <input type="number"
                                   id="tk-from-amount"
                                   class="tk-amount-input"
                                   placeholder="0.00"
                                   min="0"
                                   step="0.01">
                        </div>
                        <div class="tk-currency-info">
                            <span class="tk-flag" id="tk-from-flag"></span>
                            <span class="tk-currency-name" id="tk-from-currency"></span>
                        </div>
                    </div>

                    <!-- Swap Button -->
                    <div class="tk-swap-container">
                        <button type="button" class="tk-swap-btn" id="tk-swap-currencies">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2"/>
                                <path d="M17 14L12 9L7 14" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>

                    <!-- To Currency -->
                    <div class="tk-currency-group">
                        <label>Para (Destinatário recebe)</label>
                        <div class="tk-currency-selector">
                            <select id="tk-to-country" class="tk-country-select">
                                ${this.renderCountryOptions()}
                            </select>
                            <input type="number"
                                   id="tk-to-amount"
                                   class="tk-amount-input"
                                   placeholder="0.00"
                                   readonly>
                        </div>
                        <div class="tk-currency-info">
                            <span class="tk-flag" id="tk-to-flag"></span>
                            <span class="tk-currency-name" id="tk-to-currency"></span>
                        </div>
                    </div>

                    <!-- Exchange Rate Info -->
                    <div class="tk-rate-info" id="tk-rate-info">
                        <div class="tk-rate-display">
                            <span class="tk-rate-label">Taxa de câmbio:</span>
                            <span class="tk-rate-value" id="tk-rate-value">-</span>
                        </div>
                        <div class="tk-fee-info">
                            <span>Taxa da plataforma: 3%</span>
                        </div>
                        <div class="tk-update-time">
                            <small id="tk-update-time">Atualizado agora</small>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="tk-calculator-actions">
                        <button type="button" class="tk-btn tk-btn-primary" id="tk-send-money">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M22 2L11 13" stroke="currentColor" stroke-width="2"/>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Enviar Dinheiro
                        </button>
                        ${tkAjax.isLoggedIn ? '' : '<p class="tk-login-prompt">Faça login para enviar dinheiro</p>'}
                    </div>
                </div>
            </div>
        `;

        this.element.innerHTML = html;
    }

    renderCountryOptions() {
        if (!this.countries) return '';

        return Object.values(this.countries).map(country => `
            <option value="${country.country_code}"
                    data-currency="${country.currency_code}"
                    data-flag="${country.flag_emoji}"
                    data-name="${country.name}">
                ${country.flag_emoji} ${country.name} (${country.currency_code})
            </option>
        `).join('');
    }

    attachEvents() {
        // From amount input
        document.getElementById('tk-from-amount').addEventListener('input', (e) => {
            this.calculateConversion();
        });

        // Country selects
        document.getElementById('tk-from-country').addEventListener('change', (e) => {
            this.updateCurrencyInfo('from');
            this.calculateConversion();
        });

        document.getElementById('tk-to-country').addEventListener('change', (e) => {
            this.updateCurrencyInfo('to');
            this.calculateConversion();
        });

        // Swap button
        document.getElementById('tk-swap-currencies').addEventListener('click', () => {
            this.swapCurrencies();
        });

        // Send money button
        document.getElementById('tk-send-money').addEventListener('click', () => {
            this.handleSendMoney();
        });

        // Initialize currency info
        this.updateCurrencyInfo('from');
        this.updateCurrencyInfo('to');
    }

    updateCurrencyInfo(type) {
        const select = document.getElementById(`tk-${type}-country`);
        const option = select.selectedOptions[0];

        document.getElementById(`tk-${type}-flag`).textContent = option.dataset.flag;
        document.getElementById(`tk-${type}-currency`).textContent =
            `${option.dataset.name} - ${option.dataset.currency}`;
    }

    async calculateConversion() {
        const fromCountry = document.getElementById('tk-from-country').value;
        const toCountry = document.getElementById('tk-to-country').value;
        const amount = parseFloat(document.getElementById('tk-from-amount').value) || 0;

        if (amount <= 0) {
            document.getElementById('tk-to-amount').value = '';
            document.getElementById('tk-rate-value').textContent = '-';
            return;
        }

        const fromCurrency = this.countries[fromCountry].currency_code;
        const toCurrency = this.countries[toCountry].currency_code;

        try {
            const response = await fetch(tkAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'tk_convert_currency',
                    amount: amount,
                    from: fromCurrency,
                    to: toCurrency
                })
            });

            const data = await response.json();

            if (data.success) {
                const conversion = data.data;
                document.getElementById('tk-to-amount').value =
                    conversion.converted_amount.toFixed(2);

                document.getElementById('tk-rate-value').textContent =
                    `1 ${fromCurrency} = ${conversion.exchange_rate.toFixed(4)} ${toCurrency}`;

                const updateTime = new Date(conversion.rate_info.updated_at);
                document.getElementById('tk-update-time').textContent =
                    `Atualizado ${this.formatTimeAgo(updateTime)}`;

                this.currentRate = conversion;
            }
        } catch (error) {
            console.error('Error calculating conversion:', error);
        }
    }

    swapCurrencies() {
        const fromSelect = document.getElementById('tk-from-country');
        const toSelect = document.getElementById('tk-to-country');

        const temp = fromSelect.value;
        fromSelect.value = toSelect.value;
        toSelect.value = temp;

        this.updateCurrencyInfo('from');
        this.updateCurrencyInfo('to');
        this.calculateConversion();
    }

    handleSendMoney() {
        if (!tkAjax.isLoggedIn) {
            window.location.href = tkAjax.loginUrl;
            return;
        }

        const fromCountry = document.getElementById('tk-from-country').value;
        const toCountry = document.getElementById('tk-to-country').value;
        const amount = document.getElementById('tk-from-amount').value;

        if (!amount || amount <= 0) {
            alert('Por favor, insira um valor válido');
            return;
        }

        // Redirect to proposal creation page with pre-filled data
        const params = new URLSearchParams({
            from_country: fromCountry,
            to_country: toCountry,
            amount: amount
        });

        window.location.href = `${tkAjax.dashboardUrl}?tab=new-proposal&${params.toString()}`;
    }

    formatTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);

        if (seconds < 60) return 'agora';
        if (seconds < 3600) return `há ${Math.floor(seconds / 60)} minutos`;
        if (seconds < 86400) return `há ${Math.floor(seconds / 3600)} horas`;
        return `há ${Math.floor(seconds / 86400)} dias`;
    }

    startAutoUpdate() {
        // Auto-update every 5 minutes
        this.updateTimer = setInterval(() => {
            if (document.getElementById('tk-from-amount').value) {
                this.calculateConversion();
            }
        }, 300000);
    }

    destroy() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
    }
}

// Initialize calculator when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const calculatorElement = document.getElementById('tk-calculator-widget');
    if (calculatorElement) {
        window.tkCalculator = new TransKwanzaCalculator('tk-calculator-widget');
    }
});
