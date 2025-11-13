-- TransKwanza Web App - SQLite Database Schema
-- Versão standalone sem WordPress

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    country_code VARCHAR(3),
    document_type VARCHAR(50),
    document_number VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 5.00,
    total_transactions INTEGER DEFAULT 0,
    successful_transactions INTEGER DEFAULT 0,
    account_status VARCHAR(20) DEFAULT 'active',
    kyc_status VARCHAR(20) DEFAULT 'not_started',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Países
CREATE TABLE IF NOT EXISTS countries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    country_code VARCHAR(3) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    currency_name VARCHAR(50) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    flag_emoji VARCHAR(10),
    payment_method VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'active'
);

-- Tabela de Taxas de Câmbio
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(20,10) NOT NULL,
    rate_with_fee DECIMAL(20,10) NOT NULL,
    fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL
);

-- Tabela de Propostas
CREATE TABLE IF NOT EXISTS proposals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    from_country VARCHAR(3) NOT NULL,
    from_currency VARCHAR(3) NOT NULL,
    to_country VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    send_amount DECIMAL(15,2) NOT NULL,
    receive_amount DECIMAL(15,2) NOT NULL,
    exchange_rate DECIMAL(20,10) NOT NULL,
    fee_amount DECIMAL(15,2) NOT NULL,
    recipient_name VARCHAR(255),
    recipient_phone VARCHAR(20),
    recipient_details TEXT,
    status VARCHAR(20) DEFAULT 'open',
    matched_proposal_id INTEGER,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Transações
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    proposal_a_id INTEGER NOT NULL,
    proposal_b_id INTEGER NOT NULL,
    user_a_id INTEGER NOT NULL,
    user_b_id INTEGER NOT NULL,
    amount_a DECIMAL(15,2) NOT NULL,
    currency_a VARCHAR(3) NOT NULL,
    amount_b DECIMAL(15,2) NOT NULL,
    currency_b VARCHAR(3) NOT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    user_a_paid_at DATETIME,
    user_b_paid_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_a_id) REFERENCES users(id),
    FOREIGN KEY (user_b_id) REFERENCES users(id)
);

-- Tabela de Notificações
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Sessões
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Inserir países padrão
INSERT OR IGNORE INTO countries (country_code, name, currency_code, currency_name, currency_symbol, flag_emoji, payment_method) VALUES
('BRA', 'Brasil', 'BRL', 'Real', 'R$', '🇧🇷', 'PIX'),
('AGO', 'Angola', 'AOA', 'Kwanza', 'Kz', '🇦🇴', 'Multicaixa Express'),
('PRT', 'Portugal', 'EUR', 'Euro', '€', '🇵🇹', 'SEPA/MB Way'),
('USA', 'Estados Unidos', 'USD', 'Dólar', '$', '🇺🇸', 'Zelle/ACH'),
('CUB', 'Cuba', 'CUP', 'Peso Cubano', '₱', '🇨🇺', 'Transfermóvil'),
('RUS', 'Rússia', 'RUB', 'Rublo', '₽', '🇷🇺', 'SBP'),
('ZAF', 'África do Sul', 'ZAR', 'Rand', 'R', '🇿🇦', 'EFT'),
('NAM', 'Namíbia', 'NAD', 'Dólar Namibiano', 'N$', '🇳🇦', 'EFT'),
('MOZ', 'Moçambique', 'MZN', 'Metical', 'MT', '🇲🇿', 'M-Pesa');

-- Criar índices para performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_proposals_user ON proposals(user_id);
CREATE INDEX IF NOT EXISTS idx_proposals_status ON proposals(status);
CREATE INDEX IF NOT EXISTS idx_transactions_users ON transactions(user_a_id, user_b_id);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_exchange_rates ON exchange_rates(from_currency, to_currency);
