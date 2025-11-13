/**
 * Dashboard Page JavaScript
 */

// Require authentication
if (!requireAuth()) {
    throw new Error('Not authenticated');
}

let currentUser = null;
let countries = [];
let currencies = [];

// Initialize dashboard
async function initDashboard() {
    try {
        // Load user data
        const userData = await API.me();
        currentUser = userData.user;
        displayUserInfo();

        // Load countries
        const countriesData = await API.getCountries();
        countries = countriesData.countries;
        currencies = countriesData.by_currency;

        // Populate selects
        populateCountrySelects();
        populateCurrencyFilters();

        // Load initial data
        loadAvailableProposals();
        loadNotifications();

        // Setup event listeners
        setupEventListeners();

    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
        if (error.message === 'Unauthorized') {
            removeAuthToken();
            window.location.href = 'login.html';
        }
    }
}

// Display user info
function displayUserInfo() {
    document.getElementById('user-name').textContent = currentUser.full_name;
    document.getElementById('user-transactions').textContent = currentUser.total_transactions || 0;
    document.getElementById('user-rating').textContent = parseFloat(currentUser.rating || 5).toFixed(2);
    document.getElementById('user-status').textContent = currentUser.account_status || 'Active';
}

// Populate country selects
function populateCountrySelects() {
    const fromCountry = document.getElementById('from-country');
    const toCountry = document.getElementById('to-country');

    countries.forEach(country => {
        const option1 = document.createElement('option');
        option1.value = country.country_code;
        option1.textContent = `${country.flag_emoji} ${country.name}`;
        fromCountry.appendChild(option1);

        const option2 = option1.cloneNode(true);
        toCountry.appendChild(option2);
    });

    // Update currency select when country changes
    fromCountry.addEventListener('change', (e) => {
        updateCurrencySelect('from-currency', e.target.value);
    });

    toCountry.addEventListener('change', (e) => {
        updateCurrencySelect('to-currency', e.target.value);
    });
}

// Update currency select based on country
function updateCurrencySelect(selectId, countryCode) {
    const select = document.getElementById(selectId);
    const country = countries.find(c => c.country_code === countryCode);

    if (country) {
        select.innerHTML = `<option value="${country.currency_code}">${country.currency_code} - ${country.currency_name}</option>`;
        select.value = country.currency_code;
        select.disabled = true;
    } else {
        select.innerHTML = '<option value="">Selecione a moeda</option>';
        select.disabled = false;
    }

    // Trigger calculation
    if (document.getElementById('from-amount').value) {
        calculateProposal();
    }
}

// Populate currency filters
function populateCurrencyFilters() {
    const fromFilter = document.getElementById('filter-from-currency');
    const toFilter = document.getElementById('filter-to-currency');

    currencies.forEach(currency => {
        const option1 = document.createElement('option');
        option1.value = currency.code;
        option1.textContent = currency.code;
        fromFilter.appendChild(option1);

        const option2 = option1.cloneNode(true);
        toFilter.appendChild(option2);
    });
}

// Setup event listeners
function setupEventListeners() {
    // Logout
    document.getElementById('btn-logout').addEventListener('click', async () => {
        try {
            await API.logout();
        } catch (error) {
            console.error('Logout error:', error);
        }
        removeAuthToken();
        window.location.href = 'login.html';
    });

    // Notifications
    document.getElementById('btn-notifications').addEventListener('click', openNotificationsModal);

    // Tab switching (desktop)
    document.querySelectorAll('.tk-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            switchTab(btn.dataset.tab);
        });
    });

    // Tab switching (mobile)
    document.getElementById('tk-mobile-tab-select').addEventListener('change', (e) => {
        switchTab(e.target.value);
    });

    // Filters
    document.getElementById('btn-apply-filters').addEventListener('click', loadAvailableProposals);

    // Proposal form
    document.getElementById('proposal-form').addEventListener('submit', createProposal);

    // Calculator in proposal form
    document.getElementById('from-amount').addEventListener('input', debounce(calculateProposal, 500));
    document.getElementById('from-currency').addEventListener('change', calculateProposal);
    document.getElementById('to-currency').addEventListener('change', calculateProposal);
}

// Switch tab
function switchTab(tabName) {
    // Update desktop tabs
    document.querySelectorAll('.tk-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });

    // Update mobile select
    document.getElementById('tk-mobile-tab-select').value = tabName;

    // Update content
    document.querySelectorAll('.tk-tab-content').forEach(content => {
        content.classList.toggle('active', content.id === `tk-tab-${tabName}`);
    });

    // Load data for the tab
    switch (tabName) {
        case 'proposals-available':
            loadAvailableProposals();
            break;
        case 'my-proposals':
            loadMyProposals();
            break;
        case 'my-transactions':
            loadMyTransactions();
            break;
    }
}

// Calculate proposal
async function calculateProposal() {
    const from = document.getElementById('from-currency').value;
    const to = document.getElementById('to-currency').value;
    const amount = parseFloat(document.getElementById('from-amount').value);
    const toAmount = document.getElementById('to-amount');
    const infoDiv = document.getElementById('proposal-calculator-info');

    if (!from || !to || !amount || amount <= 0) {
        toAmount.value = '';
        infoDiv.style.display = 'none';
        return;
    }

    try {
        const data = await API.convert({ from, to, amount });
        toAmount.value = data.converted_amount.toFixed(2);

        document.getElementById('proposal-rate').textContent =
            `1 ${from} = ${data.exchange_rate.toFixed(4)} ${to}`;
        document.getElementById('proposal-fee').textContent =
            `${data.from_symbol} ${data.fee_amount.toFixed(2)}`;

        infoDiv.style.display = 'block';
    } catch (error) {
        console.error('Calculation error:', error);
        toAmount.value = '';
        infoDiv.style.display = 'none';
    }
}

// Create proposal
async function createProposal(e) {
    e.preventDefault();

    const errorDiv = document.getElementById('proposal-error');
    const successDiv = document.getElementById('proposal-success');
    const submitBtn = e.target.querySelector('button[type="submit"]');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Criando...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    // Remove empty optional fields
    Object.keys(data).forEach(key => {
        if (!data[key]) delete data[key];
    });

    try {
        const response = await API.createProposal(data);

        if (response.success) {
            successDiv.textContent = 'Proposta criada com sucesso!';
            successDiv.style.display = 'block';

            // Reset form
            e.target.reset();
            document.getElementById('to-amount').value = '';
            document.getElementById('proposal-calculator-info').style.display = 'none';

            // Switch to my proposals tab
            setTimeout(() => {
                switchTab('my-proposals');
            }, 1500);
        }
    } catch (error) {
        errorDiv.textContent = error.message || 'Erro ao criar proposta. Tente novamente.';
        errorDiv.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Criar Proposta';
    }
}

// Load available proposals
async function loadAvailableProposals() {
    const container = document.getElementById('available-proposals-list');
    container.innerHTML = '<div class="tk-loading">Carregando propostas</div>';

    try {
        const filters = {
            from_currency: document.getElementById('filter-from-currency').value,
            to_currency: document.getElementById('filter-to-currency').value
        };

        // Remove empty filters
        Object.keys(filters).forEach(key => {
            if (!filters[key]) delete filters[key];
        });

        const data = await API.getProposals(filters);

        if (data.proposals.length === 0) {
            container.innerHTML = `
                <div class="tk-empty-state">
                    <div class="tk-empty-state-icon">📭</div>
                    <p>Nenhuma proposta disponível no momento</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        data.proposals.forEach(proposal => {
            container.appendChild(createProposalCard(proposal, false));
        });
    } catch (error) {
        console.error('Failed to load proposals:', error);
        container.innerHTML = '<div class="tk-error-message">Erro ao carregar propostas</div>';
    }
}

// Load my proposals
async function loadMyProposals() {
    const container = document.getElementById('my-proposals-list');
    container.innerHTML = '<div class="tk-loading">Carregando suas propostas</div>';

    try {
        const data = await API.getProposals({ my: '1' });

        if (data.proposals.length === 0) {
            container.innerHTML = `
                <div class="tk-empty-state">
                    <div class="tk-empty-state-icon">📝</div>
                    <p>Você ainda não criou nenhuma proposta</p>
                    <button class="tk-btn tk-btn-primary" onclick="switchTab('calculator')">Criar Proposta</button>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        data.proposals.forEach(proposal => {
            container.appendChild(createProposalCard(proposal, true));
        });
    } catch (error) {
        console.error('Failed to load my proposals:', error);
        container.innerHTML = '<div class="tk-error-message">Erro ao carregar suas propostas</div>';
    }
}

// Create proposal card
function createProposalCard(proposal, isOwn) {
    const card = document.createElement('div');
    card.className = 'tk-proposal-card';

    const statusClass = `tk-status-${proposal.status}`;

    card.innerHTML = `
        <div class="tk-proposal-header">
            <div class="tk-proposal-route">
                <span>${proposal.from_symbol} ${parseFloat(proposal.send_amount).toFixed(2)}</span>
                <span>→</span>
                <span>${proposal.to_symbol} ${parseFloat(proposal.receive_amount).toFixed(2)}</span>
            </div>
            <span class="tk-status-badge ${statusClass}">${proposal.status}</span>
        </div>

        <div class="tk-proposal-details">
            <div class="tk-detail">
                <span class="tk-detail-label">De</span>
                <span class="tk-detail-value">${proposal.from_country} (${proposal.from_currency})</span>
            </div>
            <div class="tk-detail">
                <span class="tk-detail-label">Para</span>
                <span class="tk-detail-value">${proposal.to_country} (${proposal.to_currency})</span>
            </div>
            <div class="tk-detail">
                <span class="tk-detail-label">Taxa</span>
                <span class="tk-detail-value">1 ${proposal.from_currency} = ${parseFloat(proposal.exchange_rate).toFixed(4)} ${proposal.to_currency}</span>
            </div>
            <div class="tk-detail">
                <span class="tk-detail-label">Usuário</span>
                <span class="tk-detail-value">${proposal.user_name} ⭐ ${parseFloat(proposal.user_rating).toFixed(2)}</span>
            </div>
        </div>

        <div class="tk-proposal-actions" id="actions-${proposal.id}">
            ${isOwn && proposal.status === 'open' ?
                `<button class="tk-btn tk-btn-danger tk-btn-sm" onclick="cancelProposal(${proposal.id})">Cancelar</button>` :
                !isOwn && proposal.status === 'open' ?
                `<button class="tk-btn tk-btn-primary tk-btn-sm" onclick="acceptProposal(${proposal.id})">Aceitar Proposta</button>` :
                ''
            }
        </div>
    `;

    return card;
}

// Accept proposal
async function acceptProposal(proposalId) {
    if (!confirm('Deseja aceitar esta proposta?')) return;

    try {
        const response = await API.acceptProposal(proposalId);
        if (response.success) {
            alert(`Match realizado! Código da transação: ${response.transaction_code}`);
            switchTab('my-transactions');
        }
    } catch (error) {
        alert(error.message || 'Erro ao aceitar proposta');
    }
}

// Cancel proposal
async function cancelProposal(proposalId) {
    if (!confirm('Deseja cancelar esta proposta?')) return;

    try {
        await API.cancelProposal(proposalId);
        loadMyProposals();
    } catch (error) {
        alert(error.message || 'Erro ao cancelar proposta');
    }
}

// Load my transactions
async function loadMyTransactions() {
    const container = document.getElementById('my-transactions-list');
    container.innerHTML = '<div class="tk-loading">Carregando transações</div>';

    try {
        const data = await API.getTransactions();

        if (data.transactions.length === 0) {
            container.innerHTML = `
                <div class="tk-empty-state">
                    <div class="tk-empty-state-icon">💸</div>
                    <p>Você ainda não tem transações</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        data.transactions.forEach(transaction => {
            container.appendChild(createTransactionCard(transaction));
        });
    } catch (error) {
        console.error('Failed to load transactions:', error);
        container.innerHTML = '<div class="tk-error-message">Erro ao carregar transações</div>';
    }
}

// Create transaction card
function createTransactionCard(transaction) {
    const card = document.createElement('div');
    card.className = 'tk-transaction-card';

    const statusClass = `tk-status-${transaction.status}`;

    card.innerHTML = `
        <div class="tk-transaction-header">
            <div>
                <strong>Transação ${transaction.transaction_code}</strong>
                <p style="color: var(--tk-text-secondary); font-size: 0.875rem;">
                    Parceiro: ${transaction.partner_name} ⭐ ${parseFloat(transaction.partner_rating).toFixed(2)}
                </p>
            </div>
            <span class="tk-status-badge ${statusClass}">${transaction.status}</span>
        </div>

        <div class="tk-proposal-details">
            <div class="tk-detail">
                <span class="tk-detail-label">Seu valor</span>
                <span class="tk-detail-value">${transaction.my_currency} ${parseFloat(transaction.my_amount).toFixed(2)}</span>
            </div>
            <div class="tk-detail">
                <span class="tk-detail-label">Você pagou?</span>
                <span class="tk-detail-value">${transaction.i_paid ? '✅ Sim' : '❌ Não'}</span>
            </div>
            <div class="tk-detail">
                <span class="tk-detail-label">Parceiro pagou?</span>
                <span class="tk-detail-value">${transaction.partner_paid ? '✅ Sim' : '❌ Não'}</span>
            </div>
            <div class="tk-detail">
                <span class="tk-detail-label">Data</span>
                <span class="tk-detail-value">${new Date(transaction.created_at).toLocaleDateString('pt-BR')}</span>
            </div>
        </div>

        <div class="tk-proposal-actions">
            ${!transaction.i_paid && transaction.status === 'pending' ?
                `<button class="tk-btn tk-btn-primary tk-btn-sm" onclick="confirmPayment(${transaction.id})">Confirmar Pagamento</button>` :
                ''
            }
        </div>
    `;

    return card;
}

// Confirm payment
async function confirmPayment(transactionId) {
    if (!confirm('Confirma que você realizou o pagamento?')) return;

    try {
        const response = await API.confirmPayment(transactionId);
        if (response.success) {
            alert(response.both_paid ?
                'Transação concluída com sucesso!' :
                'Pagamento confirmado! Aguardando confirmação do parceiro.');
            loadMyTransactions();
        }
    } catch (error) {
        alert(error.message || 'Erro ao confirmar pagamento');
    }
}

// Load notifications
async function loadNotifications() {
    try {
        const data = await API.getNotifications({ limit: 5, unread: '1' });

        const badge = document.getElementById('notification-badge');
        if (data.unread_count > 0) {
            badge.textContent = data.unread_count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

// Open notifications modal
async function openNotificationsModal() {
    const modal = document.getElementById('notifications-modal');
    const list = document.getElementById('notifications-list');

    modal.style.display = 'flex';
    list.innerHTML = '<div class="tk-loading">Carregando notificações</div>';

    try {
        const data = await API.getNotifications({ limit: 20 });

        if (data.notifications.length === 0) {
            list.innerHTML = '<div class="tk-empty-state"><p>Nenhuma notificação</p></div>';
            return;
        }

        list.innerHTML = '';
        data.notifications.forEach(notif => {
            const item = document.createElement('div');
            item.className = 'tk-proposal-card';
            item.style.opacity = notif.is_read ? '0.6' : '1';
            item.innerHTML = `
                <h4>${notif.title}</h4>
                <p style="color: var(--tk-text-secondary); margin-top: 0.5rem;">${notif.message}</p>
                <p style="color: var(--tk-text-muted); font-size: 0.875rem; margin-top: 0.5rem;">
                    ${new Date(notif.created_at).toLocaleString('pt-BR')}
                </p>
            `;
            list.appendChild(item);
        });

        // Mark all as read
        await API.markAllNotificationsRead();
        loadNotifications();
    } catch (error) {
        console.error('Failed to load notifications:', error);
        list.innerHTML = '<div class="tk-error-message">Erro ao carregar notificações</div>';
    }
}

// Close notifications modal
function closeNotificationsModal() {
    document.getElementById('notifications-modal').style.display = 'none';
}

// Utility: debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Make functions global for onclick handlers
window.switchTab = switchTab;
window.acceptProposal = acceptProposal;
window.cancelProposal = cancelProposal;
window.confirmPayment = confirmPayment;
window.closeNotificationsModal = closeNotificationsModal;

// Initialize on page load
initDashboard();
