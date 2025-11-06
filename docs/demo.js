/**
 * TransKwanza Demo - JavaScript
 * Versão estática para demonstração (sem backend)
 */

// Mock exchange rates (valores ilustrativos)
const mockRates = {
    'BRL-EUR': 0.1811,
    'BRL-USD': 0.1950,
    'BRL-AOA': 170.50,
    'BRL-CUP': 4.68,
    'BRL-RUB': 18.25,
    'BRL-ZAR': 3.52,
    'BRL-NAD': 3.52,
    'BRL-MZN': 12.45,
    'EUR-BRL': 5.52,
    'EUR-USD': 1.08,
    'EUR-AOA': 941.30,
    'EUR-CUP': 25.84,
    'EUR-RUB': 100.74,
    'EUR-ZAR': 19.43,
    'EUR-NAD': 19.43,
    'EUR-MZN': 68.72,
    'USD-BRL': 5.13,
    'USD-EUR': 0.93,
    'USD-AOA': 874.50,
    'USD-CUP': 24.00,
    'USD-RUB': 93.57,
    'USD-ZAR': 18.05,
    'USD-NAD': 18.05,
    'USD-MZN': 63.85
};

const currencies = {
    'BRA': { code: 'BRL', name: 'Brasil', flag: '🇧🇷', symbol: 'R$' },
    'AGO': { code: 'AOA', name: 'Angola', flag: '🇦🇴', symbol: 'Kz' },
    'PRT': { code: 'EUR', name: 'Portugal', flag: '🇵🇹', symbol: '€' },
    'USA': { code: 'USD', name: 'Estados Unidos', flag: '🇺🇸', symbol: '$' },
    'CUB': { code: 'CUP', name: 'Cuba', flag: '🇨🇺', symbol: '₱' },
    'RUS': { code: 'RUB', name: 'Rússia', flag: '🇷🇺', symbol: '₽' },
    'ZAF': { code: 'ZAR', name: 'África do Sul', flag: '🇿🇦', symbol: 'R' },
    'NAM': { code: 'NAD', name: 'Namíbia', flag: '🇳🇦', symbol: 'N$' },
    'MOZ': { code: 'MZN', name: 'Moçambique', flag: '🇲🇿', symbol: 'MT' }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    attachEventListeners();
    calculateConversion();
});

function attachEventListeners() {
    const fromAmount = document.getElementById('tk-from-amount');
    const fromCountry = document.getElementById('tk-from-country');
    const toCountry = document.getElementById('tk-to-country');

    if (fromAmount) {
        fromAmount.addEventListener('input', calculateConversion);
    }
    if (fromCountry) {
        fromCountry.addEventListener('change', () => {
            updateCurrencyInfo('from');
            calculateConversion();
        });
    }
    if (toCountry) {
        toCountry.addEventListener('change', () => {
            updateCurrencyInfo('to');
            calculateConversion();
        });
    }
}

function updateCurrencyInfo(type) {
    const select = document.getElementById(`tk-${type}-country`);
    const countryCode = select.value;
    const currency = currencies[countryCode];

    if (!currency) return;

    const flagEl = document.querySelector(`#tk-calculator-widget .tk-currency-group:${type === 'from' ? 'first-child' : 'last-child'} .tk-flag`);
    const nameEl = document.querySelector(`#tk-calculator-widget .tk-currency-group:${type === 'from' ? 'first-child' : 'last-child'} .tk-currency-name`);

    if (flagEl) flagEl.textContent = currency.flag;
    if (nameEl) nameEl.textContent = `${currency.name} - ${currency.code}`;
}

function calculateConversion() {
    const fromCountry = document.getElementById('tk-from-country')?.value;
    const toCountry = document.getElementById('tk-to-country')?.value;
    const amount = parseFloat(document.getElementById('tk-from-amount')?.value) || 0;

    if (!fromCountry || !toCountry || amount <= 0) {
        document.getElementById('tk-to-amount').value = '';
        document.querySelector('.tk-rate-value').textContent = '-';
        return;
    }

    const fromCurrency = currencies[fromCountry];
    const toCurrency = currencies[toCountry];

    // Same currency
    if (fromCurrency.code === toCurrency.code) {
        document.getElementById('tk-to-amount').value = amount.toFixed(2);
        document.querySelector('.tk-rate-value').textContent = `1 ${fromCurrency.code} = 1 ${toCurrency.code}`;
        return;
    }

    // Get rate
    const rateKey = `${fromCurrency.code}-${toCurrency.code}`;
    const reverseKey = `${toCurrency.code}-${fromCurrency.code}`;

    let rate = mockRates[rateKey];
    if (!rate && mockRates[reverseKey]) {
        rate = 1 / mockRates[reverseKey];
    }

    if (!rate) {
        rate = 1; // Fallback
    }

    // Apply 3% fee
    const rateWithFee = rate * 0.97;
    const convertedAmount = amount * rateWithFee;

    document.getElementById('tk-to-amount').value = convertedAmount.toFixed(2);
    document.querySelector('.tk-rate-value').textContent =
        `1 ${fromCurrency.code} = ${rateWithFee.toFixed(4)} ${toCurrency.code}`;
}

function swapCurrencies() {
    const fromSelect = document.getElementById('tk-from-country');
    const toSelect = document.getElementById('tk-to-country');

    const temp = fromSelect.value;
    fromSelect.value = toSelect.value;
    toSelect.value = temp;

    updateCurrencyInfo('from');
    updateCurrencyInfo('to');
    calculateConversion();
}

function showDemoMessage() {
    const toast = document.getElementById('demo-toast');
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 5000);
}

// Add show class to toast
const style = document.createElement('style');
style.textContent = `
    #demo-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #1e1e1e;
        color: white;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #ff9800;
        box-shadow: 0 8px 16px rgba(0,0,0,0.5);
        max-width: 400px;
        transform: translateX(500px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 10000;
    }

    #demo-toast.show {
        transform: translateX(0);
        opacity: 1;
    }

    .tk-demo-badge {
        background: rgba(255, 152, 0, 0.1);
        border: 2px solid #ff9800;
        color: #ff9800;
        padding: 15px 20px;
        border-radius: 8px;
        margin-top: 30px;
        font-weight: 600;
        text-align: center;
    }

    @media (max-width: 768px) {
        #demo-toast {
            bottom: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }

        .tk-nav-menu {
            flex-direction: column;
            gap: 10px;
        }

        .tk-nav-menu a,
        .tk-nav-menu button {
            width: 100%;
            text-align: center;
        }
    }
`;
document.head.appendChild(style);
