/**
 * Home Page JavaScript
 */

let countries = [];
let currencies = [];

// Load countries and currencies
async function loadCountries() {
    try {
        const data = await API.getCountries();
        countries = data.countries;

        // Get unique currencies
        const currencyMap = new Map();
        data.by_currency.forEach(curr => {
            currencyMap.set(curr.code, {
                code: curr.code,
                name: curr.name,
                symbol: curr.symbol
            });
        });
        currencies = Array.from(currencyMap.values());

        // Populate currency selects
        populateCurrencySelects();

        // Display countries
        displayCountries();
    } catch (error) {
        console.error('Failed to load countries:', error);
    }
}

// Populate currency selects
function populateCurrencySelects() {
    const fromSelect = document.getElementById('calc-from-currency');
    const toSelect = document.getElementById('calc-to-currency');

    currencies.forEach(currency => {
        const option1 = document.createElement('option');
        option1.value = currency.code;
        option1.textContent = `${currency.code} - ${currency.name}`;
        fromSelect.appendChild(option1);

        const option2 = document.createElement('option');
        option2.value = currency.code;
        option2.textContent = `${currency.code} - ${currency.name}`;
        toSelect.appendChild(option2);
    });
}

// Display countries grid
function displayCountries() {
    const grid = document.getElementById('countries-grid');
    grid.innerHTML = '';

    countries.forEach(country => {
        const card = document.createElement('div');
        card.className = 'tk-country-card';
        card.innerHTML = `
            <span class="tk-country-flag">${country.flag_emoji}</span>
            <h4>${country.name}</h4>
            <p class="tk-currency">${country.currency_name} (${country.currency_code})</p>
            <p class="tk-payment">${country.payment_method}</p>
        `;
        grid.appendChild(card);
    });
}

// Calculator functionality
let calculatorTimeout;

function setupCalculator() {
    const fromCurrency = document.getElementById('calc-from-currency');
    const toCurrency = document.getElementById('calc-to-currency');
    const fromAmount = document.getElementById('calc-from-amount');
    const toAmount = document.getElementById('calc-to-amount');
    const infoDiv = document.getElementById('calculator-info');

    async function calculate() {
        const from = fromCurrency.value;
        const to = toCurrency.value;
        const amount = parseFloat(fromAmount.value);

        if (!from || !to || !amount || amount <= 0) {
            toAmount.value = '';
            infoDiv.style.display = 'none';
            return;
        }

        if (from === to) {
            toAmount.value = amount.toFixed(2);
            infoDiv.style.display = 'none';
            return;
        }

        try {
            const data = await API.convert({ from, to, amount });

            toAmount.value = data.converted_amount.toFixed(2);

            // Update info
            const fromCurr = currencies.find(c => c.code === from);
            const toCurr = currencies.find(c => c.code === to);

            document.getElementById('calc-rate').textContent =
                `1 ${from} = ${data.exchange_rate.toFixed(4)} ${to}`;

            document.getElementById('calc-fee').textContent =
                `${fromCurr?.symbol || from} ${data.fee_amount.toFixed(2)}`;

            infoDiv.style.display = 'block';
        } catch (error) {
            console.error('Calculation error:', error);
            toAmount.value = '';
            infoDiv.style.display = 'none';
        }
    }

    // Debounced calculation
    function debouncedCalculate() {
        clearTimeout(calculatorTimeout);
        calculatorTimeout = setTimeout(calculate, 500);
    }

    fromCurrency.addEventListener('change', calculate);
    toCurrency.addEventListener('change', calculate);
    fromAmount.addEventListener('input', debouncedCalculate);
}

// Initialize
loadCountries();
setupCalculator();
