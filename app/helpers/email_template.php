<?php
// app/helpers/email_template.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Renderiza um template PHP e retorna o HTML gerado.
 * $vars é um array associativo com variáveis que o template usará.
 */
function render_email_template(string $templatePath, array $vars = []): string {
    if (!is_file($templatePath)) {
        throw new RuntimeException("Template não encontrado: {$templatePath}");
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    include $templatePath;
    return (string) ob_get_clean();
}