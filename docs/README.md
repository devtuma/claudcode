# TransKwanza - Demo Estático (GitHub Pages)

⚠️ **ATENÇÃO**: Esta é uma versão DEMO estática apenas para visualização da interface.

## 🌐 Ver Demo Online

👉 **[https://devtuma.github.io/claudcode/](https://devtuma.github.io/claudcode/)**

## O que você pode ver aqui:

✅ Design e layout da plataforma com novo dark theme
✅ Calculadora de câmbio (com valores mock)
✅ Interface do dashboard
✅ Visualização de propostas
✅ Design responsivo (mobile e desktop)
✅ Componentes visuais completos

## O que NÃO funciona nesta demo:

❌ Sistema de autenticação (requer backend PHP)
❌ Matching de propostas (requer banco de dados)
❌ Criação de transações reais
❌ Upload de comprovantes
❌ Notificações em tempo real
❌ Integração com API de câmbio real
❌ Persistência de dados

## 🚀 Versões Disponíveis

### 1. Versão Standalone (Recomendada)
Aplicação web completa sem dependência do WordPress.

**Localização**: `../webapp/`

**Recursos**:
- ✅ API RESTful em PHP
- ✅ Suporte SQLite ou MySQL
- ✅ Zero dependências
- ✅ Fácil instalação (upload e pronto)
- ✅ Frontend moderno em HTML/CSS/JS vanilla

**Como usar**:
```bash
cd webapp/
php -S localhost:8000 -t .
# Acesse: http://localhost:8000/pages/index.html
```

📖 [Ver documentação completa da versão standalone](../webapp/README.md)

### 2. Versão WordPress (Alternativa)
Tema WordPress com todas as funcionalidades.

**Localização**: `../transkwanza/`

**Requisitos**:
- PHP 7.4+
- MySQL 5.7+
- WordPress 5.8+

**Instalação**:
1. Clone o repositório
2. Siga as instruções em [INSTALL.md](../INSTALL.md)
3. Configure o banco de dados
4. Ative o tema no WordPress

## 📄 Páginas da Demo:

- **[Home / Calculadora](index.html)** - Página inicial com calculadora interativa
- **[Dashboard Demo](dashboard-demo.html)** - Visualização do painel do usuário

## ⚙️ Como Configurar GitHub Pages

Se você fez fork deste repositório e quer ter sua própria demo online:

1. Vá em **Settings** do seu repositório
2. Na barra lateral, clique em **Pages**
3. Em **Source**, selecione:
   - Branch: `main` (ou `master`)
   - Folder: `/docs`
4. Clique em **Save**
5. Aguarde alguns minutos
6. Sua demo estará em: `https://seu-usuario.github.io/seu-repo/`

## 🔗 Links Úteis:

- 📖 [Documentação Completa](../README.md)
- 🚀 [Guia Versão Standalone](../webapp/README.md)
- ⚡ [Guia de Instalação WordPress](../INSTALL.md)
- 💻 [Repositório GitHub](https://github.com/devtuma/claudcode)
- 🎨 [Ver Demo Online](https://devtuma.github.io/claudcode/)

## 📊 Estatísticas

- **Versão Demo**: Estática (HTML/CSS/JS)
- **Versão Standalone**: ~4.500 linhas de código
- **Versão WordPress**: ~7.800 linhas de código
- **Países Suportados**: 9
- **Moedas**: BRL, EUR, USD, AOA, CUP, RUB, ZAR, NAD, MZN

---

**Desenvolvido com ❤️ - TransKwanza © 2025**
