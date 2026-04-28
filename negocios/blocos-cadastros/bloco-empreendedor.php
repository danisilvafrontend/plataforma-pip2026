<?php
// bloco_empreendedor.php
// Exibe os dados da tabela `empreendedores` vinculada ao negócio,
// independentemente de o empreendedor ser ou não o fundador principal.
// Variáveis esperadas no escopo pai:
//   $negocio_id     (int)
//   $pdo            (PDO)
//   $somenteLeitura (bool, opcional)

require_once __DIR__ . '/_shared.php';

// ── Busca o empreendedor vinculado ao negócio ──────────────────────────────
$empreendedor = pdo_fetch_one($pdo, "
    SELECT e.*
    FROM empreendedores e
    INNER JOIN negocios n ON n.empreendedor_id = e.id
    WHERE n.id = ?
    LIMIT 1
", [$negocio_id]);

// ── Verifica se é o fundador principal ────────────────────────────────────
$ehFundador = false;
if ($empreendedor) {
    $check = pdo_fetch_one($pdo, "
        SELECT id FROM negocio_fundadores
        WHERE negocio_id = ? AND tipo = 'principal'
          AND (
            email = ?
            OR (nome = ? AND sobrenome = ?)
          )
        LIMIT 1
    ", [
        $negocio_id,
        $empreendedor['email']     ?? '',
        $empreendedor['nome']      ?? '',
        $empreendedor['sobrenome'] ?? '',
    ]);
    $ehFundador = !empty($check);
}

$ehAdmin        = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
$somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
?>

<!-- Bloco Empreendedor Responsável -->
<div class="emp-review-card mb-4">

    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-person-badge-fill me-1"></i>
            Empreendedor Responsável

            <?php if ($ehFundador): ?>
                <span class="badge bg-success ms-2" style="font-size:.7rem;">
                    <i class="bi bi-star-fill me-1"></i>Fundador Principal
                </span>
            <?php else: ?>
                <span class="badge bg-secondary ms-2" style="font-size:.7rem;">
                    <i class="bi bi-person me-1"></i>Não é o Fundador
                </span>
            <?php endif; ?>
        </div>

        <?php if (!$ehAdmin && !$somenteLeitura): ?>
            <a href="/empreendedores/editar_conta.php" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">

        <?php if (!$empreendedor): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Empreendedor não encontrado.</strong>
                O registro pode ter sido removido ou há uma inconsistência no banco de dados.
            </div>

        <?php else: ?>

            <div class="row g-4">

                <!-- ── Coluna esquerda: identificação ── -->
                <div class="col-12 col-md-6">
                    <div class="emp-review-group">

                        <div class="emp-review-item">
                            <span class="emp-review-label">Nome Completo</span>
                            <div class="emp-review-value">
                                <?= e(trim(($empreendedor['nome'] ?? '') . ' ' . ($empreendedor['sobrenome'] ?? ''))) ?: 'Não informado' ?>
                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"
                                   title="Dado sensível — visível apenas internamente"></i>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">CPF</span>
                            <div class="emp-review-value">
                                <?= formatCPF($empreendedor['cpf'] ?? '') ?>
                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"
                                   title="Dado sensível — visível apenas internamente"></i>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">E-mail</span>
                            <div class="emp-review-value">
                                <?php if (!empty($empreendedor['email'])): ?>
                                    <a href="mailto:<?= attr($empreendedor['email']) ?>"
                                       class="text-decoration-none">
                                        <?= e($empreendedor['email']) ?>
                                    </a>
                                <?php else: ?>
                                    Não informado
                                <?php endif; ?>
                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"
                                   title="Dado sensível — visível apenas internamente"></i>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">Celular / WhatsApp</span>
                            <div class="emp-review-value">
                                <?= formatPhone($empreendedor['celular'] ?? '') ?>
                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"
                                   title="Dado sensível — visível apenas internamente"></i>
                            </div>
                        </div>

                        <?php if (!empty($empreendedor['telefone'])): ?>
                        <div class="emp-review-item">
                            <span class="emp-review-label">Telefone Fixo</span>
                            <div class="emp-review-value">
                                <?= formatPhone($empreendedor['telefone']) ?>
                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"
                                   title="Dado sensível — visível apenas internamente"></i>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- ── Coluna direita: perfil pessoal ── -->
                <div class="col-12 col-md-6">
                    <div class="emp-review-group">

                        <div class="emp-review-item">
                            <span class="emp-review-label">Data de Nascimento</span>
                            <div class="emp-review-value">
                                <?= formatDateBR($empreendedor['data_nascimento'] ?? '') ?>
                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"
                                   title="Dado sensível — visível apenas internamente"></i>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">Gênero</span>
                            <div class="emp-review-value">
                                <?= e($empreendedor['genero'] ?? '') ?: 'Não informado' ?>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">Etnia / Raça</span>
                            <div class="emp-review-value">
                                <?= e($empreendedor['etnia'] ?? '') ?: 'Não informado' ?>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">Formação</span>
                            <div class="emp-review-value">
                                <?= e($empreendedor['formacao'] ?? '') ?: 'Não informado' ?>
                            </div>
                        </div>

                        <div class="emp-review-item">
                            <span class="emp-review-label">Localização</span>
                            <div class="emp-review-value">
                                <?php
                                $estado    = e($empreendedor['estado']    ?? '');
                                $municipio = e($empreendedor['municipio'] ?? '');
                                if ($estado && $municipio)  echo "$municipio / $estado";
                                elseif ($estado)            echo $estado;
                                elseif ($municipio)         echo $municipio;
                                else                        echo 'Não informado';
                                ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- /row -->

            <!-- ── Metadados da conta — visível só para admin ── -->
            <?php if ($ehAdmin): ?>
            <hr class="my-3">
            <div class="emp-review-subblock-title secondary mb-2">
                <i class="bi bi-gear me-1"></i> Dados da Conta
                <span class="small text-muted">(apenas admin)</span>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="emp-review-item">
                        <span class="emp-review-label">ID</span>
                        <div class="emp-review-value">#<?= (int)$empreendedor['id'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="emp-review-item">
                        <span class="emp-review-label">Status</span>
                        <div class="emp-review-value">
                            <?php
                            $statusMap = [
                                'ativo'     => ['success',   'check-circle-fill', 'Ativo'],
                                'inativo'   => ['secondary', 'dash-circle',       'Inativo'],
                                'dormant'   => ['warning',   'moon-fill',         'Dormant'],
                                'bloqueado' => ['danger',    'x-circle-fill',     'Bloqueado'],
                            ];
                            $s = $statusMap[$empreendedor['status'] ?? '']
                               ?? ['secondary', 'question-circle', $empreendedor['status'] ?? 'N/D'];
                            echo "<span class=\"badge bg-{$s[0]}\">
                                    <i class=\"bi bi-{$s[1]} me-1\"></i>{$s[2]}
                                  </span>";
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="emp-review-item">
                        <span class="emp-review-label">Cadastro</span>
                        <div class="emp-review-value">
                            <?= formatDateBR($empreendedor['created_at'] ?? $empreendedor['data_cadastro'] ?? '') ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="emp-review-item">
                        <span class="emp-review-label">Último Login</span>
                        <div class="emp-review-value">
                            <?= formatDateBR($empreendedor['ultimo_login'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; // $empreendedor ?>

    </div><!-- /emp-review-card-body -->
</div>