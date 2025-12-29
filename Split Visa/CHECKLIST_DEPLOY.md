# Checklist para Deploy na Hostinger

## ✅ Correções Realizadas

1. **Funcionalidade 'ver_cobrancas' implementada** - Adicionada a funcionalidade que estava no menu mas não estava implementada
2. **Erro corrigido** - Removida referência a `$_SESSION['clienteId']` que poderia gerar warning quando não existisse
3. **Arquivo .htaccess criado** - Configurações de segurança e otimização adicionadas
4. **Referência ao visa.ico removida** - Removida referência ao arquivo que não existe
5. **Estrutura do código corrigida** - Corrigido o problema de execução duplicada de curl
6. **Sistema de variáveis de ambiente implementado** - Token e IDs dos vendedores agora estão no arquivo .env
7. **Arquivo config.php criado** - Carrega as variáveis de ambiente do arquivo .env
8. **.gitignore criado** - Protege o arquivo .env de ser versionado
9. **.env.example criado** - Template para configuração

### 2. Requisitos do Servidor
O código precisa de:
- ✅ PHP (versão 7.4 ou superior recomendada)
- ✅ Extensão cURL habilitada
- ✅ Sessões PHP habilitadas (padrão na maioria dos servidores)

**Hostinger geralmente atende esses requisitos por padrão.**

### 3. Arquivos para Upload
Certifique-se de fazer upload de:
- ✅ `index.php`
- ✅ `config.php`
- ✅ `.htaccess`
- ✅ `.env` (arquivo com as configurações sensíveis)
- ✅ `.env.example` (opcional, apenas como referência)

**⚠️ IMPORTANTE**: O arquivo `.env` contém informações sensíveis (token da API). 
- NUNCA compartilhe este arquivo
- NUNCA faça commit do `.env` no Git (já está no .gitignore)
- Mantenha o `.env` seguro no servidor

### 4. Estrutura de Diretórios
- O arquivo `index.php` deve estar na raiz do diretório público (geralmente `public_html` ou `www`)
- O `.htaccess` também deve estar na mesma pasta

## 📋 Passos para Deploy

1. **Acesse o painel da Hostinger**
   - Faça login no painel de controle

2. **Acesse o Gerenciador de Arquivos**
   - Navegue até o diretório `public_html` (ou `www` dependendo do plano)

3. **Faça upload dos arquivos**
   - Faça upload do `index.php`
   - Faça upload do `config.php`
   - Faça upload do `.htaccess`
   - Faça upload do `.env` (certifique-se de que contém os valores corretos)

4. **Verifique permissões**
   - Certifique-se que o diretório tem permissões adequadas (geralmente 755)
   - O arquivo index.php deve ter permissão 644

5. **Teste a aplicação**
   - Acesse o domínio no navegador
   - Teste as funcionalidades:
     - Criar cliente
     - Buscar cliente
     - Criar cobrança
     - Ver cobranças

## 🔒 Considerações de Segurança

1. **Token da API**: ✅ **CORRIGIDO** - Agora está no arquivo `.env` separado
   - O arquivo `.env` está protegido pelo `.htaccess` (não pode ser acessado via web)
   - O arquivo `.env` está no `.gitignore` (não será versionado)
   - **Lembre-se**: Mantenha o `.env` seguro no servidor

2. **Validação de Dados**: O código atual não valida todos os dados de entrada. Considere adicionar:
   - Validação de CPF/CNPJ
   - Validação de email
   - Sanitização de todos os inputs

3. **Proteção CSRF**: Considere adicionar tokens CSRF para proteção adicional

## ✅ Status Final

O código está **pronto para deploy** na Hostinger com sistema de variáveis de ambiente implementado!

### Checklist Final antes do Deploy:
- [x] Código corrigido e funcional
- [x] Sistema de variáveis de ambiente implementado
- [x] Arquivo .env criado com valores corretos
- [x] Arquivo .htaccess configurado para proteger .env
- [x] Arquivo .gitignore criado
- [ ] Upload dos arquivos para o servidor Hostinger
- [ ] Verificar se o arquivo .env está com os valores corretos no servidor
- [ ] Testar todas as funcionalidades após o deploy

### Nota sobre o arquivo .env:
O arquivo `.env` já foi criado localmente com seus valores. Ao fazer upload para o servidor, certifique-se de que o conteúdo está correto. O `.htaccess` já está configurado para proteger este arquivo de acesso via web.

