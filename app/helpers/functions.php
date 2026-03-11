<?php
// Funções utilitárias globais: sanitização, normalização e validação básica (CPF e CNPJ)

function sanitize_text($value) {
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function sanitize_email($value) {
    return trim(filter_var($value, FILTER_SANITIZE_EMAIL));
}


/**
 * Remove todos os caracteres não numéricos de uma string
 */
if (!function_exists('only_digits')) {
    function only_digits(string $s): string {
        return preg_replace('/\D/', '', $s);
    }
}

/**
 * Valida CPF usando o algoritmo oficial
 */
if (!function_exists('isValidCPF')) {
    function isValidCPF(string $cpf): bool {
        $cpf = only_digits($cpf);
        if (strlen($cpf) !== 11) return false;
        
        // Rejeita CPFs com todos os dígitos iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;
        
        // Calcula primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$cpf[$i] * (10 - $i);
        }
        $r = $sum % 11;
        $d1 = ($r < 2) ? 0 : 11 - $r;
        if ($d1 !== (int)$cpf[9]) return false;
        
        // Calcula segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int)$cpf[$i] * (11 - $i);
        }
        $r = $sum % 11;
        $d2 = ($r < 2) ? 0 : 11 - $r;
        
        return $d2 === (int)$cpf[10];
    }
}

/**
 * Valida CNPJ usando o algoritmo oficial da Receita Federal
 */
if (!function_exists('isValidCNPJ')) {
    function isValidCNPJ(string $cnpj): bool {
        $cnpj = only_digits($cnpj);
        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        $calc = function(string $cnpjStr, int $pos) {
            $soma = 0;
            $peso = $pos - 8; // CORREÇÃO: iniciar com pos-8 para gerar os pesos oficiais
            for ($i = 0; $i < $pos - 1; $i++) {
                $soma += (int)$cnpjStr[$i] * $peso;
                $peso = ($peso === 2) ? 9 : $peso - 1;
            }
            $res = $soma % 11;
            return ($res < 2) ? 0 : 11 - $res;
        };

        $d1 = $calc($cnpj, 13);
        $d2 = $calc($cnpj, 14);
        return $d1 === (int)$cnpj[12] && $d2 === (int)$cnpj[13];
    }
}


function consultar_cpf_receitaws(string $cpf): array {
    $cpf = preg_replace('/\D/', '', $cpf);
    $url = "https://receitaws.com.br/v1/cpf/{$cpf}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}
