# Cron Jobs — Plataforma Impactos Positivos

Este diretório contém os scripts de tarefas agendadas da plataforma.
Eles devem ser executados via **cron job no servidor** — nunca manualmente pelo navegador em produção.

---

## Scripts disponíveis

| Arquivo | Frequência recomendada | O que faz |
|---|---|---|
| `update_status.php` | Diário — uma vez por dia | Atualiza status dos empreendedores (ativo/inativo/dormant) com base no último login |
| `atualizar_fases_premiacao.php` | A cada 5 minutos | Atualiza automaticamente o status das fases e edições da premiação com base nas datas configuradas |

---

## `atualizar_fases_premiacao.php`

### O que faz

Atualiza duas tabelas baseando-se exclusivamente no horário atual comparado às datas cadastradas pelo admin:

**1. `premiacao_fases.status`**

| Condição | Status resultante |
|---|---|
| `NOW() < data_inicio` | `agendada` |
| `data_inicio ≤ NOW() ≤ data_fim` | `em_andamento` |
| `NOW() > data_fim` | `encerrada` |
| Status atual é `rascunho` | Não altera (admin ainda não publicou) |
| Status atual é `apurada` | Não altera (encerrado manualmente pelo admin) |

**2. `premiacoes.status`**

| Situação das fases | Status da edição |
|---|---|
| Nenhuma fase publicada | `planejada` |
| Ao menos 1 fase `em_andamento` | `ativa` |
| Todas as fases `encerradas`/`apuradas` | `encerrada` |
| Só fases `agendadas`, nenhuma ativa | `planejada` |

### Por que a cada 5 minutos?

As fases iniciam às **00h00** e encerram às **23h59**. Rodar a cada 5 minutos garante que a transição acontece no máximo com 5 minutos de atraso — completamente imperceptvel para os usuários.

---

## Configuração no servidor (AWS)

### Opção 1 — Crontab direto na instância EC2 (mais simples)

Conecte na instância via SSH e edite o crontab:

```bash
crontab -e
```

Adicione as linhas abaixo (ajuste o caminho para onde o projeto está instalado):

```cron
# Atualiza status das fases e edições da premiação a cada 5 minutos
*/5 * * * * php /var/www/html/cron/atualizar_fases_premiacao.php >> /var/www/html/storage/logs/cron_premiacao.log 2>&1

# Atualiza status dos empreendedores uma vez por dia às 03h
0 3 * * * php /var/www/html/cron/update_status.php >> /var/www/html/storage/logs/cron_empreendedores.log 2>&1
```

> **Dica:** Para descobrir o caminho correto do PHP no servidor:
> ```bash
> which php
> # ou
> php -v
> ```
> Se retornar `/usr/bin/php8.2`, substitua `php` por `/usr/bin/php8.2` nas linhas acima.

### Opção 2 — AWS EventBridge Scheduler (sem acesso SSH)

Se a plataforma roda no **Elastic Beanstalk**, **ECS**, ou sem acesso direto à instância, use o **EventBridge Scheduler** para disparar uma chamada HTTP ao script:

1. No Console AWS, acesse **EventBridge → Scheduler → Create schedule**
2. Frequência: `rate(5 minutes)`
3. Target: **HTTP** para a URL abaixo (com token de segurança):

```
https://staging.impactospositivos.com/cron/atualizar_fases_premiacao.php?token=SEU_TOKEN_AQUI
```

> O token padrão está definido na variável de ambiente `CRON_SECRET`.
> Se a variável não estiver configurada, o valor padrão é `pip2026_cron_secret`.
> **Importante:** defina um token forte via variável de ambiente no servidor.

### Opção 3 — AWS Lambda + EventBridge (avançado)

Para infraestrutura serverless, crie uma função Lambda simples que faz um GET na URL acima com o token, agendada pelo EventBridge.

---

## Segurança

O script `atualizar_fases_premiacao.php` só aceita chamadas HTTP se o token correto for enviado:

```
# Via query string
https://seudominio.com/cron/atualizar_fases_premiacao.php?token=SEU_TOKEN

# Via header HTTP (para uso com Lambda/EventBridge)
X-Cron-Token: SEU_TOKEN
```

Sem o token, retorna **403 Acesso negado**.

Defina o token no servidor como variável de ambiente:

```bash
# No .bashrc, .env, ou configuração do Elastic Beanstalk
export CRON_SECRET="token_forte_aqui"
```

Via CLI diretamente sempre funciona sem token (PHP_SAPI === 'cli').

---

## Verificando se o cron está funcionando

Os logs ficam em `storage/logs/`. Para checar:

```bash
# Últimas execuções
tail -50 /var/www/html/storage/logs/cron_premiacao.log

# Acompanhar em tempo real
tail -f /var/www/html/storage/logs/cron_premiacao.log
```

Exemplo de saída esperada:

```
[2026-05-01 00:00:02] Fase #3 (Voto Popular): agendada → em_andamento
[2026-05-01 00:00:02] Edição #1 (PIP 2026): planejada → ativa
[2026-05-01 00:00:02] Fases verificadas: 5 | Atualizadas: 1
[2026-05-01 00:00:02] Edições verificadas: 1 | Atualizadas: 1
[2026-05-01 00:00:02] Concluído em 12.4ms.
```

Se nenhuma fase mudar de status naquela execução, o log mostrará apenas:

```
[2026-05-01 00:05:02] Fases verificadas: 5 | Atualizadas: 0
[2026-05-01 00:05:02] Edições verificadas: 1 | Atualizadas: 0
[2026-05-01 00:05:02] Concluído em 8.1ms.
```

---

## Teste manual

Para testar sem aguardar o cron rodar, execute diretamente no servidor:

```bash
php /var/www/html/cron/atualizar_fases_premiacao.php
```

Ou acesse via browser com o token:

```
https://staging.impactospositivos.com/cron/atualizar_fases_premiacao.php?token=pip2026_cron_secret
```
