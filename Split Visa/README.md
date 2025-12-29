# Sistema de Split Automático Visa Advisory

Sistema para gerenciamento de clientes e cobranças com split automático de pagamentos via API Asaas.

## 📁 Estrutura de Arquivos

```
.
├── index.php              # Arquivo principal da aplicação
├── config.php             # Carregador de variáveis de ambiente
├── .env                   # Configurações sensíveis (NÃO versionar!)
├── .env.example           # Template de configuração
├── .htaccess              # Configurações de segurança do Apache
├── .gitignore             # Arquivos ignorados pelo Git
├── CHECKLIST_DEPLOY.md    # Checklist para deploy
└── README.md              # Este arquivo
```

## 🚀 Instalação e Configuração

### 1. Configurar Variáveis de Ambiente

Copie o arquivo `.env.example` para `.env` (se ainda não existir) e edite com suas credenciais:

```bash
# No Linux/Mac:
cp .env.example .env

# No Windows (PowerShell):
Copy-Item .env.example .env
```

Edite o arquivo `.env` e preencha os valores:

```env
# Token da API Asaas
ASAAS_TOKEN=seu_token_aqui

# IDs das carteiras dos vendedores
VENDEDOR_CAROLINA_WALLET_ID=wallet_id_carolina
VENDEDOR_JENNIFER_WALLET_ID=wallet_id_jennifer
VENDEDOR_JASSERA_WALLET_ID=wallet_id_jassera
```

### 2. Upload para Servidor

Faça upload dos seguintes arquivos para o servidor (geralmente na pasta `public_html`):

- ✅ `index.php`
- ✅ `config.php`
- ✅ `.htaccess`
- ✅ `.env` (certifique-se de que está com os valores corretos)

**⚠️ IMPORTANTE**: 
- O arquivo `.env` contém informações sensíveis
- NUNCA compartilhe este arquivo
- Certifique-se de que o `.htaccess` está protegendo o `.env` (já está configurado)

## 🔒 Segurança

- O arquivo `.env` está protegido pelo `.htaccess` e não pode ser acessado via web
- O arquivo `.env` está no `.gitignore` e não será versionado
- Mantenha o arquivo `.env` seguro no servidor

## ⚙️ Requisitos do Servidor

- PHP 7.4 ou superior
- Extensão cURL habilitada
- Sessões PHP habilitadas
- Mod_rewrite do Apache (para o .htaccess funcionar)

## 📝 Funcionalidades

- ✅ Criar cliente
- ✅ Buscar cliente por CPF/CNPJ
- ✅ Criar cobrança com split automático
- ✅ Visualizar cobranças do cliente

## 🔧 Como Funciona

1. O `config.php` carrega as variáveis do arquivo `.env`
2. O `index.php` usa a função `env()` para acessar as configurações
3. As requisições são feitas para a API Asaas usando o token configurado
4. O split é calculado automaticamente conforme o vendedor selecionado:
   - Carolina: 25% de comissão
   - Jennifer e Jassera: 15% de comissão

## 📚 Documentação Adicional

Consulte o arquivo `CHECKLIST_DEPLOY.md` para instruções detalhadas de deploy.

