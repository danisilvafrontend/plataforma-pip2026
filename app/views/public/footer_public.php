</main>

<footer class="site-footer mt-5" style="background:#f5f7f2; border-top:3px solid #CDDE00; padding:2.5rem 0 1.25rem;">
  <div class="container">
    <div class="row g-4">

      <!-- Logo e texto -->
      <div class="col-12 col-md-4 text-center text-md-start">
        <img src="/../assets/images/impactos_positivos.svg" alt="Impactos Positivos" style="height:52px;">
        <p class="small mt-3" style="color:#6c8070; line-height:1.6;">
          Plataforma Impactos Positivos — fortalecendo negócios de impacto e promovendo transformação social.
        </p>
      </div>

      <!-- Redes sociais -->
      <div class="col-12 col-md-4 text-center">
        <h6 class="fw-bold mb-3" style="color:#1E3425; text-transform:uppercase; font-size:.75rem; letter-spacing:.08em;">Redes Sociais</h6>
        <div class="d-flex justify-content-center gap-2 mb-3">
          <a href="https://www.facebook.com/impactospositivosoficial" target="_blank" class="footer-social-btn"><i class="bi bi-facebook"></i></a>
          <a href="https://www.instagram.com/impactospositivosoficial/" target="_blank" class="footer-social-btn"><i class="bi bi-instagram"></i></a>
          <a href="https://www.linkedin.com/company/impactos-positivos/" target="_blank" class="footer-social-btn"><i class="bi bi-linkedin"></i></a>
          <a href="https://www.youtube.com/channel/UCYuEo4Gnyyqvk-J64PrmqzA" target="_blank" class="footer-social-btn"><i class="bi bi-youtube"></i></a>
        </div>
        <a href="https://api.whatsapp.com/send?phone=551123673170&text=Ol%C3%A1,%20seja%20bem-vindo!%20Em%20que%20podemos%20ajudar?"
           target="_blank"
           style="background:#1E3425; color:#CDDE00; font-weight:700; border-radius:20px; padding:.35rem 1.2rem; font-size:.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem;">
          <i class="bi bi-whatsapp"></i> Fale Conosco
        </a>
      </div>

      <!-- Links essenciais -->
      <div class="col-12 col-md-4 text-center text-md-start">
        <h6 class="fw-bold mb-3" style="color:#1E3425; text-transform:uppercase; font-size:.75rem; letter-spacing:.08em;">Links Essenciais</h6>
        <ul class="list-unstyled small">
          <li class="mb-1"><a href="https://impactospositivos.com/quem-somos/" target="_blank" class="footer-link">Sobre Nós</a></li>
          <li class="mb-1"><a href="https://impactospositivos.com/manisfesto/" target="_blank" class="footer-link">Manifesto</a></li>
          <li class="mb-1"><a href="https://impactospositivos.com/sobre-a-premiacao/" target="_blank" class="footer-link">Sobre a Premiação</a></li>
          <li class="mb-1"><a href="https://impactospositivos.com/regulamento-do-premio/" target="_blank" class="footer-link">Regulamento do Prêmio</a></li>
        </ul>
      </div>

    </div>

    <hr style="border-color:#dde4d8; margin:1.5rem 0 1rem;">

    <div class="text-center small" style="color:#9aab9d;">
      © <?= date('Y') ?> Impactos Positivos — Todos os direitos reservados &nbsp;|&nbsp;
      <a href="/admin-login.php" target="_blank" style="color:#9aab9d; text-decoration:none;">Painel Administrativo</a>
    </div>
  </div>
</footer>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
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