# Cron Jobs — Plataforma Impactos Positivos

Este diretório contém os scripts de tarefas agendadas da plataforma.
Eles devem ser executados via **cron job no servidor** — nunca manualmente pelo navegador em produção.

---

## Scripts disponíveis

| Arquivo | Frequência recomendada | O que faz |
|---|---|---|
| `update_status.php` | Diário — 1x por dia às 03h | Atualiza status dos empreendedores (ativo/inativo/dormant) com base no último login |
| `atualizar_fases_premiacao.php` | Diário — 1x por dia às 00h05 | Atualiza automaticamente o status das fases e edições da premiação com base nas datas configuradas |

---

## `atualizar_fases_premiacao.php`

### O que faz

Atualiza duas tabelas baseando-se exclusivamente na data atual comparada às datas cadastradas pelo admin no painel.

**1. `premiacao_fases.status`**

| Condição | Status resultante |
|---|---|
| `HOJE < data_inicio` | `agendada` |
| `data_inicio ≤ HOJE ≤ data_fim` | `em_andamento` |
| `HOJE > data_fim` | `encerrada` |
| Status atual é `rascunho` | Não altera (admin ainda não publicou) |
| Status atual é `apurada` | Não altera (encerrado manualmente pelo admin) |

**2. `premiacoes.status`**

| Situação das fases | Status da edição |
|---|---|
| Nenhuma fase publicada | `planejada` |
| Ao menos 1 fase `em_andamento` | `ativa` |
| Todas as fases `encerradas`/`apuradas` | `encerrada` |
| Só fases `agendadas`, nenhuma ativa | `planejada` |

### Por que 1x por dia às 00h05 é suficiente?

As fases são configuradas com granularidade de **dias inteiros** (iniciam à 00h00 de uma data e encerram às 23h59 de outra). Rodar o cron logo após a virada da meia-noite garante que ao usuário acordar, a fase já está no status correto. Como o PIP acontece **uma vez por ano**, esse agendamento é mais que suficiente.

---

## Cronograma PIP 2026 (referência)

Este é o calendário da edição atual. Use-o para cadastrar as fases no painel admin com as datas e tipos corretos:

| Fase | Tipo (`tipo_fase`) | Abertura | Encerramento | Voto popular | Avaliação técnica |
|---|---|---|---|:---:|:---:|
| Lançamento / Inscrições | `inscricoes` | 11/05/2026 | 24/07/2026 | ✗ | ✗ |
| Avaliação dos Inscritos | `triagem_documental` | 25/07/2026 | 29/07/2026 | ✗ | ✗ |
| Fase 1 | `classificatoria` | 30/07/2026 | 14/08/2026 | ✓ | ✓ |
| Fase 2 | `classificatoria` | 24/08/2026 | 04/09/2026 | ✓ | ✓ |
| Fase 3 (Final) | `final` | 07/09/2026 | 18/09/2026 | ✓ | ✗ |
| Encontro 2026 | `resultado` | 24/09/2026 | 24/09/2026 | ✗ | ✗ |

> **Nota:** A "Avaliação de Votação" que existia entre a Fase 1 e a Fase 2 foi removida do cronograma
> porque essa apuração agora é feita automaticamente pelo sistema.

---

## Configuração no servidor (AWS)

### Opção 1 — Crontab direto na instância EC2 (mais simples)

Conecte na instância via SSH e edite o crontab:

```bash
crontab -e
```

Adicione as linhas abaixo (ajuste o caminho para onde o projeto está instalado):

```cron
# Atualiza status das fases da premiação todo dia às 00h05 (logo após a virada)
5 0 * * * php /var/www/html/cron/atualizar_fases_premiacao.php >> /var/www/html/storage/logs/cron_premiacao.log 2>&1

# Atualiza status dos empreendedores todo dia às 03h
0 3 * * * php /var/www/html/cron/update_status.php >> /var/www/html/storage/logs/cron_empreendedores.log 2>&1
```

> **Dica:** Para descobrir o caminho correto do PHP no servidor:
> ```bash
> which php
> # Se retornar /usr/bin/php8.2, substitua `php` por esse caminho nas linhas acima
> ```

### Opção 2 — AWS EventBridge Scheduler (sem acesso SSH)

Se a plataforma roda no **Elastic Beanstalk** ou **ECS** sem acesso direto à instância:

1. No Console AWS, acesse **EventBridge → Scheduler → Create schedule**
2. Frequência: `cron(5 0 * * ? *)` (todo dia às 00h05 UTC — ajuste para UTC-3 se necessário: `cron(5 3 * * ? *)`)
3. Target: chamada HTTP para a URL abaixo:

```
https://staging.impactospositivos.com/cron/atualizar_fases_premiacao.php?token=SEU_TOKEN_AQUI
```

> O token é definido pela variável de ambiente `CRON_SECRET` no servidor.
> Se não estiver configurada, o valor padrão é `pip2026_cron_secret` — altere para um valor forte.

### Opção 3 — AWS Lambda + EventBridge (avançado)

Crie uma função Lambda que faça um GET na URL acima com o token, agendada pelo EventBridge com expressão `cron(5 3 * * ? *)` (00h05 horário de Brasília).

---

## Segurança

O script só aceita chamadas HTTP com o token correto:

```
# Via query string
https://seudominio.com/cron/atualizar_fases_premiacao.php?token=SEU_TOKEN

# Via header HTTP
X-Cron-Token: SEU_TOKEN
```

Sem o token → retorna **403 Acesso negado**. Via CLI diretamente, o token não é necessário.

Defina o token no servidor:

```bash
export CRON_SECRET="token_forte_aqui"
```

---

## Verificando se o cron está funcionando

```bash
# Últimas execuções
tail -50 /var/www/html/storage/logs/cron_premiacao.log

# Acompanhar em tempo real
tail -f /var/www/html/storage/logs/cron_premiacao.log
```

Exemplo de saída no dia que uma fase muda:

```
[2026-07-30 00:05:01] Fase #3 (Fase 1): agendada → em_andamento
[2026-07-30 00:05:01] Edição #1 (PIP 2026): planejada → ativa
[2026-07-30 00:05:01] Fases verificadas: 6 | Atualizadas: 1
[2026-07-30 00:05:01] Edições verificadas: 1 | Atualizadas: 1
[2026-07-30 00:05:01] Concluído em 11.3ms.
```

Nos outros dias, quando nada muda:

```
[2026-07-31 00:05:01] Fases verificadas: 6 | Atualizadas: 0
[2026-07-31 00:05:01] Edições verificadas: 1 | Atualizadas: 0
[2026-07-31 00:05:01] Concluído em 9.2ms.
```

---

## Teste manual

Para testar sem aguardar o cron rodar:

```bash
# Via terminal no servidor
php /var/www/html/cron/atualizar_fases_premiacao.php 
```

Ou via browser com o token (bom para testar o staging):

```
https://staging.impactospositivos.com/cron/atualizar_fases_premiacao.php?token=pip2026_cron_secret
```
