/**
 * cpfcnpj-api.js  —  /assets/js/cpfcnpj-api.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Módulo reutilizável para consultar CNPJ e CPF via proxy seguro.
 * Inclua este arquivo nas páginas que precisam da consulta:
 *
 *   <script src="/assets/js/cpfcnpj-api.js"></script>
 *
 * Não contém o token — a chamada vai para o proxy PHP do servidor.
 * ─────────────────────────────────────────────────────────────────────────────
 */

window.CpfCnpjAPI = (function () {

  // Caminho do proxy no servidor (ajuste se necessário)
  var PROXY_URL = '/app/api/cpfcnpj_proxy.php';

  // ── Utilitários ────────────────────────────────────────────────────────────

  function onlyDigits(str) {
    return (str || '').replace(/\D/g, '');
  }

  function formatCNPJ(d) {
    d = onlyDigits(d).slice(0, 14);
    return d
      .replace(/^(\d{2})(\d)/,           '$1.$2')
      .replace(/^(\d{2})\.(\d{3})(\d)/,  '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/,          '.$1/$2')
      .replace(/(\d{4})(\d)/,            '$1-$2')
      .slice(0, 18);
  }

  function formatCPF(d) {
    d = onlyDigits(d).slice(0, 11);
    return d
      .replace(/^(\d{3})(\d)/,           '$1.$2')
      .replace(/^(\d{3})\.(\d{3})(\d)/,  '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/,          '.$1-$2')
      .slice(0, 14);
  }

  function formatAuto(v) {
    var d = onlyDigits(v);
    return d.length <= 11 ? formatCPF(d) : formatCNPJ(d);
  }

  function isValidCNPJ(c) {
    c = onlyDigits(c);
    if (c.length !== 14 || /^(\d)\1{13}$/.test(c)) return false;
    var p1 = [5,4,3,2,9,8,7,6,5,4,3,2], s = 0;
    for (var i = 0; i < 12; i++) s += parseInt(c[i]) * p1[i];
    var r = s % 11, d1 = r < 2 ? 0 : 11 - r;
    if (d1 !== parseInt(c[12])) return false;
    var p2 = [6,5,4,3,2,9,8,7,6,5,4,3,2]; s = 0;
    for (var j = 0; j < 13; j++) s += parseInt(c[j]) * p2[j];
    r = s % 11;
    return (r < 2 ? 0 : 11 - r) === parseInt(c[13]);
  }

  function isValidCPF(cpf) {
    cpf = onlyDigits(cpf);
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    var s = 0;
    for (var i = 0; i < 9; i++) s += parseInt(cpf[i]) * (10 - i);
    var r = s % 11, d1 = r < 2 ? 0 : 11 - r;
    if (d1 !== parseInt(cpf[9])) return false;
    s = 0;
    for (var j = 0; j < 10; j++) s += parseInt(cpf[j]) * (11 - j);
    r = s % 11;
    return (r < 2 ? 0 : 11 - r) === parseInt(cpf[10]);
  }

  // ── Consultas ──────────────────────────────────────────────────────────────

  /**
   * consultarCNPJ(digits, callbacks)
   *
   * digits    — 14 dígitos numéricos (sem máscara)
   * callbacks — objeto com funções opcionais:
   *   onLoading()          chamada antes da requisição
   *   onSuccess(razao)     chamada com a razão social retornada
   *   onError(msg)         chamada com mensagem de erro legível
   *   onComplete()         chamada sempre ao final (sucesso ou erro)
   */
  function consultarCNPJ(digits, callbacks) {
    var cb = callbacks || {};
    if (typeof cb.onLoading === 'function') cb.onLoading();

    fetch(PROXY_URL + '?tipo=cnpj&doc=' + digits)
      .then(function (res) { return res.json().then(function(d){ return { status: res.status, data: d }; }); })
      .then(function (result) {
        if (result.status !== 200) {
          var msg = (result.data && result.data.erro) || 'Erro ao consultar CNPJ.';
          if (typeof cb.onError === 'function') cb.onError(msg);
          return;
        }
        var data  = result.data;
        var razao = (data.razao_social || data.razao || data.nome || '').trim();
        if (razao) {
          if (typeof cb.onSuccess === 'function') cb.onSuccess(razao);
        } else {
          if (typeof cb.onError === 'function') cb.onError('Razão Social não retornada pela Receita Federal.');
        }
      })
      .catch(function () {
        if (typeof cb.onError === 'function') cb.onError('Falha na conexão. Verifique sua internet.');
      })
      .finally(function () {
        if (typeof cb.onComplete === 'function') cb.onComplete();
      });
  }

  /**
   * consultarCPF(digits, callbacks)
   *
   * digits    — 11 dígitos numéricos (sem máscara)
   * callbacks — objeto com funções opcionais:
   *   onLoading()        chamada antes da requisição
   *   onSuccess(nome)    chamada com o nome retornado
   *   onError(msg)       chamada com mensagem de erro legível
   *   onComplete()       chamada sempre ao final
   */
  function consultarCPF(digits, callbacks) {
    var cb = callbacks || {};
    if (typeof cb.onLoading === 'function') cb.onLoading();

    fetch(PROXY_URL + '?tipo=cpf&doc=' + digits)
      .then(function (res) { return res.json().then(function(d){ return { status: res.status, data: d }; }); })
      .then(function (result) {
        if (result.status !== 200) {
          var msg = (result.data && result.data.erro) || 'Erro ao consultar CPF.';
          if (typeof cb.onError === 'function') cb.onError(msg);
          return;
        }
        var nome = ((result.data && result.data.nome) || '').trim();
        if (nome) {
          if (typeof cb.onSuccess === 'function') cb.onSuccess(nome);
        } else {
          if (typeof cb.onError === 'function') cb.onError('Nome não retornado pela Receita Federal.');
        }
      })
      .catch(function () {
        if (typeof cb.onError === 'function') cb.onError('Falha na conexão. Verifique sua internet.');
      })
      .finally(function () {
        if (typeof cb.onComplete === 'function') cb.onComplete();
      });
  }

  // ── API pública do módulo ──────────────────────────────────────────────────
  return {
    consultarCNPJ : consultarCNPJ,
    consultarCPF  : consultarCPF,
    formatCNPJ    : formatCNPJ,
    formatCPF     : formatCPF,
    formatAuto    : formatAuto,
    isValidCNPJ   : isValidCNPJ,
    isValidCPF    : isValidCPF,
    onlyDigits    : onlyDigits,
  };

})();