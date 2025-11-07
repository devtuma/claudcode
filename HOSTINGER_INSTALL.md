# 🚀 Guia de Instalação TransKwanza na Hostinger

## ✅ Pré-requisitos

Você precisa ter na Hostinger:
- ✅ Plano de hospedagem ativo (qualquer plano serve)
- ✅ Acesso ao painel hPanel
- ✅ WordPress instalado (ou vou te ensinar a instalar)

**Sim, você pode usar o domínio temporário da Hostinger!**
(exemplo: `seu-site.hostingersite.com`)

---

## 📋 Instalação Rápida (15-20 minutos)

### 🎯 MÉTODO 1: Instalação Automática (Recomendado - Mais Fácil)

#### Passo 1: Instalar WordPress na Hostinger

1. **Login no hPanel**
   - Acesse: https://hpanel.hostinger.com
   - Faça login com suas credenciais

2. **Auto Installer WordPress**
   - No painel principal, procure por **"Website"** ou **"Auto Installer"**
   - Clique em **"WordPress"**
   - Clique em **"Instalar"**

3. **Configurar a Instalação**
   ```
   Domínio: Escolha seu domínio temporário (ex: seu-site.hostingersite.com)
   Diretório: deixe vazio (instala na raiz) OU digite "transkwanza"
   Título do Site: TransKwanza
   Admin Username: admin (ou seu nome)
   Admin Email: seu-email@gmail.com
   Senha: Crie uma senha forte
   ```

4. **Instalar**
   - Clique em **"Instalar"**
   - Aguarde 2-3 minutos
   - ✅ WordPress instalado!

5. **Anotar Informações**
   ```
   URL do Site: https://seu-site.hostingersite.com
   URL Admin: https://seu-site.hostingersite.com/wp-admin
   Usuário: (o que você criou)
   Senha: (a que você criou)
   ```

---

#### Passo 2: Upload do Tema TransKwanza

##### Opção A: Via File Manager (Mais Fácil)

1. **Acessar File Manager**
   - No hPanel, vá em **"Arquivos"** > **"File Manager"**
   - Ou clique no ícone de pasta

2. **Navegar até a pasta de temas**
   ```
   public_html/
   └── wp-content/
       └── themes/     ← Entre aqui
   ```

3. **Fazer Upload**
   - Primeiro, você precisa fazer **download da pasta `transkwanza/`** do repositório
   - Zipar a pasta `transkwanza` no seu computador (criar transkwanza.zip)
   - No File Manager, clique em **"Upload"**
   - Selecione o arquivo `transkwanza.zip`
   - Aguarde o upload completar

4. **Extrair o ZIP**
   - Clique com botão direito em `transkwanza.zip`
   - Selecione **"Extract"** (Extrair)
   - Confirme a extração
   - Delete o arquivo .zip após extrair

##### Opção B: Via FTP (Alternativa)

1. **Obter Credenciais FTP**
   - No hPanel, vá em **"Arquivos"** > **"Gerenciador de FTP"**
   - Anote:
     ```
     Host: ftp.seu-site.hostingersite.com
     Usuário: u123456789
     Senha: sua-senha-ftp
     Porta: 21
     ```

2. **Conectar via FileZilla**
   - Baixe FileZilla: https://filezilla-project.org
   - Instale e abra
   - Insira os dados FTP
   - Conecte

3. **Upload da Pasta**
   - No lado direito (servidor), navegue até:
     ```
     /public_html/wp-content/themes/
     ```
   - No lado esquerdo (seu PC), localize a pasta `transkwanza`
   - Arraste a pasta `transkwanza` para o lado direito
   - Aguarde o upload (pode demorar 2-5 minutos)

---

#### Passo 3: Ativar o Tema

1. **Acessar WordPress Admin**
   - Vá para: `https://seu-site.hostingersite.com/wp-admin`
   - Faça login

2. **Ativar o Tema**
   - No menu lateral, vá em **Aparência** > **Temas**
   - Você verá o tema **"TransKwanza"**
   - Passe o mouse sobre ele e clique em **"Ativar"**
   - ✅ Tema ativado!

---

#### Passo 4: Criar Banco de Dados (Automático)

O banco de dados será criado **automaticamente** quando você ativar o tema!

**Verificar se foi criado:**

1. **Acessar phpMyAdmin**
   - No hPanel, procure por **"phpMyAdmin"**
   - Clique para abrir

2. **Verificar Tabelas**
   - No menu lateral, clique no seu banco de dados (geralmente começa com `u123456789_`)
   - Você deve ver várias tabelas começando com `wp_tk_`:
     ```
     wp_tk_countries
     wp_tk_exchange_rates
     wp_tk_users
     wp_tk_proposals
     wp_tk_transactions
     wp_tk_notifications
     ... (total de 12 tabelas)
     ```

3. **Se as tabelas NÃO apareceram:**
   - Execute manualmente o SQL:
   - No phpMyAdmin, clique na aba **SQL**
   - Copie todo o conteúdo de `database/schema.sql`
   - Cole na área de texto
   - Clique em **"Executar"**

---

#### Passo 5: Configurar Páginas

1. **Criar Página Inicial**
   - WordPress Admin > **Páginas** > **Adicionar Nova**
   - Título: `Home`
   - No lado direito, em **"Atributos da Página"**:
     - Template: Selecione **"Home"** ou **"templates/home.php"**
   - Clique em **"Publicar"**

2. **Criar Página Dashboard**
   - **Páginas** > **Adicionar Nova**
   - Título: `Dashboard`
   - Template: Selecione **"Dashboard"** ou **"templates/dashboard.php"**
   - Clique em **"Publicar"**

3. **Definir Home como Página Inicial**
   - Vá em **Configurações** > **Leitura**
   - Em "Sua página inicial exibe":
     - Selecione **"Uma página estática"**
     - Homepage: Selecione **"Home"**
   - Clique em **"Salvar alterações"**

---

#### Passo 6: Configurar Permalinks

1. **Configurar URLs Amigáveis**
   - Vá em **Configurações** > **Links Permanentes**
   - Selecione **"Nome do post"**
   - Clique em **"Salvar alterações"**

---

#### Passo 7: Configurar SMTP para Emails (Opcional mas Recomendado)

1. **Instalar Plugin WP Mail SMTP**
   - Vá em **Plugins** > **Adicionar Novo**
   - Pesquise: `WP Mail SMTP`
   - Clique em **"Instalar Agora"**
   - Clique em **"Ativar"**

2. **Configurar Gmail SMTP** (gratuito)
   - No menu, vá em **WP Mail SMTP** > **Settings**
   - **From Email**: seu-email@gmail.com
   - **From Name**: TransKwanza
   - **Mailer**: Selecione **"Gmail"** ou **"Other SMTP"**

   **Para Gmail:**
   ```
   SMTP Host: smtp.gmail.com
   SMTP Port: 587
   Encryption: TLS
   Auto TLS: Yes
   Username: seu-email@gmail.com
   Password: (senha de app do Gmail - veja abaixo)
   ```

3. **Criar Senha de App do Gmail**
   - Acesse: https://myaccount.google.com/apppasswords
   - Faça login no Gmail
   - Selecione **"App"**: Mail
   - Selecione **"Dispositivo"**: Other (Custom name)
   - Digite: TransKwanza
   - Clique em **"Gerar"**
   - Copie a senha de 16 caracteres
   - Cole no campo "Password" do WP Mail SMTP

4. **Testar Email**
   - Na aba **"Email Test"**
   - Envie um email de teste
   - ✅ Se receber, está funcionando!

---

#### Passo 8: Configurar Cron Jobs (Importante)

Os cron jobs atualizam taxas de câmbio e expiram propostas antigas.

1. **Acessar Cron Jobs na Hostinger**
   - No hPanel, procure por **"Cron Jobs"** ou **"Tarefas Agendadas"**
   - Clique para abrir

2. **Adicionar 3 Cron Jobs**

   **Cron 1: Atualizar Taxas de Câmbio (a cada hora)**
   ```
   Frequência: A cada hora
   Comando:
   /usr/bin/php /home/u123456789/public_html/wp-cron.php
   ```

   **Cron 2: Expirar Propostas (a cada hora)**
   ```
   Frequência: A cada hora
   Comando:
   cd /home/u123456789/public_html && /usr/bin/wp cron event run tk_expire_proposals
   ```

   **Cron 3: Limpar Notificações (diário)**
   ```
   Frequência: Diária (00:00)
   Comando:
   cd /home/u123456789/public_html && /usr/bin/wp cron event run tk_clean_notifications
   ```

   **IMPORTANTE**: Substitua `u123456789` pelo seu nome de usuário real da Hostinger!

3. **Se não conseguir configurar Cron Jobs**
   - Não se preocupe! O WordPress tem WP-Cron que roda automaticamente
   - Só será um pouco menos eficiente

---

#### Passo 9: Configurar SSL (HTTPS) - Grátis na Hostinger

1. **Ativar SSL Grátis**
   - No hPanel, procure por **"SSL"**
   - Selecione seu domínio temporário
   - Clique em **"Instalar SSL"** (gratuito da Let's Encrypt)
   - Aguarde 5-10 minutos

2. **Forçar HTTPS no WordPress**
   - Vá em **Configurações** > **Geral**
   - Altere ambas URLs:
     ```
     De: http://seu-site.hostingersite.com
     Para: https://seu-site.hostingersite.com
     ```
   - Clique em **"Salvar alterações"**

---

#### Passo 10: Testar a Plataforma! 🎉

1. **Acessar o Site**
   - Vá para: `https://seu-site.hostingersite.com`
   - Você deve ver a página inicial com:
     - ✅ Calculadora de câmbio
     - ✅ Seção "Como Funciona"
     - ✅ Grid de países
     - ✅ Tema escuro

2. **Criar Conta de Teste**
   - Clique em **"Cadastrar"**
   - Crie uma conta
   - Faça login

3. **Acessar Dashboard**
   - Vá para: `https://seu-site.hostingersite.com/dashboard`
   - Você deve ver:
     - ✅ Abas funcionando
     - ✅ Calculadora
     - ✅ Propostas disponíveis

4. **Testar Calculadora**
   - Selecione Brasil (BRL) → Portugal (EUR)
   - Digite um valor (ex: 1000)
   - Deve calcular automaticamente
   - Taxa de 3% aplicada

---

## 🎯 CHECKLIST DE VERIFICAÇÃO

Após a instalação, verifique:

- [ ] WordPress instalado e funcionando
- [ ] Tema TransKwanza ativado
- [ ] Página inicial (Home) exibindo corretamente
- [ ] Dashboard acessível após login
- [ ] Calculadora funcionando
- [ ] Bandeiras dos países aparecendo
- [ ] Tema escuro aplicado
- [ ] Layout responsivo (teste no celular)
- [ ] SSL/HTTPS ativo (cadeado verde)
- [ ] Emails funcionando (teste com WP Mail SMTP)
- [ ] Banco de dados criado (12 tabelas)

---

## 🐛 Problemas Comuns e Soluções

### Problema 1: Tema não aparece na lista
**Solução:**
- Verifique se a pasta está em `/wp-content/themes/transkwanza`
- Verifique se o arquivo `style.css` existe dentro da pasta
- Verifique permissões (755 para pastas, 644 para arquivos)

### Problema 2: Página em branco após ativar
**Solução:**
```php
// Adicione ao wp-config.php (antes de "That's all, stop editing!")
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```
- Veja os erros em: `/wp-content/debug.log`

### Problema 3: Taxas de câmbio não atualizam
**Solução:**
- Verifique se os cron jobs estão configurados
- Teste manualmente acessando: `https://seu-site.hostingersite.com/wp-cron.php`

### Problema 4: Erro ao criar banco de dados
**Solução:**
- Acesse phpMyAdmin
- Execute manualmente o SQL de `database/schema.sql`
- Substitua `wp_` pelo prefixo correto do seu WordPress

### Problema 5: Imagens/CSS não carregam
**Solução:**
- Limpe o cache do navegador (Ctrl+Shift+R)
- Verifique se SSL está ativo
- Vá em Configurações > Geral e corrija as URLs

---

## 🔧 Configurações Adicionais

### 1. Adicionar Logo Personalizado

1. Vá em **Aparência** > **Personalizar** > **Identidade do Site**
2. Faça upload do seu logo
3. Configure favicon

### 2. Configurar Contas Bancárias da Plataforma

1. Acesse phpMyAdmin
2. Vá na tabela `wp_tk_platform_accounts`
3. Clique em **"Inserir"**
4. Adicione suas contas por país:

```sql
INSERT INTO wp_tk_platform_accounts
(country_code, currency_code, bank_name, account_holder, account_details, status)
VALUES
('BRA', 'BRL', 'Banco do Brasil', 'TransKwanza Brasil',
 '{"pix_key": "sua-chave-pix", "account": "123456-7"}', 'active');
```

### 3. Personalizar Cores (Opcional)

Edite o arquivo `assets/css/style.css`:

```css
:root {
    --tk-primary: #4CAF50;  /* Altere para sua cor principal */
    --tk-secondary: #2196F3; /* Cor secundária */
}
```

---

## 📱 Usar Domínio Temporário vs Domínio Próprio

### ✅ Domínio Temporário Hostinger

**Vantagens:**
- ✅ Grátis
- ✅ Imediato
- ✅ Já vem com SSL
- ✅ Perfeito para testes

**Formato:**
```
https://seu-nome.hostingersite.com
```

**Como obter:**
- Ao criar o WordPress, a Hostinger gera automaticamente
- Ou vá em **"Websites"** > Seu site > **"Manage"** > Ver o domínio temporário

### 🌐 Migrar para Domínio Próprio (Depois)

Quando comprar seu domínio (ex: transkwanza.com):

1. **Adicionar Domínio**
   - hPanel > **"Domínios"** > **"Adicionar Domínio"**
   - Digite seu domínio
   - Aponte DNS (se comprou fora da Hostinger)

2. **Atualizar WordPress**
   - **Configurações** > **Geral**
   - Altere URLs para o novo domínio
   - Salve

3. **Redirecionar Temporário**
   - A Hostinger faz isso automaticamente

---

## 🚀 Próximos Passos Após Instalação

1. **Criar Conteúdo**
   - Adicione termos de uso
   - Adicione política de privacidade
   - Configure FAQ

2. **Testar Completamente**
   - Crie 2 contas de teste
   - Faça propostas
   - Teste o matching
   - Simule transações

3. **Configurar Produção**
   - Adicione contas bancárias reais
   - Configure métodos de pagamento
   - Ajuste limites e valores

4. **Marketing**
   - Compartilhe o link
   - Crie redes sociais
   - Comece a divulgar

---

## 📊 Resumo do Processo

```
1. Instalar WordPress na Hostinger (5 min)
   ↓
2. Upload tema TransKwanza via File Manager (5 min)
   ↓
3. Ativar tema no WordPress (1 min)
   ↓
4. Criar páginas Home e Dashboard (2 min)
   ↓
5. Configurar permalinks (1 min)
   ↓
6. Configurar SMTP (5 min - opcional)
   ↓
7. Configurar Cron Jobs (3 min)
   ↓
8. Ativar SSL (5-10 min)
   ↓
9. Testar tudo (5 min)
   ↓
✅ PRONTO! (Total: 15-20 min)
```

---

## 💡 Dicas Importantes

1. **Use sempre HTTPS** (SSL ativo)
2. **Faça backups regulares** (a Hostinger oferece backups automáticos)
3. **Mantenha WordPress atualizado**
4. **Teste tudo antes de divulgar**
5. **Configure Google Analytics** para monitorar acessos

---

## 📞 Precisa de Ajuda?

### Suporte Hostinger
- Chat 24/7 disponível no hPanel
- Email: suporte@hostinger.com.br

### Suporte TransKwanza
- WhatsApp: +55 11 93436-3623
- Email: suporte@transkwanza.com
- Documentação: [README.md](README.md)

---

## 🎬 Vídeos Úteis

Procure no YouTube:
- "Como instalar WordPress na Hostinger"
- "Como fazer upload de tema WordPress"
- "Como configurar SSL na Hostinger"

---

## ✅ Checklist Final

Antes de lançar ao público:

- [ ] Site funcional e testado
- [ ] SSL ativo (HTTPS)
- [ ] Emails funcionando
- [ ] Todas as 12 tabelas criadas no banco
- [ ] Contas bancárias configuradas
- [ ] Termos de uso e privacidade adicionados
- [ ] Testado em mobile e desktop
- [ ] Backup configurado
- [ ] Analytics instalado (opcional)
- [ ] Domínio configurado (se não usar temporário)

---

**Tempo Total Estimado**: 15-30 minutos (dependendo da sua experiência)

**Custo na Hostinger**: A partir de R$ 8,99/mês (plano básico já serve!)

---

## 🎉 Pronto!

Agora você tem o TransKwanza rodando na Hostinger com domínio temporário!

**Acesse**: `https://seu-site.hostingersite.com`

Boa sorte com seu projeto! 🚀
