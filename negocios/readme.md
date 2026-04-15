# 💼 Módulo Negócios (`negocios/`)

Formulário multi-etapas para cadastro completo de negócios de impacto social. Acessível apenas para empreendedores autenticados. Cada negócio passa por 9 etapas antes de ser submetido para curadoria e publicação na vitrine.

***

## 📋 Arquivos do Módulo

| Arquivo | Descrição |
|---------|-----------|
| `etapa1_dados_negocio.php` | Formulário — Etapa 1: Dados do negócio |
| `processar_etapa1.php` | Processador POST da Etapa 1 |
| `editar_etapa1.php` | Edição da Etapa 1 (modo edição) |
| `etapa2_fundadores.php` | Formulário — Etapa 2: Fundadores e cofundadores |
| `processar_etapa2.php` | Processador POST da Etapa 2 |
| `etapa3_eixo_tematico.php` | Formulário — Etapa 3: Eixo temático e subáreas |
| `etapa4_ods.php` | Formulário — Etapa 4: Conexão com ODS |
| `etapa5_apresentacao.php` | Formulário — Etapa 5: Apresentação do negócio |
| `etapa6_financeiro.php` | Formulário — Etapa 6: Dados financeiros e modelo de receita |
| `etapa7_impacto.php` | Formulário — Etapa 7: Avaliação de impacto |
| `etapa8_visao.php` | Formulário — Etapa 8: Visão de futuro |
| `etapa9_documentacao.php` | Formulário — Etapa 9: Documentação |
| `confirmacao.php` | Tela de revisão e confirmação final |
| `store.php` | Controlador final — consolida todas as etapas e conclui o cadastro |
| `blocos-cadastros/` | Blocos de visualização parcial por etapa (usados na tela de revisão) |

***

## 🔄 Fluxo de Cadastro

```
Autenticação (require_empreendedor_login)
    ↓
Etapa 1: Dados do negócio → processar_etapa1.php
    ↓
Etapa 2: Fundadores       → processar_etapa2.php
    ↓
Etapa 3: Eixo Temático
    ↓
Etapa 4: ODS
    ↓
Etapa 5: Apresentação
    ↓
Etapa 6: Dados Financeiros
    ↓
Etapa 7: Avaliação de Impacto
    ↓
Etapa 8: Visão de Futuro
    ↓
Etapa 9: Documentação
    ↓
confirmacao.php (revisão completa)
    ↓
store.php → inscricao_completa = 1
```

***

## 📝 Etapas Detalhadas

### Etapa 1 — Dados do Negócio

Campos: `nome_fantasia`, `razao_social`, `categoria`, `cnpj_cpf`, `formato_legal`, `email_comercial`, `telefone_comercial`, `data_fundacao`, `setor`, endereço completo (CEP via ViaCEP), redes sociais, `interesse_marketplace`.

**Validações especiais:**
- CEP consultado na API ViaCEP para validação e preenchimento automático de cidade/estado
- `cnpj_cpf`: CNPJ obrigatório para todas as categorias, exceto `Ideação` (aceita CPF ou CNPJ)
- URLs de redes sociais validadas com `FILTER_VALIDATE_URL`
- Data de fundação não pode ser futura

**Score calculado:** `score_investimento` (componente `estagio`) salvo na tabela `scores_negocios` via `pesos_scores` e `lookup_scores`.

**Redirecionamento inteligente:**
- Modo `cadastro`: avança para Etapa 2
- Modo `editar` + `inscricao_completa = 1`: retorna para `confirmacao.php`
- Modo `editar` + cadastro em andamento: retorna para a etapa onde o empreendedor havia parado

***

### Etapa 2 — Fundadores

Campos: dados de até 5 fundadores/cofundadores. Cada fundador inclui nome, CPF, e-mail, papel, formação e etnia.

Dados salvos na tabela `negocio_fundadores`.

***

### Etapa 3 — Eixo Temático

Campos: seleção de eixo temático e subáreas relacionadas.

Tabelas utilizadas: `eixos_tematicos`, `subareas`, `negocio_subareas`.

***

### Etapa 4 — ODS

Campos: seleção das ODS (Objetivos de Desenvolvimento Sustentável) relacionadas ao negócio.

Tabela utilizada: `negocio_ods` (referencia tabela `ods` com 17 registros e ícones).

***

### Etapa 5 — Apresentação

Campos: descrição do negócio, link de vídeo, galeria de imagens, diferenciais de inovação.

Tabela utilizada: `negocio_apresentacao`.

***

### Etapa 6 — Dados Financeiros

Campos: faixa de faturamento, fontes de receita, modelo de negócio.

Tabela utilizada: `negocio_financeiro`.

***

### Etapa 7 — Avaliação de Impacto

Campos: intencionalidade de impacto, públicos beneficiados, indicadores de resultado.

Tabela utilizada: `negocio_impacto`.

***

### Etapa 8 — Visão de Futuro

Campos: planos de expansão, metas, visão de longo prazo.

Tabela utilizada: `negocio_visao`.

***

### Etapa 9 — Documentação

Campos: upload de documentos obrigatórios (Estatuto/Contrato Social, comprovante de CNPJ, outros documentos relevantes).

Tabela utilizada: `negocios_documentos`.

***

## 🗄️ Tabela Principal — `negocios`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | Auto-incremento |
| `empreendedor_id` | INT FK | Referência para `empreendedores.id` |
| `nome_fantasia` | VARCHAR | Nome do negócio |
| `razao_social` | VARCHAR | Razão social |
| `categoria` | VARCHAR | `Ideação` · `Operação` · `Tração/Escala` · `Dinamizador` |
| `cnpj_cpf` | VARCHAR | CNPJ ou CPF (somente dígitos) |
| `formato_legal` | VARCHAR | Formato jurídico |
| `formato_outros` | VARCHAR | Detalhe quando `formato_legal = 'Outros'` |
| `email_comercial` | VARCHAR | E-mail de contato do negócio |
| `telefone_comercial` | VARCHAR | Telefone comercial |
| `data_fundacao` | DATE | Data de fundação |
| `setor` | VARCHAR | Setor de atuação |
| `setor_detalhe` | VARCHAR | Detalhe do setor |
| `rua / numero / complemento / cep / municipio / estado / pais` | VARCHAR | Endereço completo |
| `site / linkedin / instagram / facebook / tiktok / youtube / outros_links` | VARCHAR | Redes sociais e site |
| `interesse_marketplace` | VARCHAR | `'Sim'` ou `'Não'` |
| `etapa_atual` | INT | Etapa em que o cadastro está (1–10) |
| `inscricao_completa` | TINYINT | `0` = em andamento · `1` = concluído |
| `status_vitrine` | VARCHAR | `pendente` · `aprovado` · `publicado` · `encerrado` |
| `criado_em` | DATETIME | Data de criação |
| `atualizado_em` | DATETIME | Data da última atualização |

***

## 🗄️ Tabelas Relacionadas

| Tabela | Descrição |
|--------|-----------|
| `negocio_fundadores` | Fundadores e cofundadores (até 5 por negócio) |
| `negocio_subareas` | Eixos temáticos e subáreas selecionadas |
| `negocio_ods` | ODS vinculadas ao negócio |
| `negocio_apresentacao` | Descrição, vídeo, galeria, inovação |
| `negocio_impacto` | Intencionalidade, públicos, indicadores |
| `negocio_financeiro` | Faturamento, fontes de receita |
| `negocio_mercado` | Modelo de negócio, mercado-alvo |
| `negocio_visao` | Visão de futuro e metas |
| `negocio_sustentabilidade` | Dados de sustentabilidade |
| `negocio_documentos` / `negocios_documentos` | Documentos da Etapa 9 |
| `scores_negocios` | `score_impacto` / `score_investimento` / `score_escala` / `score_geral` |

***

## 🏆 Sistema de Scores

Scores calculados e armazenados na tabela `scores_negocios`, usando pesos e valores de lookup nas tabelas:
- `pesos_scores` — define o peso de cada componente por tipo de score
- `lookup_scores` — tabela de valores normalizados por componente e opção

**Fórmula do score geral:**
```
score_geral = 40% × score_impacto + 30% × score_investimento + 30% × score_escala
```

**Recálculo em lote:** disponível via `admin/recalcular_scores.php`.

***

## 📂 blocos-cadastros/

Blocos PHP de visualização parcial, utilizados na tela de revisão (`confirmacao.php`) e na visualização admin (`admin/visualizar_negocio.php`).

| Arquivo | Descrição |
|---------|-----------|
| `_shared.php` | Funções e utilitários compartilhados entre blocos |
| `bloco_etapa1.php` | Exibe dados do negócio (Etapa 1) |
| `bloco_etapa2.php` | Exibe fundadores (Etapa 2) |
| `bloco_etapa3.php` | Exibe eixo temático e subáreas (Etapa 3) |
| `bloco_etapa4.php` | Exibe ODS (Etapa 4) |
| `bloco_etapa5.php` | Exibe apresentação (Etapa 5) |
| `bloco_etapa6.php` | Exibe dados financeiros (Etapa 6) |
| `bloco_etapa7.php` | Exibe avaliação de impacto (Etapa 7) |
| `bloco_etapa8.php` | Exibe visão de futuro (Etapa 8) |
| `bloco_etapa9.php` | Exibe documentação (Etapa 9) |

***

## 🔒 Segurança

- `require_empreendedor_login()` em todas as páginas de etapa
- Verificação de `empreendedor_id` em todos os INSERTs e UPDATEs (impede que um empreendedor edite negócios de outro)
- Prepared statements PDO em todas as queries
- Tokens CSRF nos formulários POST