# 🌱 Plataforma Impactos Positivos

Plataforma web para gestão de empreendedores de impacto social, negócios, parceiros e curadoria de uma vitrine pública. Desenvolvida em PHP com MySQL, Bootstrap 5 e PHPMailer.

> **Responsável:** Daniela
> **Última atualização:** Março/2026
> **Referência técnica:** Copilot – PIP 2026

---

## 📁 Estrutura de Diretórios

```
/
├── admin/                          ← Painel administrativo (módulo atual)
│   ├── dashboard.php               ← KPIs e visão geral
│   ├── administradores.php         ← Gestão de usuários admin
│   ├── create_user.php             ← Criar usuário admin
│   ├── edit_user.php               ← Editar usuário admin
│   ├── usuarios.php                ← Lista de usuários (empreendedores)
│   ├── empreendedores.php          ← Lista e filtros de empreendedores
│   ├── create_empreendedor.php     ← Criar empreendedor manualmente
│   ├── editar_empreendedor.php     ← Editar empreendedor (superadmin)
│   ├── excluir_empreendedor.php    ← Exclusão em cascata (superadmin)
│   ├── reset_email.php             ← Alterar e-mail + senha temporária
│   ├── reset_password.php          ← Resetar senha com envio por e-mail
│   ├── importar_empreendedores.php ← Importação em lote via CSV
│   ├── importar_negocios.php       ← Importação de negócios via CSV
│   ├── atribuir_negocio.php        ← Atribuir negócio da base legada a empreendedor
│   ├── negocios.php                ← Lista, filtros e scores de negócios
│   ├── visualizar_negocio.php      ← Visualização completa (todas as etapas)
│   ├── aprovar_negocio.php         ← Aprovar e publicar na vitrine
│   ├── recalcular_scores.php       ← Recalcular scores de todos os negócios
│   ├── relatorios_negocios.php     ← Gráficos e relatórios (Chart.js)
│   ├── parceiros.php               ← Lista e gestão de parceiros
│   ├── visualizar_parceiro.php     ← Dados completos + contrato do parceiro
│   ├── visualizar_carta_parceiro.php ← Carta-Acordo assinada (printável)
│   ├── processar_status_parceiro.php ← Alterar status + e-mail de aprovação
│   ├── email_templates.php         ← Editor e preview de templates de e-mail
│   ├── enviar_email_status.php     ← Envio por status de engajamento
│   ├── enviar_email_negocios_pendentes.php ← Notificar inscrições incompletas
│   ├── gerenciar_notificacoes.php  ← Classe de notificações e rastreamento
│   ├── upload_image.php            ← Upload de imagens para e-mails (TinyMCE)
│   ├── users_db_debug.php          ← Debug de conexão com tabela users
│   └── users_model_debug.php       ← Debug do UserModel
│
├── app/
│   ├── config/
│   │   ├── db.php                  ← Credenciais de conexão MySQL
│   │   └── mail.php                ← Configurações SMTP
│   ├── helpers/
│   │   ├── auth.php                ← Funções de autenticação e roles
│   │   ├── functions.php           ← Sanitização, validação de CPF, utilitários
│   │   ├── mail.php                ← Envio de e-mail via PHPMailer (send_mail)
│   │   ├── render.php              ← render_email_template / render_email_from_db
│   │   ├── scores.php              ← calcularScore() por dimensão
│   │   └── email_template.php      ← Template HTML de boas-vindas
│   ├── validators/
│   │   ├── validate_empreendedor.php ← Validação do cadastro de empreendedor
│   │   └── validate_negocio.php    ← Validação multi-etapas do negócio
│   ├── models/
│   │   ├── UserModel.php           ← CRUD de usuários admin
│   │   ├── Empreendedor.php        ← DAO de empreendedores
│   │   └── Negocio.php             ← DAO de negócios
│   ├── services/
│   │   └── Database.php            ← Singleton PDO (Database::getInstance())
│   └── views/
│       ├── admin/
│       │   ├── header.php          ← Header do painel admin (Bootstrap 5 + BI)
│       │   └── footer.php          ← Footer + scripts do painel admin
│       ├── emails/
│       │   ├── new_user.php        ← Template HTML: novo usuário admin
│       │   ├── new_user.txt.php    ← Template texto: novo usuário admin
│       │   ├── novo_empreendedor.php ← Template HTML: novo empreendedor
│       │   └── new_empreendedor.txt.php ← Template texto: novo empreendedor
│       └── public/
│           ├── header_public.php   ← Header público (Bootstrap + Select2)
│           └── footer_public.php   ← Footer público
│
├── empreendedores/
│   ├── register.php                ← Formulário de cadastro público
│   ├── store.php                   ← Processamento do cadastro (POST)
│   └── sucesso.php                 ← Confirmação de cadastro
│
├── negocios/
│   ├── etapa1.php … etapa9.php     ← Formulário multi-etapas do negócio
│   ├── store.php                   ← Controlador final (consolida todas as etapas)
│   └── blocos-cadastros/           ← Blocos de visualização por etapa
│       ├── _shared.php
│       ├── bloco_etapa1.php … bloco_etapa9.php
│
├── vendor/
│   └── phpmailer/src/
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
│
├── uploads/
│   └── email_images/               ← Imagens enviadas pelo editor TinyMCE
│
└── README.md
```

---

## 🗄️ Diagrama Relacional (Banco de Dados)

```
users                          ← Usuários admin (admin / superadmin / juri)
    │
    └── (separado)

empreendedores                 ← Responsáveis pela inscrição
    │ 1:N
    ├── negocios               ← Cada empreendedor pode ter vários negócios
    │       │ 1:N
    │       ├── negocio_fundadores     ← Fundadores e cofundadores (até 5)
    │       ├── negocio_subareas       ← Eixos temáticos e subáreas
    │       ├── negocio_ods            ← ODS relacionadas
    │       ├── negocio_apresentacao   ← Descrição, vídeos, galeria, inovação
    │       ├── negocio_impacto        ← Intencionalidade, públicos, indicadores
    │       ├── negocio_financeiro     ← Faturamento, fontes de receita
    │       ├── negocio_mercado        ← Modelo de negócio, mercado
    │       ├── negocio_visao          ← Visão de futuro
    │       ├── negocio_sustentabilidade
    │       ├── negocio_documentos
    │       ├── negocios_documentos    ← Docs da etapa 9
    │       └── scores_negocios        ← score_impacto / investimento / escala / geral
    │
    └── negocio_fundadores (por empreendedor_id)

parceiros
    ├── parceiro_contrato      ← Carta-Acordo: tipos, natureza, vigência, escopo
    ├── parceiro_interesses    ← Matchmaking: eixos, ODS, setores, maturidade
    └── parceiro_ods           ← ODS de interesse do parceiro

email_templates                ← Templates editáveis (slug, subject, body_html)
eixos_tematicos                ← Tabela de eixos
subareas                       ← Subáreas por eixo
ods                            ← 17 ODS com ícones
usuarios_importacao            ← Rastreamento de empreendedores importados
importacao_log                 ← Log de ações de importação
```

---

## 🧩 Módulos do Painel Admin

### 👥 Gestão de Usuários Admin
| Arquivo | Descrição |
|---|---|
| `administradores.php` | Lista paginada com busca, exclusão e troca rápida de status |
| `create_user.php` | Cria usuário, gera senha automática e envia e-mail de acesso |
| `edit_user.php` | Edita nome, e-mail, role e status; redireciona após salvar |

**Roles disponíveis:** `user` · `admin` · `superadmin`
**Status disponíveis:** `ativo` · `pendente` · `suspenso` · `inativo` · `excluido`
> ⚠️ Apenas `superadmin` pode marcar status como `inativo`.

---

### 🧑‍💼 Gestão de Empreendedores
| Arquivo | Descrição |
|---|---|
| `empreendedores.php` | Lista filtrada por nome, e-mail e status; paginação inteligente |
| `create_empreendedor.php` | Cria empreendedor com senha provisória e envia e-mail |
| `editar_empreendedor.php` | Edição restrita a `superadmin` |
| `excluir_empreendedor.php` | Exclusão em cascata: remove negócios e todos os dados relacionados |
| `reset_email.php` | Altera e-mail e envia nova senha temporária |
| `reset_password.php` | Gera e envia senha temporária para o e-mail atual |
| `importar_empreendedores.php` | Importação via CSV (`;`): insere novos, atualiza existentes, vincula negócios legados |

**Estrutura do CSV de empreendedores (separado por `;`):**
```
nome ; sobrenome ; cpf ; email ; celular ; data_nascimento ; genero ; cargo ; formacao
```

---

### 💼 Gestão de Negócios
| Arquivo | Descrição |
|---|---|
| `negocios.php` | Lista com filtros, scores, ordenação dinâmica e status da vitrine |
| `visualizar_negocio.php` | Visualização completa de todas as 9 etapas do negócio |
| `aprovar_negocio.php` | Aprova, publica na vitrine e notifica o empreendedor por e-mail |
| `recalcular_scores.php` | Recalcula scores de todos os negócios e salva em `scores_negocios` |
| `relatorios_negocios.php` | Gráficos Chart.js: categoria, estado, eixo, ODS, modelo, scores médios |
| `atribuir_negocio.php` | Interface com autocomplete para migrar negócios da base legada (ID 17) |
| `importar_negocios.php` | Importação via CSV: detecta delimitador, insere ou atualiza por nome+CNPJ |

**Estrutura do CSV de negócios (`,` ou `;`):**
```
email ; nome_fantasia ; razao_social ; categoria ; cnpj ; data_fundacao
```

**Fórmula de score geral:**
```
score_geral = 40% × score_impacto + 30% × score_investimento + 30% × score_escala
```

**Fluxo do cadastro de negócios (9 etapas):**
```
Etapa 1: Fundadores
Etapa 2: Dados do negócio
Etapa 3: Eixo Temático
Etapa 4: Conexão com ODS
Etapa 5: Apresentação
Etapa 6: Dados Financeiros e Modelo de Receita
Etapa 7: Avaliação de Impacto
Etapa 8: Visão de Futuro
Etapa 9: Revisão e confirmação → store.php
```

---

### 🤝 Gestão de Parceiros
| Arquivo | Descrição |
|---|---|
| `parceiros.php` | Lista com filtros e modal de alteração de status |
| `visualizar_parceiro.php` | Dados completos: organização, representante, contrato, interesses, ODS |
| `visualizar_carta_parceiro.php` | Carta-Acordo assinada digitalmente (printável / PDF) |
| `processar_status_parceiro.php` | Atualiza status; ao aprovar, envia e-mail de boas-vindas ao parceiro |

**Status do parceiro:** `em_cadastro` · `analise` · `ativo` · `inativo`

---

### 📧 E-mails e Templates
| Arquivo | Descrição |
|---|---|
| `email_templates.php` | Editor TinyMCE com preview e envio de teste para o admin logado |
| `enviar_email_status.php` | Envia e-mail segmentado por status: `ausente`, `desengajado`, `inativo` |
| `enviar_email_negocios_pendentes.php` | Notifica empreendedores com `inscricao_completa = 0` |
| `upload_image.php` | Upload de imagens para o editor TinyMCE |
| `gerenciar_notificacoes.php` | Classe `GerenciadorNotificacoes`: envio individual, em lote, rastreamento e relatório |

**Variáveis disponíveis nos templates:**
| Variável | Valor substituído |
|---|---|
| `{{nome}}` | Nome do empreendedor |
| `{{email}}` | E-mail do empreendedor |
| `{{dias}}` | Dias desde o último login |
| `{{nome_fantasia}}` | Nome fantasia do negócio |
| `{{etapa_atual}}` | Etapa atual do cadastro |
| `{{ano}}` | Ano atual |
| `{{link_vitrine}}` | URL dinâmica da vitrine |
| `{{link_painel}}` | URL do painel do parceiro |

---

### 📊 Dashboard
Arquivo `dashboard.php` exibe KPIs em tempo real:
- Total de empreendedores e empreendedores ativos
- Total de negócios: concluídos / em andamento / encerrados
- Últimos 5 logins de empreendedores
- Atalhos rápidos para todos os módulos

---

## 🔐 Autenticação e Roles

Toda rota admin requer `require_admin_login()` via `app/helpers/auth.php`.

| Função | Acesso |
|---|---|
| `require_admin_login()` | Todos os admins |
| `is_admin()` | `admin` ou `superadmin` |
| `is_superadmin()` | Somente `superadmin` |

**Proteções implementadas:**
- Tokens CSRF em todos os formulários POST
- Senhas armazenadas com `password_hash()` / `PASSWORD_DEFAULT`
- Prepared statements PDO em todas as queries
- Sanitização com `htmlspecialchars()` em todas as saídas

---

## 📬 Fluxo de Cadastro — Empreendedor

```
1. Usuário preenche register.php
2. Validação client-side (JavaScript)
3. store.php recebe os dados
4. Sanitização (functions.php)
5. Validação server-side (validate_empreendedor.php)
6. Consulta CPF na ReceitaWS (protótipo)
7. Verifica unicidade de e-mail e CPF no banco
8. Insere no banco com senha criptografada
9. Envia e-mail de boas-vindas (mail.php + email_template.php)
10. Redireciona para sucesso.php
```

---

## ✅ Requisitos

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- OpenSSL habilitado
- Saída SMTP liberada no servidor
- PHPMailer (instalado manualmente em `vendor/phpmailer/`)
- Bootstrap 5 + Bootstrap Icons
- TinyMCE (editor de templates de e-mail)
- Chart.js (relatórios gráficos)

---

## 🔒 Segurança

- **Nunca versionar** `app/config/db.php` e `app/config/mail.php` com credenciais reais
- Usar `.gitignore` para excluir arquivos de configuração sensíveis
- Rotacionar senha SMTP se exposta
- Remover arquivos `*_debug.php` do ambiente de produção
- Senha padrão da importação CSV (`Mudar@1234`) deve ser comunicada ao empreendedor para troca no primeiro acesso

---

## 📂 Módulos a documentar (próximos envios)

- [ ] `empreendedores/` — Cadastro público multi-etapas
- [ ] `negocios/` — Formulário de 9 etapas + blocos de visualização
- [ ] `parceiros/` — Fluxo de cadastro e carta-acordo
- [ ] `app/helpers/` — Helpers de scores, render, auth
- [ ] `app/models/` — UserModel, Empreendedor, Negocio
- [ ] Tabelas SQL completas (schema)
- [ ] Configuração de ambiente (.env ou config)

---

