# TransKwanza - Plataforma de Remessas Cruzadas P2P Internacional

![TransKwanza Logo](assets/images/logo.png)

**Versão:** 1.0.0
**Autor:** TransKwanza Team
**Licença:** Proprietária

---

## 📋 Visão Geral

TransKwanza é uma plataforma inovadora de remessas internacionais P2P (peer-to-peer) que conecta usuários que desejam enviar dinheiro em direções opostas, eliminando a necessidade de transferências internacionais tradicionais e reduzindo custos.

### Principais Características

- ✅ **9 Países Suportados**: Brasil, Angola, Portugal, EUA, Cuba, Rússia, África do Sul, Namíbia e Moçambique
- 💱 **Câmbio em Tempo Real**: Integração com Google Finance API
- 🤝 **Matching Inteligente**: Algoritmo automático de pareamento de propostas
- 🔒 **Segurança e Privacidade**: Dados protegidos, conformidade GDPR/LGPD
- 📱 **Design Responsivo**: Interface mobile-first com tema escuro
- ⚡ **Taxa Competitiva**: Apenas 3% sobre transações
- 🌍 **Multi-moeda**: Suporte a 9 moedas diferentes

---

## 🚀 Instalação

### Requisitos do Sistema

- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior
- **WordPress**: 5.8 ou superior
- **Servidor Web**: Apache ou Nginx
- **Memória PHP**: Mínimo 256MB (recomendado 512MB)
- **Extensões PHP necessárias**:
  - mysqli
  - curl
  - json
  - mbstring
  - openssl

### Passo 1: Preparar o Ambiente

```bash
# Clone o repositório
git clone https://github.com/your-repo/transkwanza.git

# Navegue até o diretório
cd transkwanza
```

### Passo 2: Configurar WordPress

1. **Instale o WordPress** no seu servidor se ainda não tiver:
   ```bash
   # Download WordPress
   wget https://wordpress.org/latest.tar.gz
   tar -xzf latest.tar.gz
   ```

2. **Configure o banco de dados**:
   - Crie um banco de dados MySQL
   - Anote as credenciais (host, nome do banco, usuário, senha)

3. **Configure wp-config.php**:
   ```php
   define('DB_NAME', 'transkwanza_db');
   define('DB_USER', 'seu_usuario');
   define('DB_PASSWORD', 'sua_senha');
   define('DB_HOST', 'localhost');
   define('DB_CHARSET', 'utf8mb4');
   define('DB_COLLATE', '');
   ```

### Passo 3: Instalar o Tema TransKwanza

1. **Copie os arquivos do tema** para o diretório de temas do WordPress:
   ```bash
   cp -r transkwanza /var/www/html/wp-content/themes/
   ```

2. **Ative o tema**:
   - Acesse o painel do WordPress (wp-admin)
   - Vá em Aparência > Temas
   - Ative o tema "TransKwanza"

### Passo 4: Instalação do Banco de Dados

O banco de dados será criado automaticamente na ativação do tema. Se precisar instalar manualmente:

```bash
# Execute o schema SQL
mysql -u seu_usuario -p transkwanza_db < database/schema.sql
```

### Passo 5: Configurar Páginas

Crie as seguintes páginas no WordPress:

1. **Página Inicial (Home)**:
   - Template: `templates/home.php`
   - Slug: `/` (definir como página inicial)

2. **Dashboard**:
   - Template: `templates/dashboard.php`
   - Slug: `/dashboard`

3. **Páginas Adicionais**:
   - `/paises` - Países Suportados
   - `/suporte` - Suporte
   - `/termos` - Termos de Uso
   - `/privacidade` - Política de Privacidade

### Passo 6: Configurar Cron Jobs

Adicione os seguintes cron jobs ao seu servidor:

```bash
# Editar crontab
crontab -e

# Adicionar linhas:
0 * * * * php /var/www/html/wp-cron.php tk_refresh_exchange_rates
0 * * * * php /var/www/html/wp-cron.php tk_expire_proposals
0 0 * * * php /var/www/html/wp-cron.php tk_clean_notifications
```

Ou use o WP-Cron (já configurado no tema):
```php
// functions.php já inclui:
wp_schedule_event(time(), 'hourly', 'tk_refresh_exchange_rates');
wp_schedule_event(time(), 'hourly', 'tk_expire_proposals');
wp_schedule_event(time(), 'daily', 'tk_clean_notifications');
```

### Passo 7: Configurar Permalinks

1. Vá em **Configurações > Links Permanentes**
2. Selecione **Nome do post** ou **Estrutura personalizada**: `/%postname%/`
3. Clique em **Salvar alterações**

### Passo 8: Configurar Contas Locais

Configure as contas bancárias da plataforma em cada país:

1. Acesse o banco de dados
2. Insira dados na tabela `wp_tk_platform_accounts`:

```sql
INSERT INTO wp_tk_platform_accounts (country_code, currency_code, bank_name, account_holder, account_details, status) VALUES
('BRA', 'BRL', 'Banco do Brasil', 'TransKwanza Ltda', '{"pix_key": "sua-chave-pix", "account": "123456-7"}', 'active'),
('AGO', 'AOA', 'BFA', 'TransKwanza Angola', '{"account": "123456789", "multicaixa": "987654321"}', 'active');
-- Adicionar para todos os países
```

---

## 🔧 Configuração

### Variáveis de Ambiente

Você pode configurar variáveis no `wp-config.php`:

```php
// Taxa da plataforma (padrão: 3%)
define('TK_PLATFORM_FEE', 0.03);

// Tempo de cache de taxas de câmbio (padrão: 1800 segundos / 30 minutos)
define('TK_EXCHANGE_CACHE_TIME', 1800);

// Tempo para expiração de propostas (padrão: 24 horas)
define('TK_PROPOSAL_EXPIRY_HOURS', 24);

// Tempo para cancelamento de propostas (padrão: 12 horas)
define('TK_PROPOSAL_CANCEL_HOURS', 12);

// Email de suporte
define('TK_SUPPORT_EMAIL', 'suporte@transkwanza.com');

// WhatsApp de suporte
define('TK_SUPPORT_WHATSAPP', '+5511934363623');
```

### Integração com Google Finance

O sistema usa web scraping da página pública do Google Finance. Não é necessária API key, mas você pode configurar um User-Agent customizado:

```php
// functions.php ou wp-config.php
define('TK_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
```

### Login Social (Opcional)

Para habilitar login com Google, Facebook, etc.:

1. Instale o plugin **Nextend Social Login**
2. Configure as credenciais OAuth
3. Ative os provedores desejados

---

## 📱 Uso

### Para Usuários

1. **Cadastro**:
   - Acesse `/cadastro`
   - Preencha os dados
   - Verifique o email

2. **Criar Proposta**:
   - Use a calculadora na home
   - Clique em "Enviar Dinheiro"
   - Preencha os dados do destinatário
   - Aguarde matching

3. **Aceitar Proposta**:
   - Visualize propostas disponíveis no dashboard
   - Clique em "Aceitar Proposta"
   - Preencha dados do destinatário
   - Aguarde criação da transação

4. **Completar Transação**:
   - Deposite na conta local da TransKwanza
   - Envie comprovante
   - Aguarde confirmação do parceiro
   - Receba o dinheiro no destino

### Para Administradores

1. **Gerenciar Transações**:
   - Acesse o painel admin
   - Verifique comprovantes
   - Aprove ou rejeite transações

2. **Adicionar Novos Países**:
   ```php
   // Edite config/countries.php
   'NGA' => [
       'name' => 'Nigéria',
       'currency_code' => 'NGN',
       'currency_name' => 'Naira',
       // ...
   ]
   ```

3. **Monitorar Logs**:
   ```sql
   SELECT * FROM wp_tk_activity_logs ORDER BY created_at DESC LIMIT 100;
   ```

---

## 🏗️ Arquitetura

### Estrutura de Diretórios

```
transkwanza/
├── assets/
│   ├── css/
│   │   └── style.css          # Estilos principais (tema escuro)
│   ├── js/
│   │   ├── calculator.js      # Calculadora multi-moeda
│   │   └── dashboard.js       # Funcionalidades do dashboard
│   └── images/
│       └── flags/             # Bandeiras dos países
├── config/
│   └── countries.php          # Configuração de países e moedas
├── database/
│   └── schema.sql             # Schema do banco de dados
├── includes/
│   ├── classes/
│   │   ├── Proposal.php       # Classe de propostas
│   │   └── Transaction.php    # Classe de transações
│   └── services/
│       ├── ExchangeRateService.php  # Serviço de câmbio
│       └── NotificationService.php  # Serviço de notificações
├── templates/
│   ├── home.php               # Template da home
│   └── dashboard.php          # Template do dashboard
├── functions.php              # Funções principais do tema
├── style.css                  # Arquivo de info do tema
└── README.md                  # Esta documentação
```

### Banco de Dados

**Tabelas principais:**

- `wp_tk_countries` - Países e moedas
- `wp_tk_exchange_rates` - Cache de taxas de câmbio
- `wp_tk_users` - Usuários da plataforma
- `wp_tk_proposals` - Propostas de remessa
- `wp_tk_transactions` - Transações
- `wp_tk_notifications` - Notificações
- `wp_tk_activity_logs` - Logs de atividade
- `wp_tk_platform_accounts` - Contas da plataforma
- `wp_tk_ratings` - Avaliações de usuários
- `wp_tk_faq` - FAQ multi-idioma

### Fluxo de Transação

```
1. Usuário A cria proposta: BRL → EUR
2. Usuário B cria proposta: EUR → BRL
3. Sistema faz matching automático
4. Transação criada (status: pending)
5. Usuário A deposita BRL → Status: awaiting_payment_b
6. Usuário B deposita EUR → Status: both_paid
7. Admin verifica comprovantes → Status: processing
8. Pagamentos liberados → Status: completed
```

---

## 🔐 Segurança

### Boas Práticas Implementadas

- ✅ Sanitização de inputs
- ✅ Prepared statements (SQL injection protection)
- ✅ CSRF tokens (nonces)
- ✅ XSS protection
- ✅ Hashing de senhas (bcrypt)
- ✅ HTTPS obrigatório
- ✅ Rate limiting
- ✅ Logs de auditoria

### Checklist de Segurança

- [ ] Configurar SSL/TLS (HTTPS)
- [ ] Ativar firewall (mod_security)
- [ ] Configurar headers de segurança
- [ ] Fazer backup regular do banco de dados
- [ ] Atualizar WordPress e plugins
- [ ] Revisar logs regularmente
- [ ] Implementar 2FA para admins

---

## 🌍 Internacionalização

O sistema suporta múltiplos idiomas. Para adicionar uma tradução:

1. **Crie arquivo de tradução**:
   ```bash
   languages/transkwanza-pt_BR.po
   languages/transkwanza-en_US.po
   languages/transkwanza-es_ES.po
   ```

2. **Use funções de tradução**:
   ```php
   __('Texto', 'transkwanza');
   _e('Texto', 'transkwanza');
   ```

3. **Compile**:
   ```bash
   msgfmt -o transkwanza-pt_BR.mo transkwanza-pt_BR.po
   ```

---

## 🧪 Testes

### Testar Calculadora

```javascript
// Console do navegador
tkCalculator.calculateConversion();
```

### Testar Matching

```sql
-- Criar duas propostas complementares
INSERT INTO wp_tk_proposals (user_id, from_currency, to_currency, send_amount, ...) VALUES (1, 'BRL', 'EUR', 1000, ...);
INSERT INTO wp_tk_proposals (user_id, from_currency, to_currency, send_amount, ...) VALUES (2, 'EUR', 'BRL', 500, ...);

-- Verificar matching
SELECT * FROM wp_tk_transactions WHERE status = 'pending';
```

---

## 📊 Monitoramento

### Métricas Importantes

```sql
-- Total de transações
SELECT COUNT(*) FROM wp_tk_transactions WHERE status = 'completed';

-- Volume por moeda
SELECT currency_a, SUM(amount_a) as total FROM wp_tk_transactions GROUP BY currency_a;

-- Taxa de conversão
SELECT
    (SELECT COUNT(*) FROM wp_tk_transactions WHERE status = 'completed') /
    (SELECT COUNT(*) FROM wp_tk_proposals) * 100 as conversion_rate;

-- Usuários ativos
SELECT COUNT(DISTINCT user_id) FROM wp_tk_activity_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 🐛 Troubleshooting

### Problema: Taxas de câmbio não atualizam

**Solução:**
```bash
# Verificar cron jobs
wp cron event list

# Executar manualmente
wp cron event run tk_refresh_exchange_rates
```

### Problema: Propostas não expiram

**Solução:**
```php
// Executar manualmente
$proposal = new TK_Proposal();
$proposal->autoExpire();
```

### Problema: Emails não são enviados

**Solução:**
1. Instale plugin **WP Mail SMTP**
2. Configure SMTP (Gmail, SendGrid, etc.)
3. Teste envio de email

---

## 📞 Suporte

- **Email**: suporte@transkwanza.com
- **WhatsApp**: +55 11 93436-3623
- **Documentação**: https://docs.transkwanza.com
- **GitHub Issues**: https://github.com/your-repo/transkwanza/issues

---

## 📝 Changelog

### Versão 1.0.0 (2025-01-06)
- 🎉 Lançamento inicial
- ✅ Suporte a 9 países
- ✅ Calculadora multi-moeda
- ✅ Sistema de matching inteligente
- ✅ Dashboard responsivo
- ✅ Tema escuro
- ✅ Notificações em tempo real

---

## 📄 Licença

Copyright © 2025 TransKwanza. Todos os direitos reservados.

Este software é proprietário e não pode ser redistribuído sem autorização.

---

## 👥 Contribuindo

Este é um projeto proprietário. Para contribuições, entre em contato com a equipe.

---

## 🙏 Agradecimentos

- WordPress Community
- Google Finance API
- Todos os contribuidores e testadores

---

**Desenvolvido com ❤️ pela equipe TransKwanza**
