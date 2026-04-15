# Módulo: sociedadecivil/

Área de cadastro e conta dos usuários de Sociedade Civil da Plataforma Impactos Positivos. Esse perfil representa cidadãos, profissionais, estudantes e voluntários que desejam acompanhar negócios de impacto, votar no prêmio, explorar a vitrine e se conectar com a rede. O cadastro é público, simplificado (3 etapas) e voltado para engajamento — diferente dos módulos de empreendedores e parceiros.

***

## Estrutura de Arquivos

```
sociedadecivil/
├── editarconta.php       # Edição de dados pessoais e de acesso
├── editarinteresse.php   # Edição de interesses temáticos e ODS
└── minhaconta.php        # Painel/perfil da conta do usuário
```

> O cadastro público da Sociedade Civil é realizado em `/cadastro.php` (raiz), que usa o formulário `cadastro2.php` com 3 etapas. O processamento é feito em `auth/processarcadastrosociedade.php`.

***

## Fluxo de Cadastro (3 Etapas — Público)

O cadastro é iniciado na página pública `/cadastro.php` e conduzido em 3 etapas com progresso visual e validação em tempo real.

| Etapa | Label | Conteúdo |
|-------|-------|----------|
| 1 | Dados | CPF, data de nascimento, nome, sobrenome, e-mail, celular, CEP (autocomplete via ViaCEP), cidade, estado, senha e confirmação de senha |
| 2 | Interesses | Temas de interesse, ODS selecionados da tabela `ods` |
| 3 | Perfil | Estágio de maturidade preferido, setores de interesse, perfil de impacto, identificação pessoal e motivações |

### Campos Relevantes da Etapa 1

| Campo | Tipo | Observação |
|-------|------|------------|
| `cpf` | TEXT | Validado com máscara `000.000.000-00` |
| `datanascimento` | DATE | Obrigatório |
| `nome` / `sobrenome` | TEXT | Obrigatórios |
| `email` | EMAIL | Único no sistema |
| `celular` | TEXT | Máscara `(00) 00000-0000` |
| `cep` | TEXT | Preenche cidade/estado automaticamente via ViaCEP |
| `email_autorizacao` | CHECKBOX | Aceite de notificações por e-mail |
| `celular_autorizacao` | CHECKBOX | Aceite de notificações por WhatsApp |
| `senha` / `senha_confirmacao` | PASSWORD | Mínimo 8 caracteres |

### Campos Relevantes da Etapa 1 — Perfil Profissional

| Campo | Tipo | Opções |
|-------|------|--------|
| `profissao` | SELECT | Saúde, Educação, Tecnologia, Agronegócio, Serviços, Outro |
| `organizacao` | TEXT | Opcional — onde trabalha |
| `identificacoes` | CHECKBOX (múltiplo) | Sociedade civil / cidadão, Profissional, Estudante, Voluntário/a, Empreendedor/a, Investidor/a, Outro |
| `motivacoes` | CHECKBOX (múltiplo) | Votar no prêmio, Conhecer negócios, Engajar, Voluntariado, Investir/doar, Outro |

### Campos Relevantes da Etapa 2 — Interesses

| Campo | Tipo | Observação |
|-------|------|------------|
| `interesses[]` | CHECKBOX (múltiplo) | 16 temas (Meio Ambiente, Água, Biodiversidade, Economia Circular, Energia, Saúde, Educação, Igualdade de Gênero, Equidade Racial, Trabalho, Cidades, Inovação, Inclusão, Governança, Parcerias etc.) |
| `ods[]` | CHECKBOX (múltiplo) | 17 ODS carregados da tabela `ods` com ícone e nome |

### Campos Relevantes da Etapa 3 — Mapeamento

| Campo | Tipo | Opções |
|-------|------|--------|
| `maturidade[]` | CHECKBOX (múltiplo) | Ideação, Validação, Crescimento, Escala |
| `setores[]` | CHECKBOX (múltiplo) | Tecnologia, Agronegócio sustentável, Saúde, Educação, Finanças de impacto, Energia, Moda sustentável, Alimentação, Construção civil, Cultura, ESG corporativo, Startups, Negócios sociais, Cooperativas, ONGs |
| `perfilimpacto[]` | CHECKBOX (múltiplo) | Social, Ambiental, Social+Ambiental, Inovação tecnológica, Base comunitária, Liderado por mulheres, Liderado por jovens, Impacto regional/local, Impacto global |

***

## Tabela Principal: `usuarios_sociedade_civil` (ou `users` com role `sociedade`)

O usuário de Sociedade Civil é registrado na tabela `users` com `role = sociedade`. Os dados complementares (interesses, ODS, perfil de impacto) são armazenados em tabelas associadas.

### Tabelas Relacionadas

| Tabela | Conteúdo |
|--------|----------|
| `users` | Dados de acesso: e-mail, senha, role, status |
| `sociedade_perfil` (ou campo JSON) | CPF, data de nascimento, profissão, organização, identificações, motivações |
| `sociedade_interesses` | Temas de interesse selecionados |
| `sociedade_ods` | ODS selecionados |
| `sociedade_mapeamento` | Estágio de maturidade, setores e perfil de impacto |

> Confirmar nomes exatos das tabelas no banco consultando `app/config/db.php` ou as migrations.

***

## Área Logada (`sociedadecivil/`)

Após o cadastro e login, o usuário acessa sua área pessoal com três telas:

| Arquivo | Descrição |
|---------|-----------|
| `minhaconta.php` | Resumo do perfil: nome, localização, identificações, motivações e interesses |
| `editarconta.php` | Edição de dados pessoais: nome, e-mail, celular, endereço, profissão, senha |
| `editarinteresse.php` | Edição de interesses temáticos, ODS, maturidade, setores e perfil de impacto |

***

## Autenticação

O login da Sociedade Civil é realizado pela tela pública `/login.php`, que verifica credenciais na tabela `users` e valida `role = sociedade`. O formulário de login público está em:

```
app/views/forms/form-loginsociedade.php
```

A proteção de rotas internas usa `requireSociedadeLogin()` definida em `app/helpers/auth.php`.

***

## Diferenças em Relação aos Outros Módulos

| Aspecto | Sociedade Civil | Empreendedor | Parceiro |
|---------|----------------|--------------|---------|
| Cadastro público | ✅ Sim | ✅ Sim | ✅ Sim |
| Nº de etapas | 3 | — (conta) + 9 (negócio) | 6 |
| Objetivo principal | Engajamento e votação | Inscrever negócio na vitrine | Formalizar parceria via acordo |
| Exige aprovação admin | ❌ Não | ✅ Sim (negócio) | ✅ Sim |
| Carta-Acordo | ❌ Não | ❌ Não | ✅ Sim |
| Pode votar no prêmio | ✅ Sim | — | — |
| Perfil público na vitrine | ❌ Não | ✅ (via negócio) | ✅ Sim |

***

## Votação no Prêmio

Usuários de Sociedade Civil são o público votante do prêmio. A lógica de votação está em:

```
app/helpers/votacao.php
```

O controle de votos impede múltiplos votos do mesmo usuário por edição, validado por `user_id` e `edicao_id`.

***

## View de Login Pública

```
app/views/forms/form-loginsociedade.php
```

Formulário de login com campos `email` e `senha`, estilizado com o padrão Bootstrap 5 da plataforma. Redirecionamento pós-login para `sociedadecivil/minhaconta.php`.