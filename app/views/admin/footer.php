<?php
// /home/.../app/views/admin/footer.php
?>

</main><!-- /.ip-main -->

<!-- ── Footer ────────────────────────────────────────── -->
<footer>
  <span style="font-size:.75rem; color:#9aab9d;">
    <i class="bi bi-droplet-fill me-1" style="color:#CDDE00;"></i>
    © <?= date('Y') ?> <strong style="color:#1E3425;">Impactos Positivos</strong> — Todos os direitos reservados
  </span>
  <span style="font-size:.72rem; color:#b0bdb3;">Versão MVP</span>
</footer>

<!-- ════════════════════════════════════════
     Scripts — ordem importa
═════════════════════════════════════════ -->

<!-- Bootstrap JS bundle (inclui Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<!-- Chart.js + plugin datalabels -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<!-- TinyMCE WYSIWYG -->
<script src="/assets/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
if (document.querySelector('textarea.wysiwyg')) {
  tinymce.init({
    selector: 'textarea.wysiwyg',
    height: 400,
    menubar: true,
    plugins: 'link lists image table code autolink preview searchreplace wordcount emoticons',
    toolbar: 'undo redo | styleselect | bold italic underline | ' +
             'alignleft aligncenter alignright alignjustify | ' +
             'bullist numlist outdent indent | link image table | code | ' +
             'searchreplace preview emoticons',
    content_style: 'body { font-family: "Segoe UI", system-ui, sans-serif; font-size:14px; color:#1E3425; }',
    images_upload_url: '/admin/upload_image.php',
    automatic_uploads: true,
    paste_data_images: true,
    relative_urls: false,
    remove_script_host: false,
    convert_urls: true,
    license_key: 'gpl'
  });
}
</script>

<!-- JS dos blocos e scripts gerais -->
<script src="/negocios/blocos-cadastros/assets/blocos.js"></script>
<script src="/assets/js/chart_graficos.js"></script>
<script src="/assets/js/scripts.js"></script>

<!-- Scripts específicos da página -->
<?php if (!empty($extraFooter ?? null)) echo $extraFooter; ?>

</body>
</html>