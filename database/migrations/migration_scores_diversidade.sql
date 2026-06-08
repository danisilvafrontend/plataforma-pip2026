-- ==========================================================
-- MIGRATION: Scores de Diversidade — Cadastro do Empreendedor
-- Banco: pipnewdb
-- Data: 2026-06-08
-- ==========================================================

USE pipnewdb;

-- ----------------------------------------------------------
-- 1. ADICIONAR COLUNAS NA TABELA empreendedores
--    (apenas se ainda não existirem)
-- ----------------------------------------------------------

ALTER TABLE empreendedores
  ADD COLUMN IF NOT EXISTS orientacao_sexual  VARCHAR(100)
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
      AFTER etnia,
  ADD COLUMN IF NOT EXISTS grupo_vulneravel   VARCHAR(100)
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
      AFTER orientacao_sexual;

-- ----------------------------------------------------------
-- 2. ADICIONAR COLUNAS NA TABELA negociofundadores
--    (cofundadores também podem responder via cadastro de negócio)
-- ----------------------------------------------------------

ALTER TABLE negociofundadores
  ADD COLUMN IF NOT EXISTS orientacao_sexual  VARCHAR(100)
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
      AFTER etnia,
  ADD COLUMN IF NOT EXISTS grupo_vulneravel   VARCHAR(100)
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
      AFTER orientacao_sexual;

-- ----------------------------------------------------------
-- 3. NOVOS CRITÉRIOS NA TABELA lookupscores
--
--    Estrutura existente: componente | opcao | valor
--
--    Regras:
--    a) genero = 'Feminino'          → +5 pts  (fundador principal)
--    b) etnia vulnerável             → +5 pts  (Preto(a), Pardo(a), Indígena)
--    c) orientacao_sexual respondida → +5 pts  (qualquer resposta, incl. "Prefiro não responder")
--    d) grupo_vulneravel respondido  → +5 pts  (qualquer resposta, incl. "Não")
-- ----------------------------------------------------------

INSERT IGNORE INTO lookupscores (componente, opcao, valor) VALUES
  -- (a) Gênero Feminino
  ('genero', 'Feminino', 5),

  -- (b) Etnia/Raça — grupos vulneráveis
  ('etnia', 'Preto(a)',   5),
  ('etnia', 'Pardo(a)',   5),
  ('etnia', 'Indígena',   5),

  -- (c) Orientação sexual — qualquer resposta
  ('orientacao_sexual', 'Heterossexual',        5),
  ('orientacao_sexual', 'Homossexual',           5),
  ('orientacao_sexual', 'Bissexual',             5),
  ('orientacao_sexual', 'Assexual',              5),
  ('orientacao_sexual', 'Outra',                 5),
  ('orientacao_sexual', 'Prefiro não responder', 5),

  -- (d) Grupo vulnerável — qualquer resposta
  ('grupo_vulneravel', 'Pessoa com deficiência', 5),
  ('grupo_vulneravel', 'Pessoa refugiada',        5),
  ('grupo_vulneravel', 'Não',                     5);

-- ----------------------------------------------------------
-- Verificação rápida após a migration
-- ----------------------------------------------------------
-- SELECT * FROM lookupscores
-- WHERE componente IN ('genero','etnia','orientacao_sexual','grupo_vulneravel')
-- ORDER BY componente, opcao;
