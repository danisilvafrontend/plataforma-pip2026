# Sitemap — Plataforma Impactos Positivos

Mapeamento completo de todas as rotas, agrupadas por perfil de acesso e módulo funcional.

***

## Legenda

| Símbolo | Significado |
|---------|-------------|
| 🌐 | Público — sem autenticação |
| 👤 | Empreendedor (`role = user`) |
| 🤝 | Parceiro (`role = parceiro`) |
| 🗳️ | Sociedade Civil (`role = sociedade`) |
| 🛡️ | Admin (`role = admin` ou `superadmin`) |
| ⚙️ | Superadmin exclusivo |
| 🔁 | Processamento interno (POST/redirect — sem interface) |
| 🕒 | Cron / script manual |

***

## 1. Páginas Públicas (Raiz)

Acessíveis por qualquer visitante sem login.

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🌐 `/` | `index.php` | Página inicial da plataforma |
| 🌐 `/login.php` | `login.php` | Login de empreendedores e Sociedade Civil |
| 🌐 `/admin-login.php` | `admin-login.php` | Login exclusivo do painel admin |
| 🌐 `/logout.php` | `logout.php` | Encerramento de sessão — todos os perfis |
| 🌐 `/cadastro.php` | `cadastro.php` | Cadastro público — 3 etapas (Sociedade Civil) |
| 🌐 `/cadastrosucesso.php` | `cadastrosucesso.php` | Confirmação de cadastro concluído |
| 🌐 `/vitrinenacional.php` | `vitrinenacional.php` | Vitrine nacional de negócios publicados |
| 🌐 `/negocio.php?id=` | `negocio.php` | Página pública de um negócio |
| 🌐 `/parceiros.php` | `parceiros.php` | Vitrine pública de parceiros ativos |
| 🌐 `/perfilparceiro.php?id=` | `perfilparceiro.php` | Perfil público de um parceiro específico |

***

## 2. Auth (Processamento)

Recebem POST e redirecionam. Sem interface própria.

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🔁 `/auth/processarcadastrosociedade.php` | `auth/processarcadastrosociedade.php` | Processa cadastro da Sociedade Civil (3 etapas) |

***

## 3. Área do Empreendedor (`empreendedores/`)

Requer login com `role = user`.

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 👤 `/empreendedores/dashboard.php` | `dashboard.php` | Dashboard do empreendedor — KPIs e atalhos |
| 👤 `/empreendedores/minha-conta.php` | `minha-conta.php` | Dados pessoais e senha |
| 👤 `/empreendedores/meus-negocios.php` | `meus-negocios.php` | Lista de negócios do empreendedor |
| 👤 `/empreendedores/minhasinscricoespremiacao.php` | `minhasinscricoespremiacao.php` | Inscrições do empreendedor na premiação |

### Cadastro Público do Empreendedor (`empreendedores/`)

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🌐 `/empreendedores/register.php` | `register.php` | Formulário de cadastro público do empreendedor |
| 🔁 `/empreendedores/store.php` | `store.php` | Processa o POST do cadastro |
| 🌐 `/empreendedores/sucesso.php` | `sucesso.php` | Confirmação de cadastro do empreendedor |

***

## 4. Cadastro do Negócio (`negocios/`)

Requer login com `role = user`. Formulário multi-etapas.

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 👤 `/negocios/etapa1dadosnegocio.php` | `etapa1dadosnegocio.php` | Etapa 1 — Dados gerais do negócio |
| 👤 `/negocios/etapa2fundadores.php` | `etapa2fundadores.php` | Etapa 2 — Fundadores e cofundadores (até 5) |
| 👤 `/negocios/etapa3eixotematico.php` | `etapa3eixotematico.php` | Etapa 3 — Eixo temático e subárea |
| 👤 `/negocios/etapa4ods.php` | `etapa4ods.php` | Etapa 4 — ODS relacionadas |
| 👤 `/negocios/etapa5apresentacao.php` | `etapa5apresentacao.php` | Etapa 5 — Apresentação, logo, galeria, pitch |
| 👤 `/negocios/etapa6financeiro.php` | `etapa6financeiro.php` | Etapa 6 — Dados financeiros e modelo de receita |
| 👤 `/negocios/etapa7impacto.php` | `etapa7impacto.php` | Etapa 7 — Avaliação de impacto e indicadores |
| 👤 `/negocios/etapa8visao.php` | `etapa8visao.php` | Etapa 8 — Visão de futuro e sustentabilidade |
| 👤 `/negocios/etapa9documentacao.php` | `etapa9documentacao.php` | Etapa 9 — Documentação (certidões PDF) |
| 👤 `/negocios/confirmacao.php` | `confirmacao.php` | Etapa 10 — Revisão final e inscrição na premiação |

### Processadores de Etapa (POST → redirect)

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🔁 `/negocios/processaretapa1.php` | `processaretapa1.php` | Salva etapa 1 |
| 🔁 `/negocios/processaretapa2.php` | `processaretapa2.php` | Salva etapa 2 |
| 🔁 `/negocios/processaretapa3.php` | `processaretapa3.php` | Salva etapa 3 |
| 🔁 `/negocios/processaretapa4.php` | `processaretapa4.php` | Salva etapa 4 |
| 🔁 `/negocios/processaretapa5.php` | `processaretapa5.php` | Salva etapa 5 + calcula score de impacto parcial |
| 🔁 `/negocios/processaretapa6.php` | `processaretapa6.php` | Salva etapa 6 |
| 🔁 `/negocios/processaretapa7.php` | `processaretapa7.php` | Salva etapa 7 |
| 🔁 `/negocios/processaretapa8.php` | `processaretapa8.php` | Salva etapa 8 |
| 🔁 `/negocios/processaretapa9.php` | `processaretapa9.php` | Salva etapa 9 (uploads de PDF) |

### Edição de Etapas (modo edição)

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 👤 `/negocios/editaretapa1.php` até `editaretapa9.php` | `editaretapaN.php` | Edição individual de cada etapa após cadastro concluído |

### Blocos de Visualização

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 👤 `/negocios/blocos-cadastros/shared.php` | `shared.php` | Funções compartilhadas de renderização de blocos |
| 👤 `/negocios/blocos-cadastros/blocoetapa1.php` até `blocoetapa9.php` | `blocoetapaN.php` | Blocos de leitura de cada etapa (usados na confirmação e no admin) |

***

## 5. Área do Parceiro (`parceiros/`)

Requer login com `role = parceiro` e status `ativo`.

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🤝 `/parceiros/dashboard.php` | `dashboard.php` | Painel do parceiro |
| 🤝 `/parceiros/minha-conta.php` | `minha-conta.php` | Dados e senha do parceiro |
| 🌐 `/parceiros/cadastro.php` | `cadastro.php` | Cadastro público do parceiro |
| 🔁 `/parceiros/store.php` | `store.php` | Processa o POST do cadastro do parceiro |
| 🤝 `/parceiros/cartaacordo.php` | `cartaacordo.php` | Visualização da Carta-Acordo pelo parceiro |

***

## 6. Área da Sociedade Civil (`sociedadecivil/`)

Requer login com `role = sociedade`.

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🗳️ `/sociedadecivil/minhaconta.php` | `minhaconta.php` | Painel e dados da conta |
| 🗳️ `/sociedadecivil/votacao.php` | `votacao.php` | Votação popular na premiação (fase ativa) |

***

## 7. Painel Admin (`admin/`)

Requer `requireAdminLogin()` — roles `admin` e `superadmin`.

### Dashboard

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/dashboard.php` | `dashboard.php` | KPIs em tempo real e atalhos |

### Gestão de Usuários Admin

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/administradores.php` | `administradores.php` | Lista paginada de usuários admin |
| 🛡️ `/admin/createuser.php` | `createuser.php` | Criar usuário admin com senha automática |
| 🛡️ `/admin/edituser.php?id=` | `edituser.php` | Editar usuário admin (nome, e-mail, role, status) |
| 🛡️ `/admin/usuarios.php` | `usuarios.php` | Lista geral de usuários da plataforma |
| ⚙️ `/admin/excluirusuario.php` | `excluirusuario.php` | Exclusão de usuário (superadmin) |

### Gestão de Empreendedores

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/empreendedores.php` | `empreendedores.php` | Lista com filtros e paginação |
| 🛡️ `/admin/createempreendedor.php` | `createempreendedor.php` | Criar empreendedor com senha provisória |
| ⚙️ `/admin/editarempreendedor.php?id=` | `editarempreendedor.php` | Editar dados do empreendedor |
| ⚙️ `/admin/excluirempreendedor.php?id=` | `excluirempreendedor.php` | Exclusão em cascata (superadmin) |
| 🛡️ `/admin/resetemail.php?id=` | `resetemail.php` | Alterar e-mail e enviar nova senha temporária |
| 🛡️ `/admin/resetpassword.php?id=` | `resetpassword.php` | Resetar senha por e-mail |
| 🛡️ `/admin/importarempreendedores.php` | `importarempreendedores.php` | Importação em lote via CSV |

### Gestão de Negócios

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/negocios.php` | `negocios.php` | Lista com filtros, scores e status da vitrine |
| 🛡️ `/admin/visualizarnegocio.php?id=` | `visualizarnegocio.php` | Visualização completa das 9 etapas |
| 🛡️ `/admin/aprovarnegocio.php?id=` | `aprovarnegocio.php` | Aprovar e publicar na vitrine + e-mail |
| 🛡️ `/admin/atribuirnegocio.php` | `atribuirnegocio.php` | Migrar negócio da base legada para empreendedor |
| 🛡️ `/admin/importarnegocios.php` | `importarnegocios.php` | Importação de negócios via CSV |
| 🛡️ `/admin/recalcularscores.php` | `recalcularscores.php` | Recalcular scores de todos os negócios |
| 🛡️ `/admin/relatoriosnegocios.php` | `relatoriosnegocios.php` | Gráficos e relatórios Chart.js |

### Gestão de Parceiros

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/parceiros.php` | `parceiros.php` | Lista com filtros e modal de status |
| 🛡️ `/admin/visualizarparceiro.php?id=` | `visualizarparceiro.php` | Dados completos do parceiro |
| 🛡️ `/admin/visualizarcartaparceiro.php?id=` | `visualizarcartaparceiro.php` | Carta-Acordo assinada (imprimível/PDF) |
| 🔁 `/admin/processarstatusparceiro.php` | `processarstatusparceiro.php` | Atualizar status e enviar e-mail de aprovação |

### E-mails e Notificações

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/emailtemplates.php` | `emailtemplates.php` | Editor TinyMCE de templates de e-mail |
| 🛡️ `/admin/gerenciarnotificacoes.php` | `gerenciarnotificacoes.php` | Envio individual, em lote e rastreamento |
| 🛡️ `/admin/enviaremailstatus.php` | `enviaremailstatus.php` | Envio segmentado por status de engajamento |
| 🛡️ `/admin/enviaremailnegociospendentes.php` | `enviaremailnegociospendentes.php` | Notificar empreendedores com cadastro incompleto |
| 🔁 `/admin/uploadimage.php` | `uploadimage.php` | Upload de imagens para o TinyMCE |
| 🔁 `/admin/processarfilaemails.php` | `processarfilaemails.php` | Processar fila de envios pendentes |
| 🛡️ `/admin/testeemail.php` | `testeemail.php` | Teste de envio de e-mail |

### Premiação

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| 🛡️ `/admin/premiacaoedicoes.php` | `premiacaoedicoes.php` | CRUD de edições anuais do prêmio |
| 🛡️ `/admin/premiacaoperiodos.php` | `premiacaoperiodos.php` | Gestão de fases/períodos de cada edição |

### Debug (remover em produção)

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| ⚙️ `/admin/usersdbdebug.php` | `usersdbdebug.php` | Debug de conexão com tabela `users` |
| ⚙️ `/admin/usersmodeldebug.php` | `usersmodeldebug.php` | Debug do `UserModel` |

***

## 8. Cron Jobs (`cron/`)

Execução agendada no servidor. Não acessar via navegador.

| Arquivo | Frequência sugerida | Descrição |
|---------|-------------------|-----------|
| 🕒 `cron/cronverificarvencimentos.php` | Diária (08h) | Verifica contratos de parceiros próximos do vencimento e envia alertas |

***

## 9. Scripts Utilitários (`scripts/`)

Execução manual única ou pontual. Proteger ou remover após uso.

| Arquivo | Descrição |
|---------|-----------|
| 🕒 `scripts/createsuperadmin.php` | Cria o primeiro usuário superadmin — **executar uma vez e remover** |
| 🕒 `scripts/cleanuppasswordresets.php` | Remove tokens de redefinição de senha expirados |

***

## 10. Utilitários na Raiz

| Rota | Arquivo | Descrição |
|------|---------|-----------|
| ⚙️ `/resetopcache.php` | `resetopcache.php` | Reset de OPcache (uso manual em produção) |
| ⚙️ `/sessiontest.php` | `sessiontest.php` | Debug de sessão — **remover em produção** |

***

## Visão por Perfil de Acesso

| Perfil | Ponto de entrada | Áreas acessíveis |
|--------|-----------------|-----------------|
| **Visitante** | `index.php` | Vitrine nacional, perfil de negócio, perfil de parceiro, login, cadastro |
| **Empreendedor** | `empreendedores/dashboard.php` | Minha conta, meus negócios, cadastro de negócio (9 etapas), premiação |
| **Parceiro** | `parceiros/dashboard.php` | Minha conta, Carta-Acordo |
| **Sociedade Civil** | `sociedadecivil/minhaconta.php` | Minha conta, votação popular |
| **Admin** | `admin/dashboard.php` | Todos os módulos admin exceto ações superadmin |
| **Superadmin** | `admin/dashboard.php` | Todos os módulos, incluindo exclusões e edições restritas |
| **Júri** | *(rota própria — premiação)* | Avaliação técnica da fase habilitada |

***

## Fluxo de Cadastro do Negócio

```
login.php
  └─► empreendedores/dashboard.php
        └─► empreendedores/meus-negocios.php
              └─► negocios/etapa1dadosnegocio.php
                    └─► processaretapa1.php ──► etapa2fundadores.php
                          └─► processaretapa2.php ──► etapa3eixotematico.php
                                └─► ...
                                      └─► etapa9documentacao.php
                                            └─► processaretapa9.php ──► confirmacao.php
                                                                              └─► [Inscrição na Premiação]
                                                                              └─► admin/aprovarnegocio.php
                                                                                        └─► vitrinenacional.php
```

***

## Fluxo de Cadastro da Sociedade Civil

```
index.php / cadastro.php
  └─► cadastro.php (etapa 1 — dados pessoais)
        └─► (etapa 2 — interesses e ODS)
              └─► (etapa 3 — perfil de impacto)
                    └─► auth/processarcadastrosociedade.php
                          └─► cadastrosucesso.php
                                └─► login.php ──► sociedadecivil/minhaconta.php
```

***

## Fluxo de Cadastro e Aprovação do Parceiro

```
parceiros/cadastro.php
  └─► parceiros/store.php
        └─► admin/parceiros.php (status: emcadastro)
              └─► admin/visualizarparceiro.php
                    └─► admin/processarstatusparceiro.php (status: ativo + e-mail)
                          └─► parceiros.php (vitrine pública)
```