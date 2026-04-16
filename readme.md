# Plataforma Impactos Positivos

Plataforma web para gestão de empreendedores de impacto social, negócios, parceiros e curadoria de uma vitrine pública. Desenvolvida em PHP com MySQL, Bootstrap 5 e PHPMailer.

- **Responsável:** Daniela Silva
- **Última atualização:** Abril 2026
- **Ambiente:** PHP 7.4+, MySQL 5.7 / MariaDB 10.3, HostGator

***

## Índice de Módulos

Cada módulo com documentação própria:

| Módulo | README | Descrição |
|--------|--------|-----------|
| `admin/` | [admin/README.md](admin/README.md) | Painel administrativo completo |
| `app/` | [app/README.md](app/README.md) | Helpers, models, services, validators, views |
| `empreendedores/` | [empreendedores/README.md](empreendedores/README.md) | Área logada do empreendedor e cadastro de negócios |
| `negocios/` | [negocios/README.md](negocios/README.md) | Formulário multi-etapas e blocos de visualização |
| `parceiros/` | [parceiros/README.md](parceiros/README.md) | Cadastro, Carta-Acordo e painel do parceiro |
| `sociedadecivil/` | [sociedadecivil/README.md](sociedadecivil/README.md) | Área logada da Sociedade Civil |

Módulos documentados neste arquivo: `auth/`, `cron/`, `scripts/`, premiação (admin), páginas públicas (raiz).

***

## Estrutura Geral de Diretórios

```
/
├── admin/                     # Painel administrativo
├── app/
│   ├── config/                # db.php, mail.php
│   ├── helpers/               # auth.php, functions.php, mail.php, scores.php, render.php, votacao.php, premiacaoauth.php, relatorioshelper.php, emailtemplate.php
│   ├── models/                # UserModel.php, Empreendedor.php, Negocio.php
│   ├── services/              # Database.php (Singleton PDO)
│   ├── validators/            # validateempreendedor.php, validatenegocio.php
│   └── views/                 # admin/, emails/, empreendedor/, forms/, partials/, public/, parceiros/, sociedade/
├── assets/                    # CSS, JS, imagens, ícones, ODS
├── auth/                      # Processamento de login/logout e cadastros públicos
├── cron/                      # Tarefas agendadas
├── empreendedores/            # Área logada do empreendedor
├── negocios/                  # Formulário de cadastro do negócio
├── parceiros/                 # Cadastro e área do parceiro
├── scripts/                   # Scripts utilitários manuais
├── sociedadecivil/            # Área logada da Sociedade Civil
├── storage/                   # Logs da aplicação
├── uploads/                   # Uploads de imagens (e-mails, negócios, parceiros)
├── vendor/                    # PHPMailer
├── index.php                  # Página inicial pública
├── login.php                  # Login de empreendedores e Sociedade Civil
├── admin-login.php            # Login exclusivo do painel admin
├── logout.php                 # Encerramento de sessão (todos os perfis)
├── cadastro.php               # Cadastro público — Sociedade Civil e Empreendedores
├── cadastrosucesso.php        # Confirmação de cadastro concluído
├── negocio.php                # Página pública do negócio (vitrine)
├── parceiros.php              # Vitrine pública de parceiros
├── perfilparceiro.php         # Perfil público de um parceiro específico
├── vitrinenacional.php        # Vitrine nacional de negócios
├── resetopcache.php           # Reset de OPcache (uso manual em produção)
└── sessiontest.php            # Debug de sessão (remover em produção)
```

***

## Diagrama Relacional do Banco de Dados

```
users
 └── empreendedores          (1:N via empreendedor_id)
      └── negocios            (1:N via empreendedor_id)
           ├── negocio_fundadores
           ├── negocio_subareas
           ├── negocio_ods
           ├── negocio_apresentacao
           ├── negocio_impacto
           ├── negocio_financeiro
           ├── negocio_mercado
           ├── negocio_visao
           ├── negocio_sustentabilidade
           ├── negocio_documentos / negocios_documentos
           └── scores_negocios (score_impacto, investimento, escala, geral)
 └── parceiros               (1:1 via user_id)
      ├── parceiro_contrato
      ├── parceiro_interesses
      └── parceiro_ods
premiacoes
 └── premiacao_fases         (1:N via premiacao_id)
      └── premiacao_inscricoes (N:N negocios × premiacoes)
eixostematicos
 └── subareas
ods                          (17 registros fixos)
email_templates              (slugs editáveis pelo admin)
usuarios_importacao          (rastreamento de importados via CSV)
importacao_log               (log de ações de importação)
```

***

## Autenticação e Roles

Toda proteção de rotas usa funções definidas em `app/helpers/auth.php`.

### Funções de Proteção

| Função | Acesso permitido |
|--------|-----------------|
| `requireAdminLogin()` | `admin` e `superadmin` |
| `requireSuperAdminLogin()` | Somente `superadmin` |
| `requireEmpreendedorLogin()` | Role `user` (empreendedor) |
| `requireParceiroLogin()` | Role `parceiro` com status `ativo` |
| `requireSociedadeLogin()` | Role `sociedade` |
| `isAdmin()` | Retorna true para `admin` ou `superadmin` |
| `isSuperAdmin()` | Retorna true somente para `superadmin` |

### Roles Disponíveis (`users.role`)

| Role | Perfil |
|------|--------|
| `superadmin` | Acesso total, incluindo ações destrutivas |
| `admin` | Painel admin sem ações superadmin |
| `juri` | Acesso restrito à avaliação técnica da premiação |
| `user` | Empreendedor — área logada + cadastro de negócios |
| `parceiro` | Parceiro — área logada + painel |
| `sociedade` | Sociedade Civil — área logada + votação |

### Proteções Implementadas

- Tokens CSRF em todos os formulários POST
- Senhas armazenadas com `password_hash(PASSWORD_DEFAULT)`
- Prepared statements PDO em todas as queries
- Sanitização com `htmlspecialchars()` em todas as saídas

***

## Módulo: auth/

Responsável pelo processamento de login, logout e cadastros públicos. Não possui interface própria — recebe dados via POST e redireciona após o processamento.

```
auth/
├── processarcadastrosociedade.php  # Processa cadastro público da Sociedade Civil (3 etapas)
└── (login e logout centralizados em login.php e logout.php na raiz)
```

### Fluxo de Login (todos os perfis)

1. Usuário submete `login.php` (empreendedor/sociedade) ou `admin-login.php` (admin)
2. Credenciais verificadas na tabela `users` via PDO prepared statement
3. `password_verify()` compara com o hash armazenado
4. Sessão iniciada com `usuario_id`, `role` e `nome`
5. Redirecionamento conforme role: `empreendedores/dashboard.php`, `sociedadecivil/minhaconta.php` ou `admin/dashboard.php`

### Fluxo de Cadastro — Sociedade Civil

1. Usuário preenche `/cadastro.php` (3 etapas em JavaScript)
2. POST enviado para `auth/processarcadastrosociedade.php`
3. Validação e sanitização via `app/helpers/functions.php`
4. Verificação de unicidade de e-mail e CPF
5. Inserção em `users` com `role = sociedade` e senha criptografada
6. Envio de e-mail de boas-vindas via `app/helpers/mail.php`
7. Redirecionamento para `cadastrosucesso.php`

### logout.php

Destrói a sessão ativa (`session_destroy()`) para qualquer perfil e redireciona para a página inicial ou de login correspondente.

***

## Módulo: cron/

Tarefas agendadas para execução periódica via cron job no servidor.

```
cron/
└── cronverificarvencimentos.php   # Verifica contratos de parceiros com vencimento próximo
```

### `cronverificarvencimentos.php`

Verifica parceiros com contratos próximos do vencimento (tabela `parceiro_contrato`) e dispara notificações automáticas por e-mail para os responsáveis.

**Configuração sugerida no crontab:**
```bash
# Executa diariamente às 08h
0 8 * * * /usr/bin/php /caminho/para/cron/cronverificarvencimentos.php >> /caminho/storage/logs/cron.log 2>&1
```

**O script:**
- Conecta ao banco via `app/config/db.php`
- Consulta parceiros com `data_fim_vigencia` nos próximos X dias
- Envia e-mail de aviso usando `app/helpers/mail.php`
- Registra resultado em `storage/logs/`

***

## Módulo: scripts/

Scripts utilitários para execução manual — não devem ser acessados via navegador em produção.

```
scripts/
├── cleanuppasswordresets.php   # Remove tokens de redefinição de senha expirados
└── createsuperadmin.php        # Cria o primeiro usuário superadmin no banco
```

### `createsuperadmin.php`

Cria um usuário `superadmin` diretamente no banco. Deve ser executado **uma única vez** na configuração inicial do ambiente e removido ou protegido após o uso.

### `cleanuppasswordresets.php`

Remove registros antigos da tabela de redefinição de senhas (tokens expirados). Pode ser agendado via cron para execução semanal.

***

## Módulo: Premiação (admin/)

O sistema de premiação é gerenciado inteiramente pelo painel admin. Os arquivos estão em `admin/` e documentados aqui por serem transversais à plataforma.

```
admin/
├── premiacaoedicoes.php      # CRUD de edições anuais do prêmio
├── premiacaoperiodos.php     # Gestão de fases/períodos de cada edição
└── minhasinscricoespremiacao.php  # (empreendedores/) Inscrições do empreendedor
```

### Tabelas do Sistema de Premiação

| Tabela | Conteúdo |
|--------|----------|
| `premiacoes` | Edições anuais: nome, slug, ano, status, datas de inscrição e votação |
| `premiacao_fases` | Fases de cada edição: nome, slug, datas, tipo de avaliação permitida |
| `premiacao_inscricoes` | Inscrições: `premiacao_id`, `negocio_id`, aceites, data |

### Status de uma Edição (`premiacoes.status`)

| Status | Descrição |
|--------|-----------|
| `planejada` | Configurada, ainda não iniciada |
| `ativa` | Em andamento — somente uma edição pode estar ativa por vez |
| `encerrada` | Período encerrado |
| `inativa` | Desativada sem encerramento formal |

> Ao salvar uma edição com status `ativa`, o sistema automaticamente define todas as outras como `inativa`.

### Status de uma Fase (`premiacao_fases.status`)

| Status | Descrição |
|--------|-----------|
| `rascunho` | Em criação, não visível |
| `agendada` | Configurada, aguardando início |
| `emandamento` | Dentro do período (calculado automaticamente pelas datas) |
| `encerrada` | Fora do período |
| `apurada` | Resultado apurado — status fixo, não recalculado automaticamente |

O sistema recalcula o status automaticamente com base nas datas de início e fim, **exceto** quando a fase está como `apurada`.

### Tipos de Avaliação por Fase

Cada fase pode habilitar independentemente:

- `permite_voto_popular` — votação aberta à Sociedade Civil
- `permite_avaliacao_tecnica` — seleção manual pelo admin
- `permite_juri_final` — votação pelo júri (role `juri`)

### Inscrição do Empreendedor

A inscrição é feita na tela de confirmação do negócio (`negocios/confirmacao.php`). O formulário de publicação exibe a premiação vigente (status `ativa` ou `planejada`) e permite:

- Marcar `deseja_participar`
- Aceitar o regulamento (`aceite_regulamento`)
- Declarar veracidade das informações (`aceite_veracidade`)

### `app/helpers/premiacaoauth.php`

Funções de controle de acesso específicas para o módulo de premiação, incluindo verificação de role `juri` e validação de fase ativa.

### `app/helpers/votacao.php`

Controla o registro de votos populares:
- Impede múltiplos votos do mesmo `user_id` por `edicao_id`
- Verifica se a fase ativa permite voto popular (`permite_voto_popular = 1`)

***

## App/Helpers — Referência Rápida

Todos os helpers ficam em `app/helpers/`. São incluídos com `require_once` nas páginas que os utilizam.

| Arquivo | Funções principais |
|---------|--------------------|
| `auth.php` | `requireAdminLogin()`, `requireEmpreendedorLogin()`, `requireParceiroLogin()`, `requireSociedadeLogin()`, `isAdmin()`, `isSuperAdmin()` |
| `functions.php` | Sanitização de inputs, validação de CPF, formatação de datas, utilitários gerais |
| `mail.php` | `sendMail($to, $subject, $body)` — envio via PHPMailer com configuração de `app/config/mail.php` |
| `render.php` | `renderEmailTemplate($slug, $vars)` — renderiza template do banco; `renderEmailFromDb($templateId, $vars)` |
| `scores.php` | `calcularScore($negocioId, $pdo)` — calcula score por dimensão (impacto, investimento, escala) e salva em `scores_negocios` |
| `emailtemplate.php` | Template HTML estático de boas-vindas (usado antes do sistema de templates dinâmicos) |
| `premiacaoauth.php` | Controle de acesso para rotas da premiação e validação de fase ativa |
| `votacao.php` | Registro e validação de votos populares |
| `relatorioshelper.php` | Funções auxiliares para geração dos gráficos de relatórios (`Chart.js`) |

***

## Páginas Públicas (Raiz)

Acessíveis sem autenticação.

| Arquivo | Descrição |
|---------|-----------|
| `index.php` | Página inicial — apresentação da plataforma |
| `login.php` | Login de empreendedores e Sociedade Civil |
| `admin-login.php` | Login exclusivo do painel admin |
| `cadastro.php` | Cadastro público (Sociedade Civil — 3 etapas; empreendedores — redirecionado) |
| `cadastrosucesso.php` | Confirmação pós-cadastro |
| `negocio.php` | Página pública de um negócio (vitrine individual) |
| `parceiros.php` | Vitrine pública de parceiros ativos |
| `perfilparceiro.php` | Perfil público de um parceiro específico |
| `vitrinenacional.php` | Vitrine nacional — listagem e filtros de negócios publicados |
| `logout.php` | Encerramento de sessão — todos os perfis |

***

## Assets

```
assets/
├── css/
│   ├── style.css          # Estilo público (vitrine, login, cadastro) — 99KB
│   ├── empreendedor.css   # Estilo da área do empreendedor — 49KB
│   └── admin.css          # Estilo do painel admin — 39KB
├── js/
│   ├── scripts.js         # Scripts gerais
│   └── chartgraficos.js   # Inicialização dos gráficos Chart.js
├── images/
│   ├── *.png / *.svg      # Logos, ícones de eixos temáticos, imagens de fundo
│   └── img-ods/           # Ícones dos 17 ODS (01.png a 17.png)
└── tinymce/               # Editor TinyMCE (self-hosted)
```

***

## Uploads

```
uploads/
├── emailimages/           # Imagens inseridas via editor TinyMCE nos templates de e-mail
├── negocios/
│   ├── logos/             # Logos dos negócios
│   ├── galeria/           # Galeria de imagens dos negócios
│   └── documentos/        # Documentos da etapa 9
└── parceiros/             # Uploads relacionados a parceiros
```

***

## Requisitos do Ambiente

- PHP 7.4+
- MySQL 5.7 / MariaDB 10.3
- OpenSSL habilitado
- Saída SMTP liberada no servidor
- PHPMailer instalado em `vendor/phpmailer/`
- Bootstrap 5 + Bootstrap Icons (CDN)
- TinyMCE self-hosted em `assets/tinymce/`
- Chart.js (CDN) para relatórios

***

## Segurança — Boas Práticas

- **Nunca versionar** `app/config/db.php` e `app/config/mail.php` com credenciais reais
- Usar `.gitignore` para excluir arquivos de configuração sensíveis
- Rotacionar senha SMTP se exposta
- **Remover** `sessiontest.php` e arquivos `*debug.php` do ambiente de produção
- `scripts/createsuperadmin.php` deve ser removido ou protegido após o primeiro uso

# 📋 Regras de Negócio — Módulo de Premiação
> Plataforma Impactos Positivos · Última atualização: 2026-04-15

---

## 1. Estrutura de Tabelas

| Tabela                        | Responsabilidade                                                  |
|-------------------------------|-------------------------------------------------------------------|
| `premiacoes`                  | Edições da premiação (nome, ano, status)                         |
| `premiacao_categorias`        | Categorias por edição (Ideação, Operação, Tração/Escala etc.)    |
| `premiacao_fases`             | Fases com datas, permissões e cotas de classificação             |
| `premiacao_inscricoes`        | Inscrições dos negócios em cada edição                           |
| `premiacao_votos_populares`   | Votos do público por inscrição e fase                            |
| `premiacao_votos_juri`        | Votos dos jurados na fase final                                  |
| `premiacao_selecoes_admin`    | Seleções técnicas feitas pelo admin                              |
| `premiacao_apuracao_fase`     | Snapshot de apuração por fase/categoria/fonte                    |
| `premiacao_classificados_fase`| Classificados finais por fase com origem e posição               |
| `premiacao_resultados_fase`   | Pontuação consolidada por fase                                   |
| `premiacao_resultados_finais` | Resultado final publicável com vencedor por categoria            |

---

## 2. Tipos de Fase (`premiacao_fases.tipo_fase`)

| tipo_fase            | Voto popular | Avaliação técnica | Júri | Exemplo de uso              |
|----------------------|:---:|:---:|:---:|-----------------------------|
| `inscricoes`         | ✗ | ✗ | ✗ | Período de inscrição         |
| `triagem_documental` | ✗ | ✗ | ✗ | Validação documental         |
| `classificatoria`    | ✓ | ✓ | ✗ | Classificatória 1 e 2        |
| `final`              | ✓ | ✗ | ✓ | Fase final                   |
| `resultado`          | ✗ | ✗ | ✗ | Consolidação e publicação    |

Os campos `permite_voto_popular`, `permite_avaliacao_tecnica` e `permite_juri_final`
controlam isso diretamente em `premiacao_fases`.

---

## 3. Cotas de Classificação por Fase

Cada fase guarda suas cotas nos campos:
premiacao_fases.qtd_classificados_popular → quantos vêm do voto popular
premiacao_fases.qtd_classificados_tecnica → quantos vêm da avaliação técnica
premiacao_fases.qtd_classificados_final → total final esperado por categoria

text

### Configuração padrão para uma edição

| Fase              | Popular | Técnica | Total final |
|-------------------|:-------:|:-------:|:-----------:|
| Classificatória 1 | 10      | 10      | 20          |
| Classificatória 2 | 3       | 3       | 6           |
| Fase final        | 1       | —       | 1 por cat.  |

---

## 4. Regra de Apuração por Fase (Classificatórias)

A apuração **é feita por categoria** e segue este algoritmo:
Buscar top N do voto popular → ranking por total de votos DESC

Buscar top N da avaliação técnica → ranking por nota_tecnica DESC

Unir os dois conjuntos (union, não intersecção)

Remover duplicados (negócio que aparece nos dois entra uma única vez)

Se total < qtd_classificados_final:
Puxar os próximos colocados do ranking correspondente,
na ordem: popular primeiro, depois técnica,
pulando quem já está no conjunto.
Repetir até completar ou esgotar elegíveis.

text

### Exemplo — Categoria Ideação, Classificatória 1
Top 10 popular: A B C D E F(=EducaTec) G H I J
Top 10 técnica: X Y F(=EducaTec) W V U T S R Q

União: A B C D E F G H I J X Y W V U T S R Q → 19 únicos
Falta 1 vaga → puxa 11º do popular (K), se K não estiver na técnica
Resultado final: 20 negócios classificados

text

O campo `origem_classificacao` em `premiacao_classificados_fase` registra:
- `popular` — veio apenas do ranking popular
- `tecnica` — veio apenas da avaliação técnica
- `ambos` — aparecia nos dois rankings (deduplicado)
- `repescagem` — entrou para completar a cota

---

## 5. Critérios de Desempate

Guardado em `premiacao_fases.criterio_desempate`. Ordem aplicada:

1. **`nota_tecnica`** — maior nota na avaliação técnica
2. **`votos_populares`** — maior contagem de votos do público
3. **`data_inscricao`** — data/hora mais antiga em `premiacao_inscricoes.created_at`
4. **`decisao_admin`** — intervenção administrativa registrada em `premiacao_selecoes_admin`

---

## 6. Regra da Fase Final

Cada finalista recebe até **5 pontos**:

| Origem              | Pontos | Campo                                          |
|---------------------|:------:|------------------------------------------------|
| Voto popular final  | 1      | `premiacao_resultados_fase.ponto_voto_popular` |
| Voto de jurado 1    | 1      | `premiacao_votos_juri`                         |
| Voto de jurado 2    | 1      | `premiacao_votos_juri`                         |
| Voto de jurado 3    | 1      | `premiacao_votos_juri`                         |
| Voto de jurado 4    | 1      | `premiacao_votos_juri`                         |
| **Total máximo**    | **5**  | `premiacao_resultados_finais.total_pontos`     |

- `ponto_voto_popular = 1` se o negócio for o mais votado pelo público na categoria
- Cada jurado vota **uma vez por categoria** (`uk_voto_juri_unico` garante isso)
- Vence quem tiver **maior `total_pontos`**
- Empate na final → aplicar critérios da seção 5 acima

---

## 7. Status de Inscrição (`premiacao_inscricoes.status`)

| Valor                | Significado                                              |
|----------------------|----------------------------------------------------------|
| `rascunho`           | Inscrição iniciada, não enviada                          |
| `enviada`            | Empreendedor confirmou e enviou                          |
| `em_triagem`         | Admin está analisando documentação                       |
| `elegivel`           | Aprovado na triagem — entra nas fases de votação         |
| `inelegivel`         | Reprovado na triagem — fora da premiação                 |
| `classificada_fase_1`| Passou para a Classificatória 2                          |
| `classificada_fase_2`| Passou para a Fase Final                                 |
| `finalista`          | Está na Fase Final                                       |
| `vencedora`          | Vencedor da categoria                                    |
| `eliminada`          | Eliminado em alguma fase                                 |

---

## 8. Status de Fase (`premiacao_fases.status`)

| Valor         | Significado                                |
|---------------|--------------------------------------------|
| `rascunho`    | Em configuração, invisível ao público      |
| `agendada`    | Configurada, ainda não iniciou             |
| `em_andamento`| Período ativo — votação/avaliação aberta   |
| `encerrada`   | Período encerrado, aguardando apuração     |
| `apurada`     | Apuração concluída e resultado registrado  |

---

## 9. Quem pode votar (`premiacao_votos_populares`)

O campo `tipo_eleitor` define o perfil do votante:

| Valor            | Descrição                    |
|------------------|------------------------------|
| `empreendedor`   | Usuário logado como fundador |
| `parceiro`       | Parceiro cadastrado          |
| `sociedade_civil`| Usuário da sociedade civil   |

A UNIQUE KEY impede voto duplicado:
```sql
UNIQUE KEY (fase_id, inscricao_id, tipo_eleitor, eleitor_id)
```
Ou seja: **um voto por pessoa por negócio por fase**.

---

## 10. Fluxo completo resumido
premiacoes
└─► premiacao_categorias (categorias da edição)
└─► premiacao_fases (fases com datas e cotas)
└─► premiacao_inscricoes (negócios inscritos + status)
└─► premiacao_votos_populares (votos do público)
└─► premiacao_selecoes_admin (seleções técnicas)
└─► premiacao_votos_juri (votos dos jurados)
└─► premiacao_classificados_fase (resultado da apuração)
└─► premiacao_resultados_fase (pontuação por fase)
└─► premiacao_resultados_finais (vencedores publicáveis)

text

---

> **Arquivo de referência do schema:** `scripts/schema_premiacao.sql`