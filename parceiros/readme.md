# Módulo: parceiros/

Área de cadastro e gestão dos parceiros da Plataforma Impactos Positivos. Parceiros são organizações (empresas, fundações, institutos etc.) que se vinculam à plataforma por meio de uma Carta-Acordo, declarando interesses de atuação, ODS prioritários e informações jurídicas. O módulo cobre o fluxo completo: cadastro em 6 etapas, assinatura do acordo, painel do parceiro e gestão administrativa.

***

## Estrutura de Arquivos

```
parceiros/
├── cadastro.php               # Etapa 1 — Dados da organização
├── etapa1dados.php            # Dados básicos (razão social, CNPJ, endereço)
├── etapa2tipo.php             # Tipo de organização e porte
├── etapa3combinado.php        # Representante legal e dados de contato
├── etapa4interesses.php       # Interesses temáticos e matchmaking
├── etapa5plataforma.php       # Como deseja participar da plataforma
├── etapa6juridico.php         # Informações jurídicas e aceite do acordo
├── processaretapa1.php        # Salva etapa 1 no banco
├── processaretapa2.php        # Salva etapa 2 no banco
├── processaretapa3.php        # Salva etapa 3 no banco
├── processaretapa4.php        # Salva etapa 4 no banco
├── processaretapa5.php        # Salva etapa 5 no banco
├── processaretapa6.php        # Salva etapa 6 e finaliza cadastro
├── processarloginparceiro.php # Autenticação do parceiro
├── assinaracordo.php          # Assinatura digital da Carta-Acordo
├── confirmacao.php            # Tela de confirmação pós-cadastro
├── dashboard.php              # Painel principal do parceiro
├── editarcadastro.php         # Edição simplificada de dados básicos
├── editardados.php            # Edição completa de dados da organização
├── editarinteresses.php       # Edição de interesses e ODS
├── editarperfil.php           # Edição do perfil de participação
├── processareditarcadastro.php# Processa edição básica
└── visualizarminhacarta.php   # Visualização da Carta-Acordo assinada
```

***

## Fluxo de Cadastro (6 Etapas)

O cadastro do parceiro segue um formulário multi-etapas com progresso salvo em sessão e persistência incremental no banco.

| Etapa | Arquivo | Conteúdo |
|-------|---------|----------|
| 1 | `etapa1dados.php` | Razão social, nome fantasia, CNPJ, endereço, CEP (autocomplete via ViaCEP) |
| 2 | `etapa2tipo.php` | Tipo da organização (empresa, instituto, fundação, OSC, cooperativa etc.) e porte |
| 3 | `etapa3combinado.php` | Nome do representante legal, cargo, e-mail, telefone |
| 4 | `etapa4interesses.php` | Eixos temáticos de interesse, ODS prioritários, setores e maturidade dos negócios que deseja apoiar |
| 5 | `etapa5plataforma.php` | Como pretende participar: apoio financeiro, mentoria, conexão de mercado, voluntariado etc. |
| 6 | `etapa6juridico.php` | Dados jurídicos, CNPJ confirmado, aceite dos termos e assinatura da Carta-Acordo |

Após a etapa 6, o parceiro é redirecionado para `confirmacao.php` e aguarda aprovação administrativa. O status inicial é `emcadastro`.

***

## Status do Parceiro

| Status | Descrição |
|--------|-----------|
| `emcadastro` | Cadastro iniciado, ainda não enviado |
| `analise` | Cadastro concluído, aguardando revisão do admin |
| `ativo` | Aprovado — tem acesso ao painel e à vitrine |
| `inativo` | Acesso suspenso pelo admin |

A transição de `analise` → `ativo` é feita em `admin/processarstatusparceiro.php`, que também dispara o e-mail de boas-vindas.

***

## Assinatura da Carta-Acordo

O arquivo `assinaracordo.php` renderiza o modelo da Carta-Acordo preenchido com os dados do parceiro. O aceite é registrado no banco com:

- `data_assinatura` — timestamp do aceite
- `ip_assinatura` — IP do solicitante
- `assinado` — flag booleana

A versão printável/PDF está disponível em `visualizarminhacarta.php` (parceiro) e em `admin/visualizarcartaparceiro.php` (admin).

***

## Tabela Principal: `parceiros`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Identificador do parceiro |
| `user_id` | INT FK | Vínculo com `users` |
| `razao_social` | VARCHAR | Razão social da organização |
| `nome_fantasia` | VARCHAR | Nome de exibição |
| `cnpj` | VARCHAR | CNPJ formatado |
| `tipo_organizacao` | VARCHAR | Tipo: empresa, instituto, fundação etc. |
| `porte` | VARCHAR | Porte da organização |
| `cep` | VARCHAR | CEP |
| `cidade` | VARCHAR | Cidade |
| `estado` | VARCHAR | UF |
| `representante_nome` | VARCHAR | Nome do responsável legal |
| `representante_cargo` | VARCHAR | Cargo do representante |
| `representante_email` | VARCHAR | E-mail do representante |
| `representante_telefone` | VARCHAR | Telefone de contato |
| `status` | ENUM | `emcadastro`, `analise`, `ativo`, `inativo` |
| `assinado` | TINYINT | 1 = Carta-Acordo aceita |
| `data_assinatura` | DATETIME | Timestamp do aceite |
| `ip_assinatura` | VARCHAR | IP registrado no aceite |
| `created_at` | DATETIME | Data de criação |
| `updated_at` | DATETIME | Última atualização |

### Tabelas Relacionadas

| Tabela | Vínculo | Conteúdo |
|--------|---------|----------|
| `parceiro_contrato` | `parceiro_id` | Dados do contrato: tipo, natureza, vigência, escopo |
| `parceiro_interesses` | `parceiro_id` | Eixos temáticos, setores e maturidade de interesse |
| `parceiro_ods` | `parceiro_id` | ODS prioritários do parceiro |

***

## Painel do Parceiro (`dashboard.php`)

Após login, o parceiro acessa um painel com:

- Resumo do status do cadastro
- Acesso à Carta-Acordo assinada
- Opções de edição de dados (`editardados.php`, `editarinteresses.php`, `editarperfil.php`)
- Visibilidade de negócios compatíveis com seus interesses (matchmaking)

***

## Autenticação

O login do parceiro é processado por `processarloginparceiro.php`. A autenticação verifica:

- Credenciais via `users` (role `parceiro`)
- Status `ativo` no registro de `parceiros`
- Sessão iniciada com `usuario_id` e `role = parceiro`

A função de proteção de rotas é `requireParceiroLogin()` definida em `app/helpers/auth.php`.

***

## Gestão Administrativa

Todas as ações sobre parceiros no admin estão em `admin/`:

| Arquivo | Descrição |
|---------|-----------|
| `parceiros.php` | Lista com filtros, busca e alteração rápida de status |
| `visualizarparceiro.php` | Dados completos: organização, contrato, interesses, ODS |
| `visualizarcartaparceiro.php` | Carta-Acordo assinada — versão printável/PDF |
| `processarstatusparceiro.php` | Altera status e dispara e-mail de boas-vindas ao parceiro |

***

## Views Relacionadas

```
app/views/parceiros/
└── sidebar.php    # Menu lateral do painel do parceiro
```

```
app/views/forms/
└── form-loginparceiro.php   # Formulário de login público do parceiro
```

```
app/views/public/
└── gridparceiros.php        # Grid de parceiros exibido na vitrine pública
```

***

## Arquivo Público: `parceiros.php` (raiz)

O arquivo `/parceiros.php` é a página pública da vitrine de parceiros, acessível sem autenticação. Exibe os parceiros com status `ativo` usando o componente `app/views/public/gridparceiros.php`.

O perfil detalhado de cada parceiro é acessível em `/perfilparceiro.php`.