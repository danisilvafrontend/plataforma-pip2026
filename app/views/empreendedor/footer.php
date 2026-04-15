    </div><!-- /emp-inner -->
  </div><!-- /emp-content -->
</div><!-- /emp-layout -->

<!-- Overlay mobile -->
<div class="emp-sidebar-overlay" id="empSidebarOverlay"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
<script src="/negocios/blocos-cadastros/assets/blocos.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/negocios/blocos-cadastros/assets/blocos.js') ?: time() ?>"></script>
<script src="/assets/js/scripts.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/scripts.js') ?: time() ?>"></script>

<script>
  // Toggle sidebar mobile
  const sidebar    = document.getElementById('empSidebar');
  const overlay    = document.getElementById('empSidebarOverlay');
  const btnToggle  = document.getElementById('empSidebarToggle');

  if (btnToggle) {
    btnToggle.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }

  // País → estado/cidade/região
  const pais = document.getElementById("pais");
  const estadoWrapper = document.getElementById("estado-wrapper");
  const cidadeWrapper = document.getElementById("cidade-wrapper");
  const regiaoWrapper = document.getElementById("regiao-wrapper");

  if (pais) {
    pais.addEventListener("change", function () {
      if (pais.value === "Brasil") {
        estadoWrapper?.classList.remove("d-none");
        cidadeWrapper?.classList.remove("d-none");
        regiaoWrapper?.classList.add("d-none");
      } else {
        estadoWrapper?.classList.add("d-none");
        cidadeWrapper?.classList.add("d-none");
        regiaoWrapper?.classList.remove("d-none");
      }
    });
  }
</script>

<?php if (!empty($extraFooter ?? null)) echo $extraFooter; ?>

</body>
</html>