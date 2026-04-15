<?php
session_start();

// Se não tiver sessão ativa, redireciona pro login
if (empty($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'sociedade_civil') {
    header("Location: /login.php");
    exit;
}

include __DIR__ . '/app/views/public/header_public.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5 text-center">

                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-3"
                             style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>

                        <h1 class="mb-3 text-success fw-bold">Cadastro realizado com sucesso!</h1>
                        <p class="lead mb-2">
                            Obrigado por se cadastrar na Sociedade Civil.
                        </p>
                        <p class="text-muted mb-0">
                            Seu perfil foi criado e seu acesso já está disponível.
                        </p>
                    </div>

                    <div class="alert alert-light border rounded-4 text-start mt-4 mb-4">
                        <h2 class="h5 fw-bold mb-3">O que você pode fazer agora</h2>
                        <ul class="mb-0 ps-3">
                            <li>Acessar sua conta e acompanhar suas informações.</li>
                            <li>Explorar a vitrine de iniciativas e negócios de impacto.</li>
                            <li>Conhecer os parceiros e organizações do ecossistema.</li>
                        </ul>
                    </div>

                    <div class="row g-2 mt-1 text-start">
                        <div class="col-md-4">
                            <div class="h-100 border rounded-4 p-4 bg-light">
                                <div class="mb-3 text-success fs-3">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <h2 class="h5 fw-bold">Minha conta</h2>
                                <p class="text-muted mb-3">
                                    Acesse sua área para visualizar e atualizar seus dados.
                                </p>
                                <a href="/sociedade_civil/minha_conta.php" class="btn btn-success w-100">
                                    Acessar minha conta
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="h-100 border rounded-4 p-4 bg-light">
                                <div class="mb-3 text-primary fs-3">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <h2 class="h5 fw-bold">Vitrine</h2>
                                <p class="text-muted mb-3">
                                    Descubra iniciativas, causas e projetos publicados na plataforma.
                                </p>
                                <a href="/vitrine_nacional.php" class="btn btn-primary w-100">
                                    Acessar vitrine
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="h-100 border rounded-4 p-4 bg-light">
                                <div class="mb-3 text-warning fs-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h2 class="h5 fw-bold">Parceiros</h2>
                                <p class="text-muted mb-3">
                                    Conheça quem apoia a iniciativa e fortalece o ecossistema.
                                </p>
                                <a href="/parceiros.php" class="btn btn-outline-dark w-100">
                                    Ver parceiros
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <a href="/" class="btn btn-outline-primary me-2 mb-2">
                            Ir para a página inicial
                        </a>
                        <a href="/cadastro.php" class="btn btn-secondary mb-2">
                            Cadastrar outro membro
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>