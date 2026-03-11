<?php
// /home/.../app/views/admin/footer.php
// Uso: include __DIR__ . '/footer.php'; ao final do conteúdo da página
?>

<footer class="site-footer mt-5 mb-5">
    <div class="container" style="max-width:1000px;">
      <div class="row">
      <hr>
      <div class="text-center small text-muted">
        © <?= date('Y') ?> Impactos Positivos — Todos os direitos reservados
      </div>
    </div>
  </footer>
      <!-- fim do main content -->
    </main>
  </div>

<!-- Editor WYSIWYG (TinyMCE) - carrega em todas páginas admin -->
<script src="/assets/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: 'textarea.wysiwyg',
    height: 400,
    menubar: true,
    plugins: 'link lists image table code autolink preview searchreplace wordcount emoticons',
    toolbar: 'undo redo | styleselect | bold italic underline | ' +
             'alignleft aligncenter alignright alignjustify | ' +
             'bullist numlist outdent indent | link image table | code | ' +
             'searchreplace preview emoticons',
    content_style: "body { font-family: Arial, sans-serif; font-size:14px; }",
    images_upload_url: '/admin/upload_image.php',
    automatic_uploads: true,
    paste_data_images: true,
    relative_urls: false,
    remove_script_host: false,
    convert_urls: true,
    license_key: 'gpl'
});
</script>


<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<!-- JS dos blocos -->
<script src="/negocios/blocos-cadastros/assets/blocos.js"></script>
<script src="/assets/js/chart_graficos.js"></script>
<script src="/assets/js/scripts.js"></script>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
  

  <!-- Lugar para scripts específicos da página -->
  <?php if (!empty($extraFooter ?? null)) echo $extraFooter; ?>
</body>
</html>