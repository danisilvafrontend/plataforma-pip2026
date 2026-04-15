# 🔧 Módulo Admin (`admin/`)

Painel administrativo da Plataforma Impactos Positivos. Contém todas as telas de gestão acessíveis apenas por usuários com role `admin` ou `superadmin`.

> **Autenticação:** Toda rota deste módulo requer `require_admin_login()` via `app/helpers/auth.php`.

***

## 📋 Arquivos do Módulo

### 👥 Gestão de Usuários Admin

| Arquivo | Descrição |
|---------|-----------|
| `administradores.php` | Lista paginada com busca, exclusão e troca rápida de status |
| `create_user.php` | Cria usuário admin, gera senha automática e envia e-mail de acesso |
| `edit_user.php` | Edita nome, e-mail, role e status; redireciona após salvar |

**Roles disponíveis:** `user` · `admin` · `superadmin`  
**Status disponíveis:** `ativo` · `pendente` · `suspenso` · `inativo` · `excluido`

> ⚠️ Apenas `superadmin` pode marcar status como `inativo`.

***

### 🧑‍💼 Gestão de Empreendedores

| Arquivo | Descrição |
|---------|-----------|
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

***

### 💼 Gestão de Negócios

| Arquivo | Descrição |
|---------|-----------|
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

***

### 🤝 Gestão de Parceiros

| Arquivo | Descrição |
|---------|-----------|
| `parceiros.php` | Lista com filtros e modal de alteração de status |
| `visualizar_parceiro.php` | Dados completos: organização, representante, contrato, interesses, ODS |
| `visualizar_carta_parceiro.php` | Carta-Acordo assinada digitalmente (printável / PDF) |
| `processar_status_parceiro.php` | Atualiza status; ao aprovar, envia e-mail de boas-vindas ao parceiro |

**Status do parceiro:** `em_cadastro` · `analise` · `ativo` · `inativo`

***

### 📧 E-mails e Notificações

| Arquivo | Descrição |
|---------|-----------|
| `email_templates.php` | Editor TinyMCE com preview e envio de teste para o admin logado |
| `enviar_email_status.php` | Envia e-mail segmentado por status: `ausente`, `desengajado`, `inativo` |
| `enviar_email_negocios_pendentes.php` | Notifica empreendedores com `inscricao_completa = 0` |
| `gerenciar_notificacoes.php` | Classe `GerenciadorNotificacoes`: envio individual, em lote, rastreamento e relatório |
| `upload_image.php` | Upload de imagens para o editor TinyMCE |

**Variáveis disponíveis nos templates de e-mail:**

| Variável | Valor substituído |
|----------|------------------|
| `{{nome}}` | Nome do empreendedor |
| `{{email}}` | E-mail do empreendedor |
| `{{dias}}` | Dias desde o último login |
| `{{nome_fantasia}}` | Nome fantasia do negócio |
| `{{etapa_atual}}` | Etapa atual do cadastro |
| `{{ano}}` | Ano atual |
| `{{link_vitrine}}` | URL dinâmica da vitrine |
| `{{link_painel}}` | URL do painel do empreendedor |

***

### 📊 Dashboard

| Arquivo | Descrição |
|---------|-----------|
| `dashboard.php` | KPIs em tempo real + atalhos rápidos para todos os módulos |

**KPIs exibidos:**
- Total de empreendedores e empreendedores ativos
- Total de negócios: concluídos / em andamento / encerrados
- Últimos 5 logins de empreendedores

***

### 🐛 Arquivos de Debug

| Arquivo | Descrição |
|---------|-----------|
| `users_db_debug.php` | Debug de conexão com a tabela `users` |
| `users_model_debug.php` | Debug do `UserModel` |

> ⚠️ Remover estes arquivos do ambiente de produção.

***

## 🔐 Proteções Implementadas

- Tokens CSRF em todos os formulários POST
- Senhas armazenadas com `password_hash()` / `PASSWORD_DEFAULT`
- Prepared statements PDO em todas as queries
- Sanitização com `htmlspecialchars()` em todas as saídas