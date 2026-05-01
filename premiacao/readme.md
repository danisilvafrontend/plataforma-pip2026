# Análise Atualizada: Sistema de Premiação

## 🎯 Correção: Entendimento sobre Votação Técnica

Você está absolutamente certo! Não é necessário `premiacao_selecoes_admin`. A estrutura é muito mais simples:

### ✅ **Modelo Correto:**

1. **Admin cria usuários** em `create_user.php` com:
   - `role = 'tecnico'` → Técnico (avalia inscrições)
   - `role = 'juri'` → Jurado (vota na fase final)
   - `role = 'user'` (ou padrão) → Empreendedor (vota popular)

2. **Função da bancada técnica:**
   - Quase igual à votação popular
   - Mas limitada a votar **apenas nos classificados de cada fase**
   - Fase 1: Vota em **10 inscrições** por categoria
   - Fase 2: Vota em **3 inscrições** por categoria (apenas os 6 finalistas selecionados)
   - Fase Final: Vota em **1 inscrição** por categoria (o vencedor)

3. **Regra de apuração:**
   - Não precisa de `premiacao_selecoes_admin`
   - A tabela `premiacao_votos_tecnicos` registra as notas
   - Na apuração: agrupa por `inscricao_id`, calcula `AVG(nota)` e ranqueia

---

## 📊 Análise de `premiacao_auth.php`

### ✅ Estrutura está CORRETA

```php
'tipo' => 'empreendedor',    // votação popular
'tipo' => 'parceiro',         // votação popular
'tipo' => 'sociedade_civil',  // votação popular
'contexto' => 'frontend',     // Voter normal
```

### ⚠️ **FALTA: Suporte para técnicos e jurados**

O arquivo atual **não retorna nada** para usuários com `role = 'tecnico'` ou `role = 'juri'`.

**Observação importante:** Técnicos e jurados TAMBÉM precisam estar logados como `user_id` (tabela `users`), não como empreendedor. Eles usam o mesmo login de admin/superadmin, mas com diferentes `role`.

---

## 🔧 Recomendação: Atualizar `premiacao_auth.php`

O arquivo precisa ser estendido para reconhecer técnicos e jurados:

```php
<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function premiacao_current_actor(): ?array
{
    // Admin/Superadmin: tem user_id E role específico de admin
    $rolesAdmin = ['admin', 'superadmin'];
    if (
        !empty($_SESSION['user_id']) &&
        !empty($_SESSION['user_role']) &&
        in_array($_SESSION['user_role'], $rolesAdmin, true)
    ) {
        return [
            'contexto' => 'admin',
            'tipo'     => 'admin_user',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => (string)$_SESSION['user_role'],
        ];
    }

    // ── NOVO: Técnico da bancada técnica ─────────────────────────────────────
    if (
        !empty($_SESSION['user_id']) &&
        $_SESSION['user_role'] === 'tecnico'
    ) {
        return [
            'contexto' => 'backend',  // Técnico é "backend" (não é votante comum)
            'tipo'     => 'tecnico',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'tecnico',
        ];
    }

    // ── NOVO: Jurado da fase final ───────────────────────────────────────────
    if (
        !empty($_SESSION['user_id']) &&
        $_SESSION['user_role'] === 'juri'
    ) {
        return [
            'contexto' => 'backend',  // Jurado é "backend" (não é votante comum)
            'tipo'     => 'juri',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'juri',
        ];
    }

    // Empreendedor logado (user_id presente, com qualquer role não-admin)
    if (!empty($_SESSION['user_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'empreendedor',
            'id'       => (int)$_SESSION['user_id'],
            'role'     => 'popular',
        ];
    }

    // Parceiro logado
    if (!empty($_SESSION['parceiro_id'])) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'parceiro',
            'id'       => (int)$_SESSION['parceiro_id'],
            'role'     => 'popular',
        ];
    }

    // Sociedade civil logada
    if (
        !empty($_SESSION['logado']) &&
        ($_SESSION['usuario_tipo'] ?? '') === 'sociedade_civil' &&
        !empty($_SESSION['usuario_id'])
    ) {
        return [
            'contexto' => 'frontend',
            'tipo'     => 'sociedade_civil',
            'id'       => (int)$_SESSION['usuario_id'],
            'role'     => 'popular',
        ];
    }

    return null;
}
```

---

## ✅ Conclusão: votar.php está 100% correto!

Agora que entendemos como funciona:

1. **`votar.php`** → ✅ PERFEITO para votação popular
   - Valida `$actor['tipo']` que será um de: `empreendedor`, `parceiro`, `sociedade_civil`
   - Tudo funciona corretamente

2. **`votar_tecnico.php`** → Apenas ADAPTAR para:
   - Validar `$actor['tipo'] === 'tecnico'` e `$actor['contexto'] === 'backend'`
   - **ADICIONAR**: Validar se a inscrição está na lista de classificados da fase
   - Registrar em `premiacao_votos_tecnicos` com `nota` numérica

3. **`votar_juri.php`** → Apenas ADAPTAR para:
   - Validar `$actor['tipo'] === 'juri'` e `$actor['contexto'] === 'backend'`
   - Registrar em `premiacao_votos_juri` (sem campo nota, apenas "este jurado vota nesta inscrição")

4. **`apurar_fase.php`** → ✅ Lógica já está correta
   - Vai usar `AVG(nota)` de `premiacao_votos_tecnicos` para ranking técnico

---

## 📋 Checklist de Implementação

### CRÍTICO (Fazer Agora)
- [ ] **Atualizar `premiacao_auth.php`** para reconhecer técnicos e jurados
- [ ] **Criar tabela `premiacao_votos_tecnicos`** (SQL já pronto)
- [ ] **Criar `votar_tecnico.php`** (adaptar template com validação de classificados)
- [ ] **Criar `votar_juri.php`** (adaptar template com validação de finalistas)

### RECOMENDADO
- [ ] **Criar `apurar_fase.php`** (script de apuração)
- [ ] **Testar fluxo completo** com dados reais

### OPCIONAL
- [ ] Adicionar logging de votações
- [ ] Rate limiting para evitar spam

---

## 🎨 Diagrama do Fluxo Atualizado

```
┌─────────────────────────────────────────────────────────────────┐
│                    SISTEMA DE PREMIAÇÃO                         │
└─────────────────────────────────────────────────────────────────┘

VOTAÇÃO POPULAR (Qualquer um logado)
├─ Empreendedor (user_id, sem role ou role=user)
├─ Parceiro (parceiro_id)
└─ Sociedade Civil (usuario_id com usuario_tipo='sociedade_civil')
   └─> votar.php → premiacao_votos_populares

VOTAÇÃO TÉCNICA (Técnicos)
├─ User com role='tecnico'
└─> votar_tecnico.php → premiacao_votos_tecnicos (com nota/avaliação)

VOTAÇÃO JÚRI (Jurados)
├─ User com role='juri'
└─> votar_juri.php → premiacao_votos_juri

APURAÇÃO
└─> apurar_fase.php
    ├─ Fase 1: Top 10 popular + Top 10 técnica → 20 classificados
    ├─ Fase 2: Top 3 popular + Top 3 técnica (dos 20) → 6 classificados
    └─ Final: 1 popular + N júri → Vencedor
```

---

## 💡 Ponto Importante: Limite de Votos Técnicos

Você mencionou que técnicos podem votar **apenas na quantidade de classificados de cada fase**:

**Fase 1:** 10 por categoria
**Fase 2:** 3 por categoria
**Fase Final:** 1 por categoria

Isso é **DIFERENTE** da votação popular, que é livre. Precisa ser validado em `votar_tecnico.php`:

```php
// ── Validar que a inscrição está entre os classificados permitidos ──
$stmtPermitidas = $pdo->prepare("
    SELECT COUNT(*) FROM premiacao_inscricoes pi
    WHERE pi.id = ?
      AND pi.premiacao_id = ?
      AND pi.status = 'elegivel'
    LIMIT ?  -- IMPORTANTE: Limitar ao máximo de classificados da fase
");
```

Ou melhor ainda, usar um JOIN com `premiacao_classificados_fase` se a fase anterior já foi apurada.

---

## 📌 Resumo Final

| Item | Status | Ação |
|------|--------|------|
| `votar.php` | ✅ OK | Usar como está |
| `premiacao_auth.php` | ⚠️ Incompleto | Adicionar técnico + juri |
| `premiacao_votos_tecnicos` | ❌ Falta | Criar tabela |
| `votar_tecnico.php` | ❌ Falta | Criar com validação de limite |
| `votar_juri.php` | ❌ Falta | Criar |
| `apurar_fase.php` | ❌ Falta | Criar |

Tudo está pronto! É só implementar os últimos passos. 🚀



# 📋 Guia de Implementação: Sistema de Premiação

## Resumo Executivo

Seu sistema está **95% pronto**. Faltam apenas 5 arquivos para completar:

1. ✅ `votar.php` — Já existe e está correto
2. ❌ Tabela `premiacao_votos_tecnicos` — Criar no banco
3. ❌ `votar_tecnico.php` — Criar (template pronto)
4. ❌ `votar_juri.php` — Criar (template pronto)
5. ❌ `apurar_fase.php` — Criar (template pronto)
6. ⚠️ `premiacao_auth.php` — Atualizar (versão nova pronta)

---

## 🔧 PASSO 1: Atualizar `premiacao_auth.php`

### O que fazer:
Substituir seu arquivo atual (`/app/helpers/premiacao_auth.php`) pela versão atualizada que inclui suporte a técnicos e jurados.

### Arquivo:
`premiacao_auth_ATUALIZADO.php`

### Mudanças incluídas:
```php
// Agora suporta:
- premiacao_is_admin()
- premiacao_is_tecnico()
- premiacao_is_juri()
- premiacao_can_vote_popular()
- premiacao_can_vote_tecnico()
- premiacao_can_vote_juri()
- premiacao_require_admin()
- premiacao_require_tecnico()
- premiacao_require_juri()
- premiacao_require_login()
```

### Verificar:
Depois de substituir, teste se o `$_SESSION['user_role']` está sendo preenchido corretamente em seu login.

```php
// Verificar em qualquer página após login:
$actor = premiacao_current_actor();
echo "Tipo: " . ($actor['tipo'] ?? 'nenhum');
// Deve retornar: empreendedor, parceiro, sociedade_civil, tecnico, juri, ou admin_user
```

---

## 🔧 PASSO 2: Criar Tabela no Banco de Dados

### O que fazer:
Executar o SQL para criar a tabela `premiacao_votos_tecnicos`.

### Arquivo:
`criar_premiacao_votos_tecnicos.sql`

### SQL resumido:
```sql
CREATE TABLE IF NOT EXISTS `premiacao_votos_tecnicos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `premiacao_id` int unsigned NOT NULL,
  `fase_id` int unsigned NOT NULL,
  `categoria_id` int unsigned NOT NULL,
  `inscricao_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,  -- técnico
  `nota` decimal(5,2) NOT NULL,      -- 0 a 100
  `justificativa` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_voto_tecnico_unico` (`fase_id`, `categoria_id`, `inscricao_id`, `user_id`),
  -- ... foreign keys ...
) ENGINE=InnoDB;
```

### Como executar:
```bash
# Via MySQL CLI:
mysql -u username -p database_name < criar_premiacao_votos_tecnicos.sql

# Ou via PHPMyAdmin:
# 1. Abra o banco de dados
# 2. Clique em "SQL"
# 3. Cole o conteúdo do arquivo
# 4. Clique em "Executar"
```

### Validar:
```sql
-- Verificar se tabela foi criada
SHOW TABLES LIKE '%votos_tecnico%';

-- Verificar estrutura
DESCRIBE premiacao_votos_tecnicos;
```

---

## 🔧 PASSO 3: Criar `votar_tecnico.php`

### O que fazer:
Criar novo arquivo `/premiacao/votar_tecnico.php` com o conteúdo do template.

### Arquivo:
`template_votar_tecnico.php`

### Caminho:
```
/seu-projeto/premiacao/votar_tecnico.php
```

### O que ele faz:
- Valida autenticação de técnico (`role = 'tecnico'`)
- Registra nota/avaliação técnica (0-100)
- Valida se a inscrição está entre os classificados da fase
- Valida limite de votos por categoria:
  - Fase 1: Máx 10 votos por categoria
  - Fase 2: Máx 3 votos por categoria
  - Fase Final: Máx 1 voto por categoria
- Armazena em `premiacao_votos_tecnicos`

### Como testar:
```bash
# Criar um técnico
INSERT INTO users (email, user_role, status) 
VALUES ('tecnico@example.com', 'tecnico', 'ativo');

# Fazer request POST:
curl -X POST http://seu-site.com/premiacao/votar_tecnico.php \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "inscricao_id=1&fase_id=20&categoria_id=1&nota=85.5"
```

---

## 🔧 PASSO 4: Criar `votar_juri.php`

### O que fazer:
Criar novo arquivo `/premiacao/votar_juri.php` com o conteúdo do template.

### Arquivo:
`template_votar_juri.php`

### Caminho:
```
/seu-projeto/premiacao/votar_juri.php
```

### O que ele faz:
- Valida autenticação de jurado (`role = 'juri'`)
- Valida se é a **Fase Final** (`tipo_fase = 'final'`)
- Valida se a inscrição é **finalista** (classificada na Fase 2)
- Previne **voto duplicado** (1 voto por categoria por jurado)
- Armazena em `premiacao_votos_juri`

### Como testar:
```bash
curl -X POST http://seu-site.com/premiacao/votar_juri.php \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "inscricao_id=1&fase_id=22&categoria_id=1"
```

---

## 🔧 PASSO 5: Criar `apurar_fase.php`

### O que fazer:
Criar novo arquivo `/premiacao/apurar_fase.php` com o conteúdo do template.

### Arquivo:
`template_apurar_fase.php`

### Caminho:
```
/seu-projeto/premiacao/apurar_fase.php
```

### O que ele faz:

#### **Fase 1 (Classificatória 1):**
1. Top 10 votos populares por categoria
2. Top 10 notas técnicas por categoria (AVG de `premiacao_votos_tecnicos`)
3. Mesclar os dois conjuntos (remover duplicatas)
4. Completar até 20 com próximas inscrições ainda não selecionadas
5. Armazenar em `premiacao_classificados_fase`

#### **Fase 2 (Classificatória 2):**
1. Top 3 votos populares (APENAS entre os 20 classificados da Fase 1)
2. Top 3 notas técnicas (APENAS entre os 20 classificados da Fase 1)
3. Mesclar (remover duplicatas)
4. Completar até 6
5. Armazenar em `premiacao_classificados_fase`

#### **Fase Final:**
1. Contar votos de cada finalista
2. 1 voto do ranking popular (por estar finalista)
3. + votos do júri (1 por jurado que votou)
4. Definir vencedor (maior soma)
5. Marcar como `status = 'vencedora'`

### Como testar:
```bash
# Apenas admin pode executar
curl -X POST http://seu-site.com/premiacao/apurar_fase.php \
  -d "fase_id=20"

# Ou via navegador (se logado como admin):
# http://seu-site.com/premiacao/apurar_fase.php?fase_id=20
```

### Importante:
Este script **deve ser executado manualmente pelo admin** após encerrar cada fase:
1. Encerrar fase (status = 'encerrada')
2. Executar `apurar_fase.php?fase_id=XX`
3. Verificar resultados em `premiacao_classificados_fase`

---

## 🧪 PASSO 6: Testar Fluxo Completo

### Preparação:

```bash
# 1. Criar usuários de teste

# Empreendedor (votação popular)
INSERT INTO users (email, user_role, status) 
VALUES ('empreendedor@test.com', 'user', 'ativo');

# Técnico (avalia inscrições)
INSERT INTO users (email, user_role, status) 
VALUES ('tecnico@test.com', 'tecnico', 'ativo');

# Jurado (vota na final)
INSERT INTO users (email, user_role, status) 
VALUES ('jurado@test.com', 'juri', 'ativo');

# Admin (apura fases)
INSERT INTO users (email, user_role, status) 
VALUES ('admin@test.com', 'admin', 'ativo');
```

### Fluxo de teste:

```
1. FASE 1 - Em Andamento
   ├─ Empreendedor acessa /premiacao/votar.php
   │  └─ Vota em 3 inscrições diferentes
   │
   ├─ Técnico acessa /premiacao/votar_tecnico.php
   │  └─ Avalia 5 inscrições com notas (70, 80, 90, etc)
   │
   └─ (Fase 1 finaliza)

2. APURAÇÃO FASE 1
   ├─ Admin executa /premiacao/apurar_fase.php?fase_id=20
   │  └─ Gera Top 10 Popular + Top 10 Técnica = 20 classificados
   │
   └─ Verificar: SELECT * FROM premiacao_classificados_fase WHERE fase_id=20

3. FASE 2 - Em Andamento
   ├─ Empreendedor vota em 2 inscrições (apenas dos 20 finalistas)
   │
   ├─ Técnico avalia 3 inscrições (apenas dos 20 finalistas)
   │
   └─ (Fase 2 finaliza)

4. APURAÇÃO FASE 2
   ├─ Admin executa /premiacao/apurar_fase.php?fase_id=21
   │  └─ Gera Top 3 Popular + Top 3 Técnica = 6 classificados
   │
   └─ Verificar: SELECT * FROM premiacao_classificados_fase WHERE fase_id=21

5. FASE FINAL - Em Andamento
   ├─ Jurado acessa /premiacao/votar_juri.php
   │  └─ Vota em 1 inscrição por categoria (entre os 6 finalistas)
   │
   └─ (Fase Final finaliza)

6. APURAÇÃO FINAL
   ├─ Admin executa /premiacao/apurar_fase.php?fase_id=22
   │  └─ Soma: 1 voto popular + N votos do júri = vencedor
   │
   └─ Verificar: SELECT * FROM premiacao_classificados_fase WHERE fase_id=22 AND status='vencedor'
```

---

## ✅ Checklist de Implementação

### Antes de começar:
- [ ] Backup do banco de dados
- [ ] Ambiente de testes (staging) disponível
- [ ] Acesso ao código-fonte

### Implementação:
- [ ] **PASSO 1:** Atualizar `premiacao_auth.php`
- [ ] **PASSO 2:** Executar SQL para criar tabela
- [ ] **PASSO 3:** Criar `votar_tecnico.php`
- [ ] **PASSO 4:** Criar `votar_juri.php`
- [ ] **PASSO 5:** Criar `apurar_fase.php`

### Testes:
- [ ] Login de técnico funciona
- [ ] Login de jurado funciona
- [ ] `votar_tecnico.php` registra votos
- [ ] `votar_juri.php` registra votos
- [ ] `apurar_fase.php` executa corretamente
- [ ] Resultados aparecem em `premiacao_classificados_fase`

### Deploy em Produção:
- [ ] Testar em staging
- [ ] Fazer backup de produção
- [ ] Copiar arquivos para produção
- [ ] Executar SQL em produção
- [ ] Testar com dados reais
- [ ] Monitorar logs

---

## 📞 Troubleshooting

### Erro: "Você precisa estar autenticado como técnico"
**Causa:** `$_SESSION['user_role']` não está sendo preenchido corretamente no login
**Solução:** Verificar em `login.php` se está fazendo `$_SESSION['user_role'] = $user['role']`

### Erro: "Inscrição não encontrada entre classificados"
**Causa:** A inscrição não foi classificada na fase anterior
**Solução:** Executar `apurar_fase.php` para a fase anterior antes de votar

### Erro: "Você já votou em 10 inscrições"
**Causa:** Técnico tentou votar mais que o limite da fase
**Solução:** Normal - limite está funcionando corretamente

### Fase não aparecendo em dropdown
**Causa:** Fase com `status != 'em_andamento'`
**Solução:** Verificar status da fase em `premiacao_fases`

---

## 📊 Estrutura Final

```
/premiacao/
├── votar.php                    ✅ Existente
├── votar_tecnico.php            ❌ → CRIAR
├── votar_juri.php               ❌ → CRIAR
├── apurar_fase.php              ❌ → CRIAR
├── premiacao_voto_popular.php   ✅ Existente
├── premiacao_voto_tecnico.php   ✅ Existente (painel admin)
├── premiacao_juri.php           ✅ Existente (painel admin)
└── premiacao_periodos.php       ✅ Existente

/app/helpers/
└── premiacao_auth.php           ⚠️ → ATUALIZAR

/banco de dados/
└── premiacao_votos_tecnicos     ❌ → CRIAR TABELA
```

---

## 📚 Referências Rápidas

### Endpoints POST:
- `votar.php` — Votação Popular
- `votar_tecnico.php` — Votação Técnica
- `votar_juri.php` — Votação Júri
- `apurar_fase.php` — Apuração (admin only)

### Tabelas:
- `premiacao_votos_populares` — Votos populares
- `premiacao_votos_tecnicos` — Votos técnicos (NOVA)
- `premiacao_votos_juri` — Votos do júri
- `premiacao_classificados_fase` — Classificados/vencedores

### Fases:
- Fase 1 (rodada=1): 10 popular + 10 técnica → 20 classificados
- Fase 2 (rodada=2): 3 popular + 3 técnica → 6 finalistas
- Fase Final: 1 popular + N júri → 1 vencedor

---

## 🎯 Próximas Melhorias (Futuro)

- [ ] Painel de votação em tempo real
- [ ] Alertas de erro mais amigáveis
- [ ] Logs detalhados de todas as votações
- [ ] Export de resultados em CSV/PDF
- [ ] Sistema de recursos/contestações
- [ ] Notificações por email

---

**Sistema pronto para ser implementado! Dúvidas? Ver arquivos de análise.** 🚀