# 📦 Plataforma Impactos Positivos

Este projeto é uma plataforma web para gerenciamento de usuários e envio de e-mails automáticos via SMTP. Abaixo está a estrutura de diretórios e arquivos criados ou modificados, com descrições para facilitar a manutenção e continuidade do desenvolvimento.

---

## 📁 Estrutura de Diretórios / Directory Structure

/ ├── app/ │   ├── config/ │   │   └── mail.php │   ├── helpers/ │   │   └── mail.php │   └── views/ │       └── emails/ │           └── new_user.php ├── vendor/ │   └── phpmailer/ │       └── src/ │           ├── Exception.php │           ├── PHPMailer.php │           └── SMTP.php ├── public_html/ │   ├── test_mail_debug.php │   ├── test_phpmailer_load.php │   ├── test_debug_includes.php │   └── test_simple.php ├── admin/ │   └── create_user.php └── README.md


---

## 📄 Arquivos e funções / Files and Functions

### `app/config/mail.php`
**PT:** Configurações de envio SMTP.  
**EN:** SMTP configuration file.

### `app/helpers/mail.php`
**PT:** Helper para envio de e-mails via PHPMailer.  
**EN:** PHPMailer-based email helper.

### `app/views/emails/new_user.php`
**PT:** Template HTML do e-mail de boas-vindas.  
**EN:** Welcome email HTML template.

### `admin/create_user.php`
**PT:** Script para criação de usuários e envio de e-mail automático.  
**EN:** User creation script with email dispatch.

### `public_html/test_mail_debug.php`
**PT:** Teste completo de envio SMTP com debug.  
**EN:** Full SMTP debug test using PHPMailer.

### `public_html/test_phpmailer_load.php`
**PT:** Verifica se PHPMailer está corretamente carregado.  
**EN:** Checks PHPMailer class loading.

### `public_html/test_debug_includes.php`
**PT:** Depuração de includes e arquivos.  
**EN:** Include and file loading debugger.

### `public_html/test_simple.php`
**PT:** Teste mínimo de execução PHP.  
**EN:** Minimal PHP execution test.

---

## ✅ Requisitos / Requirements

- PHP 7.4+
- OpenSSL habilitado / OpenSSL enabled
- Saída SMTP liberada no servidor / SMTP outbound access allowed
- PHPMailer instalado manualmente / PHPMailer manually installed

---

## 🔐 Segurança / Security

- Nunca versionar `mail.php` com senha real  
- Never commit `mail.php` with real credentials  
- Rotacionar senha SMTP se exposta  
- Rotate SMTP password if exposed  
- Validar entrada e sanitizar variáveis  
- Validate input and sanitize variables

---

## 👤 Responsável / In Charge

- Desenvolvedora: **Daniela**
- Última atualização: **Novembro/2025**
- Conversa técnica registrada em: **Copilot – PIP 2026**

## Diagrama Relacional 
                ┌───────────────┐
                │   users        │   ← já existente (admin/superadmin/juri)
                └───────┬───────┘
                        │ (FK opcional)
                        │
                ┌───────────────┐
                │ empreendedores │   ← responsáveis pela inscrição
                └───────┬───────┘
                        │ 1:N
                        │
        ┌───────────────┐
        │   fundadores   │   ← cofundadores ligados a um empreendedor
        └───────────────┘

                        │ 1:N
                        │
        ┌───────────────┐
        │   negocios     │   ← cada empreendedor pode cadastrar vários negócios
        └───────┬───────┘
                │
                │ 1:N
                │
 ┌───────────────────────┐
 │   eixos_tematicos      │ ← eixos e subáreas de impacto
 └───────────────────────┘

 ┌───────────────────────┐
 │   ods_negocio          │ ← ODS principal + relacionados
 └───────────────────────┘

 ┌───────────────────────┐
 │ apresentacao_negocio   │ ← descrição, problema/solução, vídeos, imagens, inovação
 └───────────────────────┘

 ┌───────────────────────┐
 │ dados_financeiros      │ ← faturamento, fontes de receita, investimentos
 └───────────────────────┘

 ┌───────────────────────┐
 │ avaliacao_impacto      │ ← intencionalidade, públicos, indicadores, evidências
 └───────────────────────┘


 /empreendedores
  register.php             ← formulário de cadastro
  store.php                ← processamento do cadastro (POST)
  sucesso.php              ← confirmação de cadastro

/app/views/public/header_public.php   ← já carrega Bootstrap + Select2
/app/views/public/footer_public.php   ← fecha HTML + scripts
/empreendedores/register.php          ← formulário de cadastro


# Fluxo de Cadastro Empreendedores

Este documento descreve a estrutura de pastas e arquivos criados para o fluxo de cadastro de empreendedores, incluindo validação de CPF, senha, envio de e-mail e integração futura com APIs externas.

---

## 📂 Estrutura de Pastas

app/ 
├── config/ 
│    └── db.php                # Configuração de conexão com o banco de dados (host, user, senha, etc.) 
├── helpers/ 
│    ├── functions.php         # Funções utilitárias globais: sanitização, normalização e validação de CPF 
│    ├── mail.php              # Função de envio de e-mails usando PHPMailer 
│    └── email_template.php    # Template HTML para e-mails de boas-vindas 
└── validators/ 
        └── validate_empreendedor.php # Validação dos dados do empreendedor (nome, email, CPF, senha, etc.)
empreendedores/ 
        └── store.php                  # Script principal de cadastro: valida dados, consulta CPF, insere no banco e envia e-mail

---

## 📌 Descrição dos Arquivos

### `app/config/db.php`
- Contém as credenciais e parâmetros de conexão com o banco de dados MySQL.
- Usado pelo `store.php` para inserir os dados do empreendedor.

### `app/helpers/functions.php`
- Funções globais:
  - `sanitize_text()` → sanitiza texto.
  - `sanitize_email()` → sanitiza e valida e-mail.
  - `only_digits()` → remove caracteres não numéricos.
  - `validar_cpf()` → valida estrutura matemática do CPF.
  - `consultar_cpf_receitaws()` → consulta CPF na API ReceitaWS (para protótipo).

### `app/helpers/mail.php`
- Configuração e envio de e-mails via PHPMailer.
- Usado para enviar e-mail de boas-vindas após cadastro.

### `app/helpers/email_template.php`
- Template HTML para o corpo do e-mail de boas-vindas.
- Personalizado com nome do empreendedor.

### `app/validators/validate_empreendedor.php`
- Função `validar_empreendedor()` que valida:
  - Nome e sobrenome obrigatórios.
  - CPF válido (estrutura).
  - E-mail válido.
  - Senha com mínimo de 8 caracteres e confirmação igual.
  - Data de nascimento no formato correto (opcional).
- Inclui `functions.php` para usar utilitários.

### `empreendedores/store.php`
- Fluxo principal de cadastro:
  1. Recebe dados do formulário.
  2. Sanitiza e valida com `validate_empreendedor.php`.
  3. Consulta CPF na ReceitaWS (protótipo).
  4. Verifica unicidade de e-mail e CPF no banco.
  5. Insere dados no banco com senha criptografada.
  6. Envia e-mail de boas-vindas.
  7. Mantém usuário na mesma página em caso de erro (alert inline ou JavaScript).

---

# Registration Flow (English)

This document describes the folder structure and files created for the entrepreneur registration flow, including CPF validation, password checks, email sending, and future API integration.

---

## 📂 Folder Structure
app/ 
├── config/ 
│    └── db.php                # Database connection settings (host, user, password, etc.) 
├── helpers/ 
│    ├── functions.php         # Global utility functions: sanitization, normalization, CPF validation 
│    ├── mail.php              # Email sending function using PHPMailer 
│    └── email_template.php    # HTML template for welcome emails 
└── validators/ 
        └── validate_empreendedor.php # Entrepreneur data validation (name, email, CPF, password, etc.)
empreendedores/ 
        └── store.php                  # Main registration script: validates data, checks CPF, inserts into DB, sends email

---

## 📌 File Descriptions

### `app/config/db.php`
- Contains MySQL database connection credentials and parameters.
- Used by `store.php` to insert entrepreneur data.

### `app/helpers/functions.php`
- Global functions:
  - `sanitize_text()` → sanitize text.
  - `sanitize_email()` → sanitize and validate email.
  - `only_digits()` → remove non-numeric characters.
  - `validar_cpf()` → validate CPF mathematical structure.
  - `consultar_cpf_receitaws()` → query CPF in ReceitaWS API (prototype).

### `app/helpers/mail.php`
- Email configuration and sending via PHPMailer.
- Used to send welcome emails after registration.

### `app/helpers/email_template.php`
- HTML template for welcome email body.
- Personalized with entrepreneur’s name.

### `app/validators/validate_empreendedor.php`
- Function `validar_empreendedor()` validates:
  - Required name and surname.
  - Valid CPF (structure).
  - Valid email.
  - Password with minimum 8 characters and matching confirmation.
  - Correct date of birth format (optional).
- Includes `functions.php` for utilities.

### `empreendedores/store.php`
- Main registration flow:
  1. Receives form data.
  2. Sanitizes and validates with `validate_empreendedor.php`.
  3. Queries CPF in ReceitaWS (prototype).
  4. Checks email and CPF uniqueness in DB.
  5. Inserts data into DB with hashed password.
  6. Sends welcome email.
  7. Keeps user on same page in case of error (inline alert or JavaScript).

---

flowchart TD
    A[Usuário preenche formulário] --> B[Validação Client-side (JavaScript)]
    B -->|Erros| B1[Mensagens inline nos campos]
    B -->|Tudo certo| C[Envio para store.php]

    C --> D[Sanitização dos dados (functions.php)]
    D --> E[Validação servidor (validate_empreendedor.php)]
    E -->|Erros| E1[Alert + mantém usuário na página]
    E -->|Tudo certo| F[Consulta CPF na ReceitaWS]

    F -->|Inválido ou situação != REGULAR| F1[Alert: CPF inválido ou irregular]
    F -->|REGULAR| G[Verificação unicidade (email/CPF no banco)]

    G -->|Duplicado| G1[Alert: já cadastrado]
    G -->|Novo| H[Inserção no banco com senha criptografada]

    H --> I[Envio de e-mail de boas-vindas (mail.php + email_template.php)]
    I --> J[Cadastro concluído com sucesso]

flowchart TD
    A[User fills form] --> B[Client-side validation (JavaScript)]
    B -->|Errors| B1[Inline error messages]
    B -->|Valid| C[Send to store.php]

    C --> D[Data sanitization (functions.php)]
    D --> E[Server-side validation (validate_empreendedor.php)]
    E -->|Errors| E1[Alert + keep user on page]
    E -->|Valid| F[CPF check via ReceitaWS]

    F -->|Invalid or status != REGULAR| F1[Alert: Invalid or irregular CPF]
    F -->|REGULAR| G[Uniqueness check (email/CPF in DB)]

    G -->|Duplicate| G1[Alert: already registered]
    G -->|New| H[Insert into DB with hashed password]

    H --> I[Send welcome email (mail.php + email_template.php)]
    I --> J[Registration completed successfully]


# Fluxo de Cadastro Negócios

    app/
 ├── config/
 │    └── db.php                  # Conexão com banco
 ├── helpers/
 │    ├── functions.php           # Funções utilitárias globais
 │    ├── mail.php                # Envio de e-mails
 │    └── email_template.php      # Template de e-mail
 ├── validators/
 │    ├── validate_empreendedor.php # Validação de empreendedores
 │    └── validate_negocio.php      # Validação dos dados do negócio (multi-etapas)
 └── models/
      ├── Empreendedor.php        # Classe/DAO para empreendedores
      └── Negocio.php             # Classe/DAO para negócios

negocios/
 ├── etapa1.php                   # Cadastro de fundadores
 ├── etapa2.php                   # Dados do negócio (ex.: nome fantasia, razão social)
 ├── etapa3.php                   # Eixo Temático
 ├── etapa4.php                   # Conexão com os ODS
 ├── etapa5.php                   # Apresentação do Negócio
 ├── etapa6.php                   # Dados Financeiros e Modelo de Receita  
 ├── etapa7.php                   # Avaliação de Impacto 
 ├── etapa8.php                   # Visão de Futuro do Empreendimento 
 ├── etapa9.php                   # Revisão e confirmação
 └── store.php                    # Controlador final que junta todas as etapas


 flowchart TD
    A[Início do cadastro do negócio] --> B[Etapa 1: Fundadores]
    B --> C[Etapa 2: Dados do negócio]
    C --> D[Etapa 3: Eixo Temático]
    D --> E[Etapa 4: Conexão com os ODS]
    E --> F[Etapa 5: Apresentação do Negócio]
    F --> G[Etapa 6: Dados Financeiros e Modelo de Receita]
    G --> H[Etapa 7: Avaliação de Impacto]
    H --> I[Etapa 8: Visão de Futuro]
    I --> J[Etapa 9: Revisão e confirmação]
    J --> K[store.php → Consolida e salva no banco]
    K --> L[Cadastro concluído com sucesso]


erDiagram
    EMPREENDEDORES {
        int id PK
        string nome
        string sobrenome
        string cpf
        string email
        string celular
        date data_nascimento
        string genero
        string cidade
        string estado
        string pais
        string formacao
        string etnia
    }

    NEGOCIOS {
        int id PK
        string nome_fantasia
        string razao_social
        string eixo_tematico
        string ods_conexao
        text apresentacao
        text modelo_receita
        text avaliacao_impacto
        text visao_futuro
        int etapa_atual
        int empreendedor_principal_id FK
    }

    NEGOCIOS_FUNDADORES {
        int id PK
        int negocio_id FK
        int empreendedor_id FK
        string tipo  // fundador ou cofundador
    }

    EMPREENDEDORES ||--o{ NEGOCIOS_FUNDADORES : "pode ser fundador/cofundador"
    NEGOCIOS ||--o{ NEGOCIOS_FUNDADORES : "pode ter até 5 fundadores"
    EMPREENDEDORES ||--o{ NEGOCIOS : "pode criar vários negócios"