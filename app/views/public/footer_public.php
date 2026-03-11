  </main>

  <footer class="site-footer mt-5">
    <div class="container">
      <div class="row">
        <!-- Logo e texto -->
        <div class="col-md-4 mb-3 text-center text-md-start">
          <img src="/../assets/images/impactos_positivos.svg" alt="Impactos Positivos" style="height:60px;">
          <p class="small text-muted mt-2">
            Plataforma Impactos Positivos — fortalecendo negócios de impacto e promovendo transformação social.
          </p>
        </div>

        <!-- Redes sociais -->
        <div class="col-md-4 mb-3 text-center">
          <h6 class="fw-bold">Redes Sociais</h6>
          <div class="d-flex justify-content-center gap-2">
            <a href="https://www.facebook.com/impactospositivosoficial" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-facebook"></i></a>
            <a href="https://www.instagram.com/impactospositivosoficial/" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-instagram"></i></a>
            <a href="https://www.linkedin.com/company/impactos-positivos/" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-linkedin"></i></a>
            <a href="https://www.youtube.com/channel/UCYuEo4Gnyyqvk-J64PrmqzA" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-youtube"></i></a>
          </div>
          <div class="mt-2">
            <a href="https://api.whatsapp.com/send?phone=551123673170&text=Ol%C3%A1,%20seja%20bem-vindo!%20Em%20que%20podemos%20ajudar?" 
              target="_blank" class="btn btn-success btn-sm">
              <i class="bi bi-whatsapp"></i> Fale Conosco
            </a>
          </div>
        </div>

        <!-- Links essenciais -->
        <div class="col-md-4 mb-3 text-center text-md-start">
          <h6 class="fw-bold">Links Essenciais</h6>
          <ul class="list-unstyled small">
            <li><a href="https://impactospositivos.com/quem-somos/" class="text-muted">Sobre Nós</a></li>
            <li><a href="https://impactospositivos.com/manisfesto/" target="_blank" class="text-muted">Manifesto</a></li>
            <li><a href="https://impactospositivos.com/sobre-a-premiacao/" target="_blank" class="text-muted">Sobre a Premiação</a></li>
            <li><a href="https://impactospositivos.com/regulamento-do-premio/" target="_blank" class="text-muted">Regulamento do Prêmio</a></li>
          </ul>
        </div>
      </div>

      <hr>
      <div class="text-center small text-muted">
        © <?= date('Y') ?> Impactos Positivos — Todos os direitos reservados | <a href="/admin-login.php" target="_blank" class="text-muted">Painel Administrativo</a>
      </div>
    </div>
  </footer>
  <!-- jQuery (se você estiver usando nos scripts customizados) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS bundle (ANTES dos scripts customizados) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<!-- JS dos blocos e scripts customizados (DEPOIS do Bootstrap) -->
<script src="/../../negocios/blocos-cadastros/assets/blocos.js"></script>
<script src="/../../assets/js/scripts.js"></script>

<?php if (!empty($extraFooter ?? null)) echo $extraFooter; ?>

  <script>
    const pais = document.getElementById("pais");
    const estadoWrapper = document.getElementById("estado-wrapper");
    const cidadeWrapper = document.getElementById("cidade-wrapper");
    const regiaoWrapper = document.getElementById("regiao-wrapper");

    if (pais) {
      pais.addEventListener("change", function() {
        if (pais.value === "Brasil") {
          estadoWrapper.classList.remove("d-none");
          cidadeWrapper.classList.remove("d-none");
          regiaoWrapper.classList.add("d-none");
        } else {
          estadoWrapper.classList.add("d-none");
          cidadeWrapper.classList.add("d-none");
          regiaoWrapper.classList.remove("d-none");
        }
      });
    }
  </script>

</body>
</html>