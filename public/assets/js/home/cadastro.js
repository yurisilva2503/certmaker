const inputTelefone = document.getElementById("inputTelefone");
const inputNome = document.getElementById("inputNome");
const form = document.querySelector("form");
const inputEmail = document.getElementById("inputEmail");
const btnRegistrar = document.getElementById("btnRegistrar");

inputTelefone.addEventListener("input", (e) => {
  e.target.value = Helpers.mascaraTelefone(e.target.value);
});

inputNome.addEventListener("input", (e) => {
  e.target.value = Helpers.removerNumeros(e.target.value);
});

form.addEventListener("submit", (e) => {
  e.preventDefault();

  if (!Helpers.validarNome(inputNome.value)) {
    Swal.fire({
      icon: "warning",
      title: "Atenção",
      text: "O nome deve conter apenas letras.",
      confirmButtonText: "Ok",
      confirmButtonColor: "green",
      theme: `${temaAlerta}`,
    });
    return;
  }

  if (!Helpers.validarEmail(inputEmail.value)) {
    Swal.fire({
      icon: "warning",
      title: "Atenção",
      text: "O e-mail deve ser válido.",
      confirmButtonText: "Ok",
      confirmButtonColor: "green",
      theme: `${temaAlerta}`,
    });
    return;
  }

  btnRegistrar.disabled = true;
  btnRegistrar.innerHTML =
    'Aguarde <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
  e.target.submit();
});
