<?php
// app/helpers/render.php
function render_email_template($filePath, $vars = []) {
    if (!file_exists($filePath)) {
        return '';
    }
    // Extrai variáveis para uso dentro do template
    extract($vars);
    ob_start();
    include $filePath;
    return ob_get_clean();
}

function render_email_from_db($subject, $bodyHtml, $vars = []) {
    foreach ($vars as $key => $value) {
        $bodyHtml = str_replace('{{'.$key.'}}', htmlspecialchars($value), $bodyHtml);
        $subject  = str_replace('{{'.$key.'}}', htmlspecialchars($value), $subject);
    }

   $header = '
        <div style="background:#1E3425;padding:20px;text-align:center">
            <img src="https://pip2026.dscriacaoweb.com.br/assets/images/impactos_positivos_branco.png"
                alt="Impactos Positivos" style="height:60px">
            <div style="background:#CDDE00;height:5px;margin-top:15px"></div>
        </div>';
    $footer = '
        <div style="background:#000000;padding:20px;text-align:center;font-size:12px;color:#fff">
            <p style="color:#d2de32;font-weight:bold">Impactos Positivos</p>
            <p>© '.date('Y').' Impactos Positivos. Todos os direitos reservados.</p>
            <p>
                <a href="https://www.facebook.com/impactospositivosoficial" target="_blank">
                    <img src="https://pip2026.dscriacaoweb.com.br/assets/images/icons/facebook.png" alt="Facebook" style="height:20px;margin:0 5px">
                </a>
                <a href="https://www.instagram.com/impactospositivosoficial/" target="_blank">
                    <img src="https://pip2026.dscriacaoweb.com.br/assets/images/icons/instagram.png" alt="Instagram" style="height:20px;margin:0 5px">
                </a>
                <a href="https://www.linkedin.com/company/impactos-positivos/" target="_blank">
                    <img src="https://pip2026.dscriacaoweb.com.br/assets/images/icons/linkedin.png" alt="LinkedIn" style="height:20px;margin:0 5px">
                </a>
                <a href="https://www.youtube.com/channel/UCYuEo4Gnyyqvk-J64PrmqzA" target="_blank">
                    <img src="https://pip2026.dscriacaoweb.com.br/assets/images/icons/youtube.png" alt="Youtube" style="height:20px;margin:0 5px">
                </a>
            </p>
            <p style="margin-top:10px;">Você está recebendo este e-mail porque faz parte da rede Impactos Positivos.</p>
        </div>';

    return [
        'subject' => $subject,
        'bodyHtml' => $header . '<div style="padding:20px">'.$bodyHtml.'</div>' . $footer
    ];
}