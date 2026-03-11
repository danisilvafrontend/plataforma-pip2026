<?php
require_once __DIR__ . '/../helpers/functions.php';

function validar_empreendedor(array $data): array {
    $erros = [];

    // Nome
    if (empty($data['nome'])) $erros['nome'] = 'Informe seu nome.';
    if (empty($data['sobrenome'])) $erros['sobrenome'] = 'Informe seu sobrenome.';

    // CPF
    if (!empty($data['cpf']) && !isValidCPF($data['cpf'])) {
        $erros['cpf'] = 'CPF inválido.';
    }

    // Email
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'E-mail inválido.';
    }

    // Celular (opcional, mas se vier, normaliza)
    if (!empty($data['celular'])) {
        $data['celular'] = only_digits($data['celular']);
        if (strlen($data['celular']) < 10) $erros['celular'] = 'Celular inválido.';
    }

    // Data de nascimento (opcional)
    if (!empty($data['data_nascimento'])) {
        $d = DateTime::createFromFormat('Y-m-d', $data['data_nascimento']);
        if (!$d) $erros['data_nascimento'] = 'Data de nascimento inválida (use AAAA-MM-DD).';
    }

    // Senha
    if (empty($data['senha']) || strlen($data['senha']) < 8) {
        $erros['senha'] = 'A senha deve ter ao menos 8 caracteres.';
    }
    if ($data['senha'] !== ($data['senha_confirm'] ?? '')) {
        $erros['senha_confirm'] = 'As senhas não coincidem.';
    }

    return $erros;
}
