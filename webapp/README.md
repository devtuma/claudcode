# TransKwanza Standalone Web App

Aplicação web standalone de remessas internacionais P2P (peer-to-peer) sem dependência do WordPress.

## 📋 Sobre

TransKwanza é uma plataforma que conecta pessoas que querem enviar dinheiro internacionalmente em direções opostas, economizando em taxas de câmbio através de um sistema de matching inteligente.

### Características

- ✅ **9 países suportados**: Brasil, Angola, Portugal, EUA, Cuba, Rússia, África do Sul, Namíbia, Moçambique
- ✅ **Sistema de matching P2P**: Conecta usuários que querem enviar em direções opostas
- ✅ **Taxa transparente**: 3% sobre cada transação
- ✅ **Calculadora em tempo real**: Conversão instantânea com taxas atualizadas
- ✅ **Dark theme**: Interface moderna e agradável
- ✅ **Mobile-first**: Totalmente responsivo
- ✅ **API RESTful**: Backend PHP com arquitetura limpa
- ✅ **SQLite ou MySQL**: Suporte para ambos os bancos de dados

## 🚀 Instalação

### Requisitos

- PHP 7.4 ou superior
- SQLite3 (ou MySQL/MariaDB)
- Servidor web (Apache, Nginx, ou PHP built-in server)
- Extensões PHP: PDO, PDO_SQLite (ou PDO_MySQL), json, mbstring

### Instalação Rápida

1. **Clone ou faça download do projeto**

```bash
cd /seu/servidor/web/
git clone https://github.com/seu-usuario/transkwanza.git
cd transkwanza/webapp
```

2. **Configure o banco de dados**

Edite `config/database.php` se necessário. Por padrão, usa SQLite.

3. **Inicialize o banco de dados**

```bash
# Se usando SQLite (padrão)
php -r "require 'config/database.php'; initDatabase();"

# Ou execute manualmente
sqlite3 database.db < database/schema.sql
```

4. **Configure permissões**

```bash
# Para SQLite
chmod 666 database.db
chmod 777 database/

# Para uploads (se necessário no futuro)
chmod 777 uploads/
```

5. **Inicie o servidor**

```bash
# Usando PHP built-in server (desenvolvimento)
php -S localhost:8000 -t .

# Ou configure Apache/Nginx para apontar para a pasta webapp/
```

6. **Acesse a aplicação**

Abra seu navegador em: `http://localhost:8000/pages/index.html`

## 📁 Estrutura do Projeto

```
webapp/
├── api/                    # API Backend
│   ├── index.php          # Router principal
│   └── endpoints/         # Endpoints da API
│       ├── register.php   # Registro de usuário
│       ├── login.php      # Login
│       ├── logout.php     # Logout
│       ├── me.php         # Dados do usuário
│       ├── countries.php  # Lista de países
│       ├── exchange_rate.php  # Taxas de câmbio
│       ├── convert.php    # Conversão de moedas
│       ├── proposals.php  # CRUD de propostas
│       ├── transactions.php   # Gestão de transações
│       └── notifications.php  # Notificações
├── assets/                # Frontend Assets
│   ├── css/
│   │   └── style.css      # Estilos (dark theme)
│   └── js/
│       ├── api.js         # Cliente API
│       ├── auth.js        # Autenticação
│       ├── home.js        # Página inicial
│       └── dashboard.js   # Dashboard
├── config/                # Configurações
│   └── database.php       # Config do banco de dados
├── database/              # Banco de dados
│   ├── schema.sql         # Schema SQLite
│   └── database.db        # Banco SQLite (criado automaticamente)
├── includes/              # Funções auxiliares
│   ├── auth.php          # Funções de autenticação
│   └── helpers.php       # Funções utilitárias
└── pages/                 # Páginas HTML
    ├── index.html         # Página inicial
    ├── login.html         # Login/Registro
    └── dashboard.html     # Dashboard do usuário
```

## 🔧 Configuração

### Banco de Dados

Edite `config/database.php` para escolher entre SQLite ou MySQL:

```php
// SQLite (padrão)
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../database/database.db');

// Ou MySQL
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'transkwanza');
define('DB_USER', 'root');
define('DB_PASS', 'password');
```

### Constantes da Aplicação

Em `config/database.php`, você pode ajustar:

```php
define('PLATFORM_FEE', 0.03);              // Taxa de 3%
define('EXCHANGE_CACHE_MINUTES', 30);      // Cache de taxas
define('SESSION_LIFETIME', 7 * 24 * 3600); // 7 dias
```

## 🌐 Deploy em Servidor de Produção

### Hostinger / cPanel

1. Faça upload de toda a pasta `webapp/` via FTP ou File Manager
2. Acesse cPanel > PHP Selector e selecione PHP 7.4+
3. Certifique-se que SQLite3 está habilitado
4. Configure `.htaccess` na raiz de `webapp/`:

```apache
# webapp/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to pages/ if accessing root
    RewriteRule ^$ pages/index.html [L]

    # API routes
    RewriteRule ^api/(.*)$ api/index.php [L,QSA]
</IfModule>

# Security
<FilesMatch "\.(db|sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

5. Inicialize o banco de dados via terminal SSH ou PHPMyAdmin

### Nginx

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /var/www/transkwanza/webapp;
    index index.html;

    location / {
        try_files $uri $uri/ /pages/index.html;
    }

    location /api {
        try_files $uri /api/index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(db|sql|git) {
        deny all;
    }
}
```

### Apache

```apache
# .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On

    # API routing
    RewriteRule ^api/(.*)$ api/index.php [L,QSA]

    # Security
    RewriteRule \.(db|sql)$ - [F,L]
</IfModule>
```

## 📖 Uso da API

### Autenticação

**Registro:**
```bash
POST /api/auth/register
Content-Type: application/json

{
  "email": "usuario@email.com",
  "password": "senha123",
  "full_name": "Nome Completo",
  "country_code": "BRA"
}
```

**Login:**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "usuario@email.com",
  "password": "senha123"
}
```

**Resposta:**
```json
{
  "success": true,
  "token": "abc123...",
  "user": { ... }
}
```

### Endpoints Principais

- `GET /api/countries` - Lista países e moedas
- `GET /api/exchange-rate?from=BRL&to=EUR` - Taxa de câmbio
- `POST /api/convert` - Converter valores
- `GET /api/proposals` - Listar propostas
- `POST /api/proposals` - Criar proposta
- `POST /api/proposals/:id/accept` - Aceitar proposta
- `GET /api/transactions` - Minhas transações
- `POST /api/transactions/:id/confirm-payment` - Confirmar pagamento

Todos os endpoints (exceto auth e countries) requerem autenticação via header:
```
Authorization: Bearer {token}
```

## 🎨 Personalização

### Cores (Dark Theme)

Edite `assets/css/style.css`:

```css
:root {
    --tk-primary: #4CAF50;        /* Verde principal */
    --tk-bg-primary: #121212;     /* Fundo principal */
    --tk-bg-card: #1e1e1e;        /* Cards */
    /* ... */
}
```

### Taxas e Configurações

Edite `config/database.php`:

```php
define('PLATFORM_FEE', 0.03);  // 3% (mude para 0.05 para 5%)
```

## 🔒 Segurança

- ✅ Senhas hasheadas com bcrypt
- ✅ Proteção contra SQL injection (PDO prepared statements)
- ✅ Sanitização de inputs
- ✅ Tokens de sessão seguros (64 caracteres random)
- ✅ CORS configurado
- ⚠️ **IMPORTANTE**: Em produção, use HTTPS sempre!

## 🐛 Troubleshooting

### Erro: "Failed to open database"
```bash
chmod 666 database/database.db
chmod 777 database/
```

### Erro: "Call to undefined function sqlite_open"
```bash
# Instale SQLite3
sudo apt-get install php-sqlite3

# Ou habilite no php.ini
extension=pdo_sqlite
extension=sqlite3
```

### Erro 500 na API
Verifique os logs do PHP e certifique-se que mod_rewrite está habilitado.

## 📝 Licença

Este projeto é de código aberto. Sinta-se livre para usar e modificar.

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Add MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 📧 Suporte

Para dúvidas ou problemas, abra uma issue no GitHub.

---

**Desenvolvido com ❤️ para facilitar remessas internacionais**
