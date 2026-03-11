<?php
// _shared.php - helpers reutilizáveis para os blocos de cadastro

// Segurança: escape para saída HTML
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Escape para atributos (mesma coisa, mas sem quebra de linha)
function attr(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Formata CNPJ/CPF (aceita string numérica)
function formatCNPJ(string $cnpj): string {
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if ($cnpj === '') return '';
    if (strlen($cnpj) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
    }
    if (strlen($cnpj) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }
    return $cnpj;
}

// Formata CPF simples
function formatCPF(string $cpf): string {
    $cpf = preg_replace('/\D/', '', $cpf);
    if ($cpf === '' || strlen($cpf) !== 11) return 'Não informado';
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Formata data para BR
function formatDateBR(?string $date): string {
    if (empty($date)) return 'Não informado';
    $ts = strtotime($date);
    if ($ts === false) return 'Não informado';
    return date('d/m/Y', $ts);
}

// Formata telefone brasileiro
function formatPhone(?string $phone): string {
    if (empty($phone)) return 'Não informado';
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 10) {
        return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $digits);
    }
    if (strlen($digits) === 11) {
        return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $digits);
    }
    return $phone;
}

// Extrai ID do YouTube e retorna URL embed segura
function embedYouTube(string $url): string {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]{6,})/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // fallback: retorna valor original (use com cuidado)
    return $url;
}

// Gera ID único para evitar colisões entre partials
function unique_id(string $prefix = 'id'): string {
    return $prefix . '-' . bin2hex(random_bytes(4));
}

// Renderiza um partial com escopo isolado
// $path relativo ao diretório de blocos ou caminho absoluto
// $vars array de variáveis que serão extraídas no escopo do partial
function render_partial(string $path, array $vars = []): void {
    if (!file_exists($path)) {
        trigger_error("Partial not found: {$path}", E_USER_WARNING);
        return;
    }
    extract($vars, EXTR_SKIP);
    include $path;
}

// Decodifica JSON de galeria com fallback seguro
function gallery_from_apresentacao(array $apresentacao): array {
    $raw = $apresentacao['galeria_imagens'] ?? '[]';
    $arr = json_decode($raw, true);
    if (!is_array($arr)) return [];
    // filtra strings vazias e normaliza
    return array_values(array_filter(array_map('trim', $arr)));
}

// Decodifica links adicionais com fallback
function links_from_apresentacao(array $apresentacao): array {
    $raw = $apresentacao['info_adicionais_links'] ?? '[]';
    $arr = json_decode($raw, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('trim', $arr)));
}

// Helper para renderizar badge com classe opcional
function render_badge(string $text, string $class = 'bg-secondary'): string {
    return '<span class="badge ' . e($class) . ' me-1 mb-1">' . e($text) . '</span>';
}

// Pequeno wrapper para consultas PDO seguras (retorna fetchAll)
function pdo_fetch_all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pequeno wrapper para fetch único
function pdo_fetch_one(PDO $pdo, string $sql, array $params = []): array|false {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// _shared.php - helpers reutilizáveis

function buscarFinanceiro(PDO $pdo, int $negocio_id): array {
    $stmt = $pdo->prepare("SELECT * FROM negocio_financeiro WHERE negocio_id = ?");
    $stmt->execute([$negocio_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// ===========================
// Helpers para arrays JSON
// ===========================

// Decodifica um campo JSON salvo no banco (retorna array)
function decode_json_array(?string $json): array {
    if (!$json) return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

// Renderiza lista de itens (chips/badges)
function render_badges(array $items, string $color = 'primary'): string {
    if (empty($items)) return '<span class="small-muted">Não informado</span>';
    $html = '<div class="d-flex flex-wrap gap-2">';
    foreach ($items as $item) {
        $html .= '<span class="badge bg-' . e($color) . ' px-3 py-2">' . e($item) . '</span>';
    }
    $html .= '</div>';
    return $html;
}

// ===========================
// Helpers específicos Etapa 7 (Impacto)
// ===========================

// Beneficiários (até 3)
function impacto_beneficiarios(array $impacto): array {
    return decode_json_array($impacto['beneficiarios'] ?? '[]');
}

// Métricas (até 8)
function impacto_metricas(array $impacto): array {
    return decode_json_array($impacto['metricas'] ?? '[]');
}

// Formas de medição (até 4)
function impacto_formas_medicao(array $impacto): array {
    return decode_json_array($impacto['formas_medicao'] ?? '[]');
}

// Links de resultados (até 4)
function impacto_links(array $impacto): array {
    return decode_json_array($impacto['resultados_links'] ?? '[]');
}

// PDFs de resultados (até 4)
function impacto_pdfs(array $impacto): array {
    return decode_json_array($impacto['resultados_pdfs'] ?? '[]');
}
