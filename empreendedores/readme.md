# 🧑 Módulo Empreendedores (`empreendedores/`)

Área pública de cadastro de empreendedores. Permite que novos usuários se registrem na plataforma para, em seguida, cadastrar seus negócios.

***

## 📋 Arquivos do Módulo

| Arquivo | Descrição |
|---------|-----------|
| `register.php` | Formulário público de cadastro do empreendedor |
| `store.php` | Processamento do POST: validação, inserção no banco e envio de e-mail |
| `sucesso.php` | Página de confirmação exibida após o cadastro concluído |

***

## 🔄 Fluxo de Cadastro

```
1. Usuário acessa register.php
2. Preenche o formulário (validação client-side via JavaScript)
3. POST enviado para store.php
4. Sanitização dos dados (app/helpers/functions.php)
5. Validação server-side (app/validators/validate_empreendedor.php)
6. Verificação de unicidade de e-mail e CPF no banco
7. Inserção na tabela empreendedores com senha criptografada (password_hash)
8. Envio de e-mail de boas-vindas (app/helpers/mail.php + app/helpers/email_template.php)
9. Redirecionamento para sucesso.php?email={email}
```

***

## 📝 Campos do Formulário (`register.php`)

| Campo | Obrigatório | Observações |
|-------|-------------|-------------|
| `nome` | ✅ | Trim aplicado |
| `sobrenome` | ✅ | Trim aplicado |
| `cpf` | ✅ | Validado via `isValidCPF()` |
| `email` | ✅ | Validado com `FILTER_VALIDATE_EMAIL`; deve ser único no banco |
| `celular` | ✅ | Sanitizado |
| `data_nascimento` | ✅ | Formato `Y-m-d` |
| `genero` | ✅ | Sanitizado |
| `cidade` | ✅ | Trim aplicado |
| `estado` | ✅ | Trim aplicado |
| `pais` | ✅ | Sanitizado |
| `regiao` | ✅ | Trim aplicado |
| `cargo` | ✅ | Trim aplicado |
| `origem_conhecimento` | ✅ | Sanitizado |
| `consentimento_email` | — | Checkbox; salvo como `1` ou `0` |
| `consentimento_whatsapp` | — | Checkbox; salvo como `1` ou `0` |
| `termos_uso` | ✅ | Obrigatório para submissão |
| `senha` | ✅ | Mínimo definido em `validate_empreendedor.php` |
| `senha_confirm` | ✅ | Deve ser idêntica a `senha` |
| `eh_fundador` | ✅ | `'Sim'` ou `'Não'` |
| `formacao` | Condicional | Obrigatório se `eh_fundador = 'Sim'` |
| `etnia` | Condicional | Obrigatório se `eh_fundador = 'Sim'` |

***

## 🗄️ Tabela Principal

**`empreendedores`**

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Auto-incremento |
| `nome` | VARCHAR | Primeiro nome |
| `sobrenome` | VARCHAR | Sobrenome |
| `cpf` | VARCHAR | CPF (somente dígitos, único) |
| `email` | VARCHAR | E-mail (único) |
| `celular` | VARCHAR | Celular |
| `data_nascimento` | DATE | Data de nascimento |
| `genero` | VARCHAR | Gênero |
| `cidade` | VARCHAR | Cidade de residência |
| `estado` | VARCHAR | Estado (UF) |
| `pais` | VARCHAR | País |
| `regiao` | VARCHAR | Região |
| `cargo` | VARCHAR | Cargo / função |
| `origem_conhecimento` | VARCHAR | Como conheceu a plataforma |
| `consentimento_email` | TINYINT | Aceite de comunicação por e-mail |
| `consentimento_whatsapp` | TINYINT | Aceite de comunicação por WhatsApp |
| `termos_uso` | TINYINT | Aceite dos termos |
| `eh_fundador` | VARCHAR | `'Sim'` ou `'Não'` |
| `formacao` | VARCHAR | Formação acadêmica (fundadores) |
| `etnia` | VARCHAR | Etnia/raça (fundadores) |
| `senha` | VARCHAR | Hash bcrypt (`password_hash`) |
| `status` | VARCHAR | `ativo` · `pendente` · `inativo` |
| `criado_em` | DATETIME | Data de criação |
| `ultimo_login` | DATETIME | Último acesso registrado |

***

## 📧 E-mail de Boas-Vindas

Após o cadastro bem-sucedido, o sistema envia um e-mail automático para o empreendedor via `send_mail()` com:
- Template montado em `app/helpers/email_template.php`
- Assunto configurável
- Instrução para acessar o painel e começar o cadastro do negócio

***

## 🔒 Segurança

- Senha armazenada com `password_hash()` (bcrypt)
- Prepared statements PDO em todas as queries
- Sanitização de todos os campos antes da inserção
- Verificação de unicidade de e-mail e CPF antes do INSERT
- Tokens CSRF no formulário POST