-- TransKwanza Database Schema
-- P2P International Remittance Platform

-- Tabela de Países
CREATE TABLE IF NOT EXISTS tk_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(3) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    name_es VARCHAR(100) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    currency_name VARCHAR(50) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    flag_emoji VARCHAR(10),
    payment_method VARCHAR(100) NOT NULL,
    payment_method_details JSON,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    regulations TEXT,
    locale VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_country_code (country_code),
    INDEX idx_currency_code (currency_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Taxas de Câmbio (Cache)
CREATE TABLE IF NOT EXISTS tk_exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(20, 10) NOT NULL,
    rate_with_fee DECIMAL(20, 10) NOT NULL COMMENT 'Rate with 3% fee applied',
    source VARCHAR(50) DEFAULT 'Google Finance',
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    INDEX idx_currencies (from_currency, to_currency),
    INDEX idx_expires (expires_at),
    INDEX idx_valid (is_valid),
    UNIQUE KEY uk_currency_pair (from_currency, to_currency, fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Usuários (estende wp_users)
CREATE TABLE IF NOT EXISTS tk_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wp_user_id BIGINT(20) UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    country_code VARCHAR(3),
    document_type VARCHAR(50) COMMENT 'CPF, Passport, ID Card, etc',
    document_number VARCHAR(100),
    document_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255),
    rating DECIMAL(3, 2) DEFAULT 5.00,
    total_transactions INT DEFAULT 0,
    successful_transactions INT DEFAULT 0,
    failed_transactions INT DEFAULT 0,
    account_status ENUM('active', 'suspended', 'banned', 'pending_verification') DEFAULT 'pending_verification',
    kyc_status ENUM('not_started', 'pending', 'approved', 'rejected') DEFAULT 'not_started',
    kyc_submitted_at TIMESTAMP NULL,
    kyc_approved_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wp_user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    INDEX idx_wp_user (wp_user_id),
    INDEX idx_country (country_code),
    INDEX idx_status (account_status),
    INDEX idx_kyc (kyc_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Propostas
CREATE TABLE IF NOT EXISTS tk_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_country VARCHAR(3) NOT NULL,
    from_currency VARCHAR(3) NOT NULL,
    to_country VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    send_amount DECIMAL(15, 2) NOT NULL COMMENT 'Amount user wants to send',
    receive_amount DECIMAL(15, 2) NOT NULL COMMENT 'Amount user wants to receive',
    exchange_rate DECIMAL(20, 10) NOT NULL,
    fee_amount DECIMAL(15, 2) NOT NULL,
    recipient_name VARCHAR(255),
    recipient_phone VARCHAR(20),
    recipient_payment_details JSON COMMENT 'Payment details for recipient',
    status ENUM('open', 'matched', 'processing', 'completed', 'cancelled', 'expired') DEFAULT 'open',
    matched_proposal_id INT NULL COMMENT 'ID of the matched proposal',
    expires_at TIMESTAMP NOT NULL COMMENT 'Auto-cancel after 24h',
    can_cancel_at TIMESTAMP NOT NULL COMMENT 'Can cancel after 12h if not matched',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tk_users(id) ON DELETE CASCADE,
    FOREIGN KEY (matched_proposal_id) REFERENCES tk_proposals(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_currencies (from_currency, to_currency),
    INDEX idx_countries (from_country, to_country),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    INDEX idx_matching (from_currency, to_currency, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Transações
CREATE TABLE IF NOT EXISTS tk_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    proposal_a_id INT NOT NULL COMMENT 'First proposal (user A)',
    proposal_b_id INT NOT NULL COMMENT 'Second proposal (user B)',
    user_a_id INT NOT NULL,
    user_b_id INT NOT NULL,
    amount_a DECIMAL(15, 2) NOT NULL COMMENT 'Amount in currency A',
    currency_a VARCHAR(3) NOT NULL,
    amount_b DECIMAL(15, 2) NOT NULL COMMENT 'Amount in currency B',
    currency_b VARCHAR(3) NOT NULL,
    exchange_rate DECIMAL(20, 10) NOT NULL,
    platform_fee_a DECIMAL(15, 2) NOT NULL,
    platform_fee_b DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'awaiting_payment_a', 'awaiting_payment_b', 'both_paid', 'processing', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    user_a_payment_proof VARCHAR(255) NULL,
    user_a_paid_at TIMESTAMP NULL,
    user_b_payment_proof VARCHAR(255) NULL,
    user_b_paid_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_a_id) REFERENCES tk_proposals(id) ON DELETE CASCADE,
    FOREIGN KEY (proposal_b_id) REFERENCES tk_proposals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_a_id) REFERENCES tk_users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_b_id) REFERENCES tk_users(id) ON DELETE CASCADE,
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_users (user_a_id, user_b_id),
    INDEX idx_proposals (proposal_a_id, proposal_b_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Avaliações
CREATE TABLE IF NOT EXISTS tk_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    rated_user_id INT NOT NULL COMMENT 'User being rated',
    rating_user_id INT NOT NULL COMMENT 'User giving the rating',
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES tk_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (rated_user_id) REFERENCES tk_users(id) ON DELETE CASCADE,
    FOREIGN KEY (rating_user_id) REFERENCES tk_users(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_rated_user (rated_user_id),
    UNIQUE KEY uk_rating (transaction_id, rated_user_id, rating_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Notificações
CREATE TABLE IF NOT EXISTS tk_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'match_found, payment_received, transaction_completed, etc',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON COMMENT 'Additional data for the notification',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tk_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Logs de Atividades
CREATE TABLE IF NOT EXISTS tk_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) COMMENT 'proposal, transaction, user, etc',
    entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tk_users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Contas da Plataforma (para recebimento/envio em cada país)
CREATE TABLE IF NOT EXISTS tk_platform_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_code VARCHAR(3) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    account_holder VARCHAR(255) NOT NULL,
    account_details JSON NOT NULL COMMENT 'Account number, routing, PIX key, etc',
    status ENUM('active', 'inactive') DEFAULT 'active',
    balance DECIMAL(15, 2) DEFAULT 0.00,
    last_reconciliation TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (country_code) REFERENCES tk_countries(country_code) ON DELETE CASCADE,
    INDEX idx_country (country_code),
    INDEX idx_currency (currency_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de FAQ Multi-idioma
CREATE TABLE IF NOT EXISTS tk_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_pt TEXT NOT NULL,
    question_en TEXT NOT NULL,
    question_es TEXT NOT NULL,
    answer_pt TEXT NOT NULL,
    answer_en TEXT NOT NULL,
    answer_es TEXT NOT NULL,
    category VARCHAR(50),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_order (display_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Views úteis

-- View de propostas abertas com informações completas
CREATE OR REPLACE VIEW vw_open_proposals AS
SELECT
    p.id,
    p.user_id,
    u.full_name,
    u.rating,
    u.total_transactions,
    p.from_country,
    p.from_currency,
    cf.flag_emoji AS from_flag,
    p.to_country,
    p.to_currency,
    ct.flag_emoji AS to_flag,
    p.send_amount,
    p.receive_amount,
    p.exchange_rate,
    p.fee_amount,
    p.status,
    p.expires_at,
    p.created_at
FROM tk_proposals p
JOIN tk_users u ON p.user_id = u.id
JOIN tk_countries cf ON p.from_country = cf.country_code
JOIN tk_countries ct ON p.to_country = ct.country_code
WHERE p.status = 'open' AND p.expires_at > NOW();

-- View de transações ativas
CREATE OR REPLACE VIEW vw_active_transactions AS
SELECT
    t.id,
    t.transaction_code,
    t.user_a_id,
    ua.full_name AS user_a_name,
    t.user_b_id,
    ub.full_name AS user_b_name,
    t.amount_a,
    t.currency_a,
    t.amount_b,
    t.currency_b,
    t.status,
    t.user_a_paid_at,
    t.user_b_paid_at,
    t.created_at
FROM tk_transactions t
JOIN tk_users ua ON t.user_a_id = ua.id
JOIN tk_users ub ON t.user_b_id = ub.id
WHERE t.status NOT IN ('completed', 'cancelled', 'refunded');
