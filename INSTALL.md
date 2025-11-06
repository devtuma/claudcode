# 🚀 Guia de Instalação Rápida - TransKwanza

## ⚡ Instalação Express (5 minutos)

### Pré-requisitos
- Servidor com PHP 7.4+ e MySQL 5.7+
- WordPress 5.8+ instalado
- Acesso SSH ao servidor

---

## 📦 Passo a Passo

### 1. Upload dos Arquivos
```bash
# Via SSH
cd /var/www/html/wp-content/themes/
git clone https://github.com/your-repo/transkwanza.git

# Ou via FTP
# Faça upload da pasta 'transkwanza' para wp-content/themes/
```

### 2. Ativar o Tema
```bash
# Via WP-CLI (recomendado)
wp theme activate transkwanza

# Ou via painel WordPress:
# wp-admin > Aparência > Temas > Ativar "TransKwanza"
```

### 3. Banco de Dados (Automático)
O banco de dados é criado automaticamente na ativação do tema.

**Verificar instalação:**
```bash
wp db query "SHOW TABLES LIKE 'wp_tk_%';"
```

### 4. Criar Páginas Essenciais
```bash
# Via WP-CLI
wp post create --post_type=page --post_title='Home' --post_status=publish --post_name='home' --page_template='templates/home.php'
wp post create --post_type=page --post_title='Dashboard' --post_status=publish --post_name='dashboard' --page_template='templates/dashboard.php'

# Definir Home como página inicial
wp option update show_on_front page
wp option update page_on_front $(wp post list --post_type=page --name=home --field=ID --format=ids)
```

### 5. Configurar Permalinks
```bash
wp rewrite structure '/%postname%/'
wp rewrite flush
```

### 6. Configurar Cron (Opcional mas recomendado)
```bash
# Editar crontab
crontab -e

# Adicionar:
0 * * * * cd /var/www/html && wp cron event run tk_refresh_exchange_rates
0 * * * * cd /var/www/html && wp cron event run tk_expire_proposals
0 0 * * * cd /var/www/html && wp cron event run tk_clean_notifications
```

---

## ✅ Verificação

### Teste Básico
1. Acesse: `https://seu-site.com`
2. Deve ver a calculadora de câmbio
3. Crie uma conta de teste
4. Acesse: `https://seu-site.com/dashboard`

### Teste de Taxas de Câmbio
```bash
wp eval "
\$service = new TK_ExchangeRateService();
\$rate = \$service->getExchangeRate('USD', 'BRL');
print_r(\$rate);
"
```

---

## 🔧 Configurações Opcionais

### SSL (Altamente Recomendado)
```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d seu-site.com
```

### Otimização de Performance
```bash
# Instalar cache
wp plugin install wp-super-cache --activate

# Otimizar banco de dados
wp db optimize
```

### Backup Automático
```bash
# Instalar UpdraftPlus
wp plugin install updraftplus --activate
```

---

## 🏦 Configurar Contas Bancárias

Edite o arquivo SQL ou faça via phpMyAdmin:

```sql
-- Acesse phpMyAdmin ou MySQL
mysql -u root -p transkwanza_db

-- Inserir contas da plataforma
INSERT INTO wp_tk_platform_accounts
(country_code, currency_code, bank_name, account_holder, account_details, status)
VALUES
('BRA', 'BRL', 'Banco do Brasil', 'TransKwanza Brasil Ltda',
 '{"pix_key": "12345678901", "account": "123456-7", "agency": "1234"}', 'active'),

('AGO', 'AOA', 'BFA', 'TransKwanza Angola',
 '{"account": "123456789", "multicaixa": "987654321"}', 'active'),

('PRT', 'EUR', 'Millennium BCP', 'TransKwanza Portugal',
 '{"iban": "PT50000000000000000000001", "mb_way": "+351912345678"}', 'active'),

('USA', 'USD', 'Bank of America', 'TransKwanza LLC',
 '{"routing": "026009593", "account": "1234567890", "zelle": "email@transkwanza.com"}', 'active');
-- Continuar para outros países...
```

---

## 👤 Criar Usuário Admin

```bash
# Via WP-CLI
wp user create admin admin@transkwanza.com --role=administrator --user_pass=SenhaSegura123

# Ou via painel
# wp-admin > Usuários > Adicionar Novo
```

---

## 🔐 Segurança Adicional

### 1. Proteger wp-config.php
```bash
chmod 600 wp-config.php
```

### 2. Desabilitar edição de arquivos
```php
// Adicionar ao wp-config.php
define('DISALLOW_FILE_EDIT', true);
```

### 3. Limitar tentativas de login
```bash
wp plugin install limit-login-attempts-reloaded --activate
```

### 4. Configurar Headers de Segurança
```apache
# Adicionar ao .htaccess
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

---

## 📧 Configurar Email (SMTP)

### Opção 1: Plugin WP Mail SMTP
```bash
wp plugin install wp-mail-smtp --activate
```

Configure via painel:
- **From Email**: noreply@transkwanza.com
- **From Name**: TransKwanza
- **Mailer**: Gmail / SendGrid / Mailgun
- Insira credenciais SMTP

### Opção 2: Gmail (para testes)
```php
// Adicionar ao functions.php ou wp-config.php
add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'smtp.gmail.com';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 587;
    $phpmailer->Username = 'seu-email@gmail.com';
    $phpmailer->Password = 'sua-senha-app';
    $phpmailer->SMTPSecure = 'tls';
});
```

---

## 📱 Configurar Login Social (Opcional)

### Google Login
```bash
# Instalar plugin
wp plugin install nextend-social-login --activate
```

Configurar:
1. Criar projeto no [Google Cloud Console](https://console.cloud.google.com)
2. Habilitar Google+ API
3. Criar credenciais OAuth 2.0
4. Adicionar Client ID e Secret no plugin

### Facebook Login
1. Criar app no [Facebook Developers](https://developers.facebook.com)
2. Adicionar Facebook Login
3. Configurar redirect URI
4. Adicionar App ID e Secret no plugin

---

## 🌍 Multi-idioma (Opcional)

```bash
# Instalar WPML ou Polylang
wp plugin install polylang --activate

# Ou manualmente criar arquivos .po/.mo em:
# wp-content/themes/transkwanza/languages/
```

---

## 📊 Monitoramento

### Google Analytics
```php
// Adicionar ao header.php ou usar plugin
wp plugin install google-analytics-for-wordpress --activate
```

### Logs de Erro
```php
// wp-config.php
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## 🧪 Testes Finais

### Checklist de Funcionalidades
- [ ] Calculadora funciona
- [ ] Cadastro de usuário funciona
- [ ] Login funciona
- [ ] Dashboard carrega
- [ ] Criar proposta funciona
- [ ] Matching automático funciona
- [ ] Notificações chegam
- [ ] Emails são enviados
- [ ] Layout responsivo (mobile)
- [ ] Tema escuro aplicado

### Teste de Carga
```bash
# Instalar WP-CLI Load Test
wp load-test run --concurrency=10 --requests=100
```

---

## 🆘 Problemas Comuns

### Erro 500
```bash
# Verificar logs
tail -f /var/log/apache2/error.log
tail -f wp-content/debug.log
```

### Página em branco
```bash
# Aumentar memória PHP
# Editar php.ini ou .htaccess
php_value memory_limit 256M
```

### Tema não ativa
```bash
# Verificar permissões
chmod -R 755 wp-content/themes/transkwanza
chown -R www-data:www-data wp-content/themes/transkwanza
```

### Banco não cria
```bash
# Executar manualmente
wp db query < wp-content/themes/transkwanza/database/schema.sql
```

---

## 📞 Suporte Técnico

- **Email**: dev@transkwanza.com
- **WhatsApp**: +55 11 93436-3623
- **Docs**: https://docs.transkwanza.com
- **Issues**: https://github.com/your-repo/transkwanza/issues

---

## 🎉 Pronto!

Sua instalação TransKwanza está completa!

**Próximos passos:**
1. Personalizar cores e logos
2. Configurar contas bancárias
3. Testar fluxo completo
4. Convidar usuários beta
5. Lançar! 🚀

---

**Tempo médio de instalação:** 5-10 minutos
**Dificuldade:** ⭐⭐☆☆☆ (Fácil)

Boa sorte! 💪
