# 📦 Módulo App — Core (`app/`)

Núcleo da plataforma. Contém configurações, helpers, validators, models, services e views compartilhadas entre o painel admin e as áreas públicas.

***

## 📁 Estrutura

```
app/
├── config/
│   ├── db.php              ← Credenciais de conexão MySQL
│   └── mail.php            ← Configurações SMTP
├── helpers/
│   ├── auth.php            ← Funções de autenticação e roles
│   ├── functions.php       ← Sanitização, validação de CPF/CNPJ, utilitários
│   ├── mail.php            ← Envio de e-mail via PHPMailer (send_mail)
│   ├── render.php          ← render_email_template / render_email_from_db
│   ├── scores.php          ← calcularScore() por dimensão
│   └── email_template.php  ← Template HTML padrão de boas-vindas
├── validators/
│   ├── validate_empreendedor.php  ← Validação do cadastro de empreendedor
│   └── validate_negocio.php       ← Validação multi-etapas do negócio
├── models/
│   ├── UserModel.php       ← CRUD de usuários admin
│   ├── Empreendedor.php    ← DAO de empreendedores
│   └── Negocio.php         ← DAO de negócios
├── services/
│   └── Database.php        ← Singleton PDO (Database::getInstance())
└── views/
    ├── admin/
    │   ├── header.php      ← Header do painel admin (Bootstrap 5 + BI)
    │   └── footer.php      ← Footer + scripts do painel admin
    ├── emails/
    │   ├── new_user.php          ← Template HTML: novo usuário admin
    │   ├── new_user.txt.php      ← Template texto: novo usuário admin
    │   ├── novo_empreendedor.php ← Template HTML: novo empreendedor
    │   └── new_empreendedor.txt.php ← Template texto: novo empreendedor
    └── public/
        ├── header_public.php   ← Header público (Bootstrap + Select2)
        └── footer_public.php   ← Footer público
```

***

## ⚙️ config/

### `db.php`
Credenciais de conexão com o banco MySQL. Cria a variável `$pdo` (PDO) utilizada em todos os módulos.

> ⚠️ **Nunca versionar** este arquivo com credenciais reais. Adicionar ao `.gitignore`.

### `mail.php`
Configurações SMTP: host, porta, usuário, senha, remetente padrão. Utilizado pelo helper `app/helpers/mail.php`.

> ⚠️ **Nunca versionar** este arquivo com credenciais reais.

***

## 🛠️ helpers/

### `auth.php`
Funções de controle de acesso e sessão.

| Função | Descrição |
|--------|-----------|
| `require_admin_login()` | Redireciona para login se não há sessão admin ativa |
| `is_admin()` | Retorna `true` se role for `admin` ou `superadmin` |
| `is_superadmin()` | Retorna `true` somente para `superadmin` |
| `require_empreendedor_login()` | Redireciona para login se não há sessão de empreendedor ativa |

***

### `functions.php`
Utilitários gerais da plataforma.

| Função | Descrição |
|--------|-----------|
| `sanitize_text($str)` | Remove tags HTML e caracteres especiais |
| `isValidCPF($cpf)` | Valida CPF (11 dígitos, verificação de dígitos) |
| `isValidCNPJ($cnpj)` | Valida CNPJ (14 dígitos, verificação de dígitos) |
| `formatar_data($date)` | Formata datas do banco (`Y-m-d`) para exibição (`d/m/Y`) |

***

### `mail.php`
Wrapper do PHPMailer.

```php
send_mail(string $to, string $name, string $subject, string $body, array $headers = [])
```

- Usa as configurações de `app/config/mail.php`
- Envia e-mail em formato HTML com fallback texto
- Lança exceção em caso de falha (capturada pelos controladores)

***

### `render.php`
Renderiza templates de e-mail com substituição de variáveis.

| Função | Descrição |
|--------|-----------|
| `render_email_template($template, $vars)` | Substitui `{{variavel}}` no template HTML por valores do array `$vars` |
| `render_email_from_db($slug, $vars, $pdo)` | Busca template por slug na tabela `email_templates` e aplica variáveis |

***

### `scores.php`
Cálculo de scores de negócios por dimensão.

```php
calcularScore(int $negocio_id, string $dimensao, PDO $pdo): int
```

**Dimensões disponíveis:** `impacto` · `investimento` · `escala`

Consulta as tabelas `pesos_scores` e `lookup_scores` para calcular o score ponderado. O score geral é calculado em `admin/recalcular_scores.php`:

```
score_geral = 40% × score_impacto + 30% × score_investimento + 30% × score_escala
```

***

### `email_template.php`
Template HTML padrão de boas-vindas utilizado no cadastro de empreendedores e criação manual pelo admin.

***

## ✅ validators/

### `validate_empreendedor.php`

```php
validar_empreendedor(array $data): array
```

Retorna um array de erros indexado por campo. Valida:
- Campos obrigatórios: nome, sobrenome, e-mail, CPF, celular, data de nascimento, gênero, cidade, estado
- Formato de e-mail
- CPF (via `isValidCPF()`)
- Termos de uso
- Confirmação de senha
- Campos condicionais: `formacao` e `etnia` obrigatórios se `eh_fundador = 'Sim'`

***

### `validate_negocio.php`

```php
validar_negocio_etapa(int $etapa, array $data): array
```

Validação modular por etapa (1 a 9). Retorna array de erros por campo.

***

## 🗃️ models/

### `UserModel.php`
CRUD completo para a tabela `users` (usuários admin).

| Método | Descrição |
|--------|-----------|
| `create(array $data)` | Insere novo usuário admin |
| `update(int $id, array $data)` | Atualiza dados do usuário |
| `delete(int $id)` | Remove usuário (soft delete via status `excluido`) |
| `findById(int $id)` | Busca usuário por ID |
| `findByEmail(string $email)` | Busca usuário por e-mail (login) |
| `listAll(array $filters)` | Lista paginada com filtros |

***

### `Empreendedor.php`
DAO para a tabela `empreendedores`.

| Método | Descrição |
|--------|-----------|
| `create(array $data)` | Insere novo empreendedor |
| `update(int $id, array $data)` | Atualiza dados |
| `findById(int $id)` | Busca por ID |
| `findByEmail(string $email)` | Busca por e-mail |
| `listAll(array $filters)` | Lista paginada com filtros |

***

### `Negocio.php`
DAO para a tabela `negocios` e tabelas relacionadas.

| Método | Descrição |
|--------|-----------|
| `findById(int $id)` | Busca negócio com todas as etapas |
| `listByEmpreendedor(int $empreendedor_id)` | Lista negócios do empreendedor |
| `updateStatus(int $id, string $status)` | Atualiza status / aprovação da vitrine |

***

## 🔌 services/

### `Database.php`
Implementa o padrão Singleton para a conexão PDO.

```php
$pdo = Database::getInstance();
```

Garante que apenas uma conexão com o banco seja aberta por requisição.

***

## 🖼️ views/

### `admin/header.php` e `admin/footer.php`
Layout base do painel admin. Incluídos no topo e rodapé de cada página do módulo `admin/`.

- Bootstrap 5 + Bootstrap Icons
- Menu de navegação lateral com atalhos por módulo
- Exibe nome e role do admin logado

### `emails/`
Templates de e-mail em PHP para montagem e envio. Separados em `.php` (HTML) e `.txt.php` (texto plano).

### `public/header_public.php` e `public/footer_public.php`
Layout base das páginas públicas (`empreendedores/`, `negocios/`, área de parceiros).

- Bootstrap 5 + Select2
- Header sem autenticação obrigatória