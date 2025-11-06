/**
 * TransKwanza - Dashboard
 *
 * Handles user dashboard functionality including proposals, transactions, and matching
 */

class TransKwanzaDashboard {
    constructor() {
        this.currentTab = 'proposals-available';
        this.currentFilters = {};
        this.refreshInterval = null;

        this.init();
    }

    init() {
        this.attachEvents();
        this.loadCurrentTab();
        this.startAutoRefresh();
        this.loadNotifications();
    }

    attachEvents() {
        // Tab navigation
        document.querySelectorAll('.tk-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Mobile tab select
        const mobileTabSelect = document.getElementById('tk-mobile-tab-select');
        if (mobileTabSelect) {
            mobileTabSelect.addEventListener('change', (e) => {
                this.switchTab(e.target.value);
            });
        }

        // Filter buttons
        document.querySelectorAll('.tk-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.applyFilter(e.target.dataset.filter, e.target.dataset.value);
            });
        });

        // Proposal actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.tk-accept-proposal')) {
                const proposalId = e.target.closest('.tk-accept-proposal').dataset.proposalId;
                this.acceptProposal(proposalId);
            }

            if (e.target.closest('.tk-edit-proposal')) {
                const proposalId = e.target.closest('.tk-edit-proposal').dataset.proposalId;
                this.editProposal(proposalId);
            }

            if (e.target.closest('.tk-cancel-proposal')) {
                const proposalId = e.target.closest('.tk-cancel-proposal').dataset.proposalId;
                this.cancelProposal(proposalId);
            }

            if (e.target.closest('.tk-upload-proof')) {
                const transactionId = e.target.closest('.tk-upload-proof').dataset.transactionId;
                this.showUploadProofModal(transactionId);
            }
        });

        // New proposal form
        const newProposalForm = document.getElementById('tk-new-proposal-form');
        if (newProposalForm) {
            newProposalForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitNewProposal(new FormData(e.target));
            });
        }

        // Notification interactions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.tk-notification-item')) {
                const notifId = e.target.closest('.tk-notification-item').dataset.notificationId;
                this.markNotificationAsRead(notifId);
            }
        });
    }

    switchTab(tabName) {
        this.currentTab = tabName;

        // Update active tab button
        document.querySelectorAll('.tk-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        // Update mobile select
        const mobileSelect = document.getElementById('tk-mobile-tab-select');
        if (mobileSelect) {
            mobileSelect.value = tabName;
        }

        // Hide all tab contents
        document.querySelectorAll('.tk-tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Show selected tab
        const selectedTab = document.getElementById(`tk-tab-${tabName}`);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }

        // Load tab data
        this.loadCurrentTab();
    }

    async loadCurrentTab() {
        const loader = this.showLoader();

        try {
            switch (this.currentTab) {
                case 'proposals-available':
                    await this.loadAvailableProposals();
                    break;
                case 'my-proposals':
                    await this.loadMyProposals();
                    break;
                case 'my-transactions':
                    await this.loadMyTransactions();
                    break;
                case 'calculator':
                    // Calculator is static, no need to load
                    break;
            }
        } catch (error) {
            console.error('Error loading tab:', error);
            this.showError('Erro ao carregar dados');
        } finally {
            this.hideLoader(loader);
        }
    }

    async loadAvailableProposals() {
        const response = await fetch(tkAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tk_get_available_proposals',
                filters: JSON.stringify(this.currentFilters),
                nonce: tkAjax.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.renderAvailableProposals(data.data);
        }
    }

    renderAvailableProposals(proposals) {
        const container = document.getElementById('tk-available-proposals-list');
        if (!container) return;

        if (proposals.length === 0) {
            container.innerHTML = `
                <div class="tk-empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h3>Nenhuma proposta disponível</h3>
                    <p>Não há propostas correspondentes aos seus filtros no momento.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = proposals.map(proposal => `
            <div class="tk-proposal-card">
                <div class="tk-proposal-header">
                    <div class="tk-user-info">
                        <div class="tk-user-avatar">${proposal.full_name.charAt(0)}</div>
                        <div>
                            <strong>${proposal.full_name}</strong>
                            <div class="tk-user-stats">
                                <span class="tk-rating">⭐ ${proposal.rating}</span>
                                <span class="tk-transactions">${proposal.total_transactions} transações</span>
                            </div>
                        </div>
                    </div>
                    <span class="tk-proposal-time">${this.formatTimeAgo(proposal.created_at)}</span>
                </div>

                <div class="tk-proposal-body">
                    <div class="tk-currency-flow">
                        <div class="tk-currency-box">
                            <span class="tk-flag">${proposal.from_flag}</span>
                            <div>
                                <strong>${proposal.send_amount.toFixed(2)} ${proposal.from_currency}</strong>
                                <small>${proposal.from_country_name}</small>
                            </div>
                        </div>

                        <div class="tk-arrow">→</div>

                        <div class="tk-currency-box">
                            <span class="tk-flag">${proposal.to_flag}</span>
                            <div>
                                <strong>${proposal.receive_amount.toFixed(2)} ${proposal.to_currency}</strong>
                                <small>${proposal.to_country_name}</small>
                            </div>
                        </div>
                    </div>

                    <div class="tk-proposal-info">
                        <span>Taxa: 1 ${proposal.from_currency} = ${proposal.exchange_rate.toFixed(4)} ${proposal.to_currency}</span>
                    </div>
                </div>

                <div class="tk-proposal-footer">
                    <button class="tk-btn tk-btn-primary tk-accept-proposal"
                            data-proposal-id="${proposal.id}">
                        Aceitar Proposta
                    </button>
                </div>
            </div>
        `).join('');
    }

    async loadMyProposals() {
        const response = await fetch(tkAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tk_get_my_proposals',
                nonce: tkAjax.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.renderMyProposals(data.data);
        }
    }

    renderMyProposals(proposals) {
        const container = document.getElementById('tk-my-proposals-list');
        if (!container) return;

        if (proposals.length === 0) {
            container.innerHTML = `
                <div class="tk-empty-state">
                    <h3>Você ainda não criou propostas</h3>
                    <p>Clique em "Nova Proposta" para criar sua primeira proposta.</p>
                    <button class="tk-btn tk-btn-primary" onclick="document.querySelector('[data-tab=calculator]').click()">
                        Criar Proposta
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = proposals.map(proposal => `
            <div class="tk-proposal-card tk-my-proposal ${proposal.status}">
                <div class="tk-proposal-header">
                    <span class="tk-status-badge tk-status-${proposal.status}">
                        ${this.getStatusLabel(proposal.status)}
                    </span>
                    <span class="tk-proposal-time">${this.formatTimeAgo(proposal.created_at)}</span>
                </div>

                <div class="tk-proposal-body">
                    <div class="tk-currency-flow">
                        <div class="tk-currency-box">
                            <span class="tk-flag">${proposal.from_flag}</span>
                            <div>
                                <strong>${proposal.send_amount.toFixed(2)} ${proposal.from_currency}</strong>
                                <small>${proposal.from_country_name}</small>
                            </div>
                        </div>

                        <div class="tk-arrow">→</div>

                        <div class="tk-currency-box">
                            <span class="tk-flag">${proposal.to_flag}</span>
                            <div>
                                <strong>${proposal.receive_amount.toFixed(2)} ${proposal.to_currency}</strong>
                                <small>${proposal.to_country_name}</small>
                            </div>
                        </div>
                    </div>

                    ${proposal.matched_proposal_id ? `
                        <div class="tk-match-notification">
                            ✅ Proposta pareada! Transação em andamento.
                        </div>
                    ` : ''}
                </div>

                <div class="tk-proposal-footer">
                    ${proposal.status === 'open' ? `
                        <button class="tk-btn tk-btn-secondary tk-edit-proposal"
                                data-proposal-id="${proposal.id}">
                            Editar
                        </button>
                        <button class="tk-btn tk-btn-danger tk-cancel-proposal"
                                data-proposal-id="${proposal.id}">
                            Cancelar
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    async loadMyTransactions() {
        const response = await fetch(tkAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tk_get_my_transactions',
                nonce: tkAjax.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.renderMyTransactions(data.data);
        }
    }

    renderMyTransactions(transactions) {
        const container = document.getElementById('tk-my-transactions-list');
        if (!container) return;

        if (transactions.length === 0) {
            container.innerHTML = `
                <div class="tk-empty-state">
                    <h3>Nenhuma transação ainda</h3>
                    <p>Suas transações aparecerão aqui quando você aceitar ou tiver uma proposta aceita.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = transactions.map(transaction => `
            <div class="tk-transaction-card">
                <div class="tk-transaction-header">
                    <div>
                        <strong>Código: ${transaction.transaction_code}</strong>
                        <span class="tk-status-badge tk-status-${transaction.status}">
                            ${this.getStatusLabel(transaction.status)}
                        </span>
                    </div>
                    <span class="tk-transaction-time">${this.formatTimeAgo(transaction.created_at)}</span>
                </div>

                <div class="tk-transaction-body">
                    <div class="tk-transaction-amounts">
                        <div class="tk-amount-box">
                            <label>Você envia</label>
                            <strong>${transaction.amount_a.toFixed(2)} ${transaction.currency_a}</strong>
                        </div>
                        <div class="tk-amount-box">
                            <label>Você recebe</label>
                            <strong>${transaction.amount_b.toFixed(2)} ${transaction.currency_b}</strong>
                        </div>
                    </div>

                    <div class="tk-transaction-progress">
                        <div class="tk-progress-step ${transaction.user_a_paid_at ? 'completed' : ''}">
                            <span>Pagamento enviado</span>
                            ${transaction.user_a_paid_at ? '✅' : '⏳'}
                        </div>
                        <div class="tk-progress-step ${transaction.user_b_paid_at ? 'completed' : ''}">
                            <span>Pagamento recebido</span>
                            ${transaction.user_b_paid_at ? '✅' : '⏳'}
                        </div>
                        <div class="tk-progress-step ${transaction.completed_at ? 'completed' : ''}">
                            <span>Concluído</span>
                            ${transaction.completed_at ? '✅' : '⏳'}
                        </div>
                    </div>
                </div>

                <div class="tk-transaction-footer">
                    ${!transaction.user_a_paid_at || !transaction.user_b_paid_at ? `
                        <button class="tk-btn tk-btn-primary tk-upload-proof"
                                data-transaction-id="${transaction.id}">
                            Enviar Comprovante
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    async acceptProposal(proposalId) {
        const confirmed = confirm('Deseja aceitar esta proposta e criar uma transação?');
        if (!confirmed) return;

        // Show recipient data modal
        this.showRecipientDataModal(proposalId);
    }

    showRecipientDataModal(proposalId) {
        // Create and show modal for recipient data
        const modal = document.createElement('div');
        modal.className = 'tk-modal';
        modal.innerHTML = `
            <div class="tk-modal-content">
                <div class="tk-modal-header">
                    <h3>Dados do Destinatário</h3>
                    <button class="tk-modal-close">&times;</button>
                </div>
                <form id="tk-recipient-form">
                    <input type="hidden" name="proposal_id" value="${proposalId}">
                    <div class="tk-form-group">
                        <label>Nome completo</label>
                        <input type="text" name="recipient_name" required>
                    </div>
                    <div class="tk-form-group">
                        <label>Telefone</label>
                        <input type="tel" name="recipient_phone" required>
                    </div>
                    <div class="tk-form-group">
                        <label>Dados de pagamento (PIX, conta, etc)</label>
                        <textarea name="recipient_payment_details" required></textarea>
                    </div>
                    <div class="tk-modal-footer">
                        <button type="button" class="tk-btn tk-btn-secondary tk-modal-close">Cancelar</button>
                        <button type="submit" class="tk-btn tk-btn-primary">Confirmar</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.tk-modal-close').addEventListener('click', () => {
            modal.remove();
        });

        modal.querySelector('#tk-recipient-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitAcceptProposal(new FormData(e.target));
            modal.remove();
        });
    }

    async submitAcceptProposal(formData) {
        formData.append('action', 'tk_accept_proposal');
        formData.append('nonce', tkAjax.nonce);

        const response = await fetch(tkAjax.ajaxurl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            this.showSuccess('Proposta aceita! Transação criada.');
            this.switchTab('my-transactions');
        } else {
            this.showError(data.data.message || 'Erro ao aceitar proposta');
        }
    }

    async cancelProposal(proposalId) {
        const confirmed = confirm('Deseja cancelar esta proposta?');
        if (!confirmed) return;

        const response = await fetch(tkAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tk_cancel_proposal',
                proposal_id: proposalId,
                nonce: tkAjax.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.showSuccess('Proposta cancelada');
            this.loadMyProposals();
        } else {
            this.showError(data.data.message || 'Erro ao cancelar proposta');
        }
    }

    async loadNotifications() {
        const response = await fetch(tkAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tk_get_notifications',
                nonce: tkAjax.nonce
            })
        });

        const data = await response.json();

        if (data.success) {
            this.updateNotificationBadge(data.data.unread_count);
        }
    }

    updateNotificationBadge(count) {
        const badge = document.getElementById('tk-notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    startAutoRefresh() {
        // Refresh every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadCurrentTab();
            this.loadNotifications();
        }, 30000);
    }

    getStatusLabel(status) {
        const labels = {
            'open': 'Aberta',
            'matched': 'Pareada',
            'processing': 'Processando',
            'completed': 'Concluída',
            'cancelled': 'Cancelada',
            'expired': 'Expirada',
            'pending': 'Pendente',
            'awaiting_payment_a': 'Aguardando pagamento',
            'awaiting_payment_b': 'Aguardando pagamento',
            'both_paid': 'Pagamentos confirmados'
        };
        return labels[status] || status;
    }

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const seconds = Math.floor((new Date() - date) / 1000);

        if (seconds < 60) return 'agora';
        if (seconds < 3600) return `há ${Math.floor(seconds / 60)} min`;
        if (seconds < 86400) return `há ${Math.floor(seconds / 3600)}h`;
        return `há ${Math.floor(seconds / 86400)}d`;
    }

    showLoader() {
        const loader = document.createElement('div');
        loader.className = 'tk-loader';
        loader.innerHTML = '<div class="tk-spinner"></div>';
        document.body.appendChild(loader);
        return loader;
    }

    hideLoader(loader) {
        if (loader) loader.remove();
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `tk-toast tk-toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tk-dashboard')) {
        window.tkDashboard = new TransKwanzaDashboard();
    }
});
