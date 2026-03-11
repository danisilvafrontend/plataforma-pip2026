// /assets/js/scripts.js

document.addEventListener('DOMContentLoaded', function () {
  const cepInput = document.getElementById('cep');
  const ruaInput = document.getElementById('rua');
  const municipioInput = document.getElementById('municipio');
  const estadoInput = document.getElementById('estado'); // agora é um input normal

  if (cepInput) {
    // Máscara para CEP (00000-000)
    cepInput.addEventListener('input', function () {
      let value = this.value.replace(/\D/g, '');
      if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
      }
      this.value = value;
    });

    // Consulta ViaCEP ao sair do campo
    cepInput.addEventListener('blur', function () {
      let cep = this.value.replace(/\D/g, '');
      if (cep.length !== 8) {
        alert("CEP inválido. Informe 8 dígitos.");
        return;
      }

      fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
          if (data.erro) {
            alert("CEP não encontrado.");
            if (ruaInput) ruaInput.value = '';
            if (municipioInput) municipioInput.value = '';
            if (estadoInput) estadoInput.value = '';
          } else {
            if (ruaInput) ruaInput.value = data.logradouro;
            if (municipioInput) municipioInput.value = data.localidade;
            if (estadoInput) estadoInput.value = data.uf;
          }
        })
        .catch(() => alert("Erro ao consultar CEP."));
    });
  }

  // Permitir apenas números no campo Número
  const numeroInput = document.getElementById('numero');
  if (numeroInput) {
    numeroInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '');
    });
  }
});