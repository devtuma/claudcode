<?php
/**
 * TransKwanza - Configuração de Países e Moedas
 *
 * Define todos os países suportados pela plataforma, suas moedas
 * e métodos de pagamento locais
 */

return [
    'BRA' => [
        'name' => 'Brasil',
        'name_en' => 'Brazil',
        'name_es' => 'Brasil',
        'currency_code' => 'BRL',
        'currency_name' => 'Real',
        'currency_symbol' => 'R$',
        'flag' => '🇧🇷',
        'payment_method' => 'PIX',
        'payment_method_details' => [
            'name' => 'PIX',
            'type' => 'instant',
            'max_time' => '5 minutes',
            'fields' => ['pix_key']
        ],
        'status' => 'active',
        'regulations' => 'BACEN compliant',
        'locale' => 'pt_BR'
    ],
    'AGO' => [
        'name' => 'Angola',
        'name_en' => 'Angola',
        'name_es' => 'Angola',
        'currency_code' => 'AOA',
        'currency_name' => 'Kwanza',
        'currency_symbol' => 'Kz',
        'flag' => '🇦🇴',
        'payment_method' => 'Multicaixa Express',
        'payment_method_details' => [
            'name' => 'Multicaixa Express',
            'type' => 'instant',
            'max_time' => '30 minutes',
            'fields' => ['phone_number', 'account_number']
        ],
        'status' => 'active',
        'regulations' => 'BNA compliant',
        'locale' => 'pt_AO'
    ],
    'PRT' => [
        'name' => 'Portugal',
        'name_en' => 'Portugal',
        'name_es' => 'Portugal',
        'currency_code' => 'EUR',
        'currency_name' => 'Euro',
        'currency_symbol' => '€',
        'flag' => '🇵🇹',
        'payment_method' => 'SEPA/MB Way',
        'payment_method_details' => [
            'name' => 'SEPA/MB Way',
            'type' => 'bank_transfer',
            'max_time' => '1 business day',
            'fields' => ['iban', 'phone_number']
        ],
        'status' => 'active',
        'regulations' => 'EU/GDPR compliant',
        'locale' => 'pt_PT'
    ],
    'USA' => [
        'name' => 'Estados Unidos',
        'name_en' => 'United States',
        'name_es' => 'Estados Unidos',
        'currency_code' => 'USD',
        'currency_name' => 'Dólar',
        'currency_symbol' => '$',
        'flag' => '🇺🇸',
        'payment_method' => 'Zelle/ACH',
        'payment_method_details' => [
            'name' => 'Zelle/ACH',
            'type' => 'instant',
            'max_time' => '15 minutes',
            'fields' => ['email', 'phone_number', 'routing_number', 'account_number']
        ],
        'status' => 'active',
        'regulations' => 'FinCEN compliant',
        'locale' => 'en_US'
    ],
    'CUB' => [
        'name' => 'Cuba',
        'name_en' => 'Cuba',
        'name_es' => 'Cuba',
        'currency_code' => 'CUP',
        'currency_name' => 'Peso Cubano',
        'currency_symbol' => '₱',
        'flag' => '🇨🇺',
        'payment_method' => 'Transfermóvil',
        'payment_method_details' => [
            'name' => 'Transfermóvil',
            'type' => 'mobile',
            'max_time' => '10 minutes',
            'fields' => ['phone_number', 'card_number']
        ],
        'status' => 'active',
        'regulations' => 'BCC compliant',
        'locale' => 'es_CU'
    ],
    'RUS' => [
        'name' => 'Rússia',
        'name_en' => 'Russia',
        'name_es' => 'Rusia',
        'currency_code' => 'RUB',
        'currency_name' => 'Rublo',
        'currency_symbol' => '₽',
        'flag' => '🇷🇺',
        'payment_method' => 'SBP',
        'payment_method_details' => [
            'name' => 'Sistema Rápido de Pagamentos',
            'type' => 'instant',
            'max_time' => '5 minutes',
            'fields' => ['phone_number']
        ],
        'status' => 'active',
        'regulations' => 'CBR compliant',
        'locale' => 'ru_RU'
    ],
    'ZAF' => [
        'name' => 'África do Sul',
        'name_en' => 'South Africa',
        'name_es' => 'Sudáfrica',
        'currency_code' => 'ZAR',
        'currency_name' => 'Rand',
        'currency_symbol' => 'R',
        'flag' => '🇿🇦',
        'payment_method' => 'EFT',
        'payment_method_details' => [
            'name' => 'Electronic Funds Transfer',
            'type' => 'bank_transfer',
            'max_time' => '2 hours',
            'fields' => ['account_number', 'bank_code']
        ],
        'status' => 'active',
        'regulations' => 'SARB compliant',
        'locale' => 'en_ZA'
    ],
    'NAM' => [
        'name' => 'Namíbia',
        'name_en' => 'Namibia',
        'name_es' => 'Namibia',
        'currency_code' => 'NAD',
        'currency_name' => 'Dólar Namibiano',
        'currency_symbol' => 'N$',
        'flag' => '🇳🇦',
        'payment_method' => 'EFT',
        'payment_method_details' => [
            'name' => 'Electronic Funds Transfer',
            'type' => 'bank_transfer',
            'max_time' => '2 hours',
            'fields' => ['account_number', 'bank_code']
        ],
        'status' => 'active',
        'regulations' => 'BON compliant',
        'locale' => 'en_NA'
    ],
    'MOZ' => [
        'name' => 'Moçambique',
        'name_en' => 'Mozambique',
        'name_es' => 'Mozambique',
        'currency_code' => 'MZN',
        'currency_name' => 'Metical',
        'currency_symbol' => 'MT',
        'flag' => '🇲🇿',
        'payment_method' => 'M-Pesa',
        'payment_method_details' => [
            'name' => 'M-Pesa',
            'type' => 'mobile',
            'max_time' => '10 minutes',
            'fields' => ['phone_number']
        ],
        'status' => 'active',
        'regulations' => 'BM compliant',
        'locale' => 'pt_MZ'
    ]
];
