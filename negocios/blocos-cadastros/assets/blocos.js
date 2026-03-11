/* blocos.js - scripts para blocos-cadastros
   - Inicializa galeria (modal)
   - Gera IDs únicos para carousels quando necessário
   - Lazy-load de iframes (data-src)
   - Limpeza de src ao fechar modal para liberar memória
   - Pequenos aprimoramentos de acessibilidade/UX
*/

(function () {
  'use strict';

  /**
   * Gera um ID único simples
   */
  function uid(prefix = 'id') {
    return prefix + '-' + Math.random().toString(36).slice(2, 9);
  }

  /**
   * Inicializa carousels: garante IDs únicos e atualiza controles (data-bs-target)
   */
  function initCarousels() {
    var carousels = document.querySelectorAll('.carousel');
    carousels.forEach(function (carousel, idx) {
      // se já tem id, pula
      if (!carousel.id) {
        var newId = 'galeriaCarousel-' + idx + '-' + Date.now().toString(36);
        carousel.id = newId;
        // atualiza controles filhos (se existirem)
        var prev = carousel.querySelector('.carousel-control-prev');
        var next = carousel.querySelector('.carousel-control-next');
        if (prev) prev.setAttribute('data-bs-target', '#' + newId);
        if (next) next.setAttribute('data-bs-target', '#' + newId);
      }
    });
  }

  /**
   * Inicializa modal de visualização de imagens
   * Espera elementos .gallery-item com atributo data-img
   * Modal esperado: #modalImageViewer, #modalImage, #modalImageLink
   */
  function initGalleryModal() {
    var modalEl = document.getElementById('modalImageViewer');
    var modalImg = document.getElementById('modalImage');
    var modalLink = document.getElementById('modalImageLink');

    if (!modalEl || !modalImg || !modalLink) {
      // nada a fazer se modal não existir
      return;
    }

    // Delegation: attach click to gallery items
    document.querySelectorAll('.gallery-item').forEach(function (item) {
      item.addEventListener('click', function (ev) {
        var src = item.getAttribute('data-img') || item.querySelector('img')?.src;
        if (!src) return;
        modalImg.src = src;
        modalLink.href = src;
        // show modal via bootstrap if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          var modal = new bootstrap.Modal(modalEl);
          modal.show();
        }
      });

      // keyboard accessibility: Enter / Space
      item.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') {
          ev.preventDefault();
          item.click();
        }
      });
    });

    // Limpar src ao fechar modal para liberar memória
    modalEl.addEventListener('hidden.bs.modal', function () {
      modalImg.src = '';
      modalLink.href = '#';
    });
  }

  /**
   * Lazy-load de iframes que usam data-src
   * - Se IntersectionObserver disponível, carrega quando entra em viewport
   * - Caso contrário, carrega imediatamente
   */
  function initIframeLazyLoad() {
    var iframes = Array.prototype.slice.call(document.querySelectorAll('.video-compact iframe[data-src]'));

    if (iframes.length === 0) return;

    function loadIframe(iframe) {
      var src = iframe.getAttribute('data-src');
      if (!src) return;
      iframe.setAttribute('src', src);
      iframe.removeAttribute('data-src');
    }

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            loadIframe(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { rootMargin: '200px 0px' });

      iframes.forEach(function (f) { io.observe(f); });
    } else {
      // fallback
      iframes.forEach(loadIframe);
    }
  }

  /**
   * Ajustes de acessibilidade e UX
   * - torna .gallery-item focável se não for
   * - adiciona role=button
   */
  function enhanceAccessibility() {
    document.querySelectorAll('.gallery-item').forEach(function (el) {
      if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
      if (!el.hasAttribute('role')) el.setAttribute('role', 'button');
      // ensure aria-label if missing
      if (!el.hasAttribute('aria-label')) {
        var alt = el.querySelector('img')?.alt || 'Abrir imagem';
        el.setAttribute('aria-label', alt);
      }
    });
  }

  /**
   * Inicialização principal
   */
  function init() {
    initCarousels();
    initGalleryModal();
    initIframeLazyLoad();
    enhanceAccessibility();
  }

  // Auto-init on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expor um namespace leve caso precise reinicializar dinamicamente
  window.Blocos = window.Blocos || {};
  window.Blocos.init = init;

})();

(function () {
  function debounce(fn, ms) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(fn, ms);
    };
  }

  function waitImg(img) {
    return img.complete
      ? Promise.resolve()
      : new Promise((res) => img.addEventListener("load", res, { once: true }));
  }

  function layoutJustified(galleryEl) {
    const links = Array.from(galleryEl.querySelectorAll("a"));
    if (!links.length) return;

    const imgs = links.map((a) => a.querySelector("img"));
    const gap = parseInt(getComputedStyle(galleryEl).gap || "8", 10) || 8;

    // “miniatura adaptada”
    const targetH = parseInt(galleryEl.dataset.jgTargetH || "150", 10);
    const maxH = parseInt(galleryEl.dataset.jgMaxH || "210", 10);

    const W = galleryEl.clientWidth;
    if (!W) return;

    let row = [];
    let sum = 0;

    links.forEach((a, idx) => {
      const img = a.querySelector("img");
      const r =
        img && img.naturalWidth && img.naturalHeight
          ? img.naturalWidth / img.naturalHeight
          : 1;

      row.push({ a, r });
      sum += r;

      const hCalc = (W - gap * (row.length - 1)) / sum;
      const last = idx === links.length - 1;

      if (hCalc <= maxH || last) {
        const h = last ? Math.min(targetH, hCalc) : Math.min(maxH, hCalc);
        row.forEach((it) => {
          it.a.style.height = h + "px";
          it.a.style.width = h * it.r + "px";
        });
        row = [];
        sum = 0;
      }
    });
  }

  function initGallery(galleryEl) {
    if (galleryEl.dataset.jgInit === "1") return;
    galleryEl.dataset.jgInit = "1";

    const links = Array.from(galleryEl.querySelectorAll("a"));
    const imgs = links.map((a) => a.querySelector("img"));

    Promise.all(imgs.map(waitImg)).then(() => layoutJustified(galleryEl));
    window.addEventListener("resize", debounce(() => layoutJustified(galleryEl), 120));

    // Lightbox wiring
    const uid = galleryEl.dataset.jgUid;
    const lb = document.getElementById("jgLightbox-" + uid);
    if (!lb) return;

    const lbImg = lb.querySelector(".jg-full");
    const caption = lb.querySelector(".jg-caption");
    let cur = 0;

    function open(i) {
      cur = i;
      lbImg.src = links[cur].href;
      caption.textContent = `${cur + 1} / ${links.length}`;
      lb.classList.add("is-open");
      lb.setAttribute("aria-hidden", "false");
    }
    function close() {
      lb.classList.remove("is-open");
      lb.setAttribute("aria-hidden", "true");
      lbImg.src = "";
    }
    function next() {
      open((cur + 1) % links.length);
    }
    function prev() {
      open((cur - 1 + links.length) % links.length);
    }

    links.forEach((a, i) =>
      a.addEventListener("click", (e) => {
        e.preventDefault();
        open(i);
      })
    );

    lb.querySelector(".jg-close")?.addEventListener("click", close);
    lb.querySelector(".jg-next")?.addEventListener("click", next);
    lb.querySelector(".jg-prev")?.addEventListener("click", prev);

    lb.addEventListener("click", (e) => {
      if (e.target === lb) close();
    });

    document.addEventListener("keydown", (e) => {
      if (!lb.classList.contains("is-open")) return;
      if (e.key === "Escape") close();
      if (e.key === "ArrowRight") next();
      if (e.key === "ArrowLeft") prev();
    });

    // Recalcular quando abrir modal (Bootstrap)
    const modal = document.getElementById("previewModal");
    if (modal) {
      modal.addEventListener("shown.bs.modal", () => {
        setTimeout(() => layoutJustified(galleryEl), 80);
      });
    }
  }

  function boot() {
    document.querySelectorAll(".jg[data-jg-uid]").forEach(initGallery);
  }

  document.addEventListener("DOMContentLoaded", boot);
})();
