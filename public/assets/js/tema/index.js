const htmlElemento = document.documentElement;
  const esquemaCor = window.matchMedia("(prefers-color-scheme: dark)");
  let temaAlerta = "";

  function temaInicial() {
    const temaSalvo = localStorage.getItem("tema");
    if (temaSalvo) return temaSalvo;
    return esquemaCor.matches ? "dark" : "light";
  }

  function aplicarTemaBase(theme) {
    htmlElemento.setAttribute("data-bs-theme", theme);
    temaAlerta = theme;
  }

  function aplicarTemaCompleto(theme) {
    aplicarTemaBase(theme);

    if (document.body) {
      document.body.style.backgroundImage =
        theme === "dark"
          ? 'url("/assets/img/background-dark.png")'
          : 'url("/assets/img/background-light.png")';
      document.body.style.backgroundSize = "contain";
    }

    const logoTemaMudar = document.getElementById("logoTemaMudar");
    if (logoTemaMudar) {
      logoTemaMudar.src = "/assets/img/favicon.png";
    }

    const btnTema = document.getElementById("btnTema");
    if (btnTema) {
      btnTema.innerHTML =
        theme === "dark"
          ? '<i class="bi bi-moon-stars-fill"></i>'
          : '<i class="bi bi-sun-fill"></i>';
    }
  }

  function ativarTema() {
    const temaAtual = htmlElemento.getAttribute("data-bs-theme");
    const novoTema = temaAtual === "dark" ? "light" : "dark";
    aplicarTemaCompleto(novoTema);
    localStorage.setItem("tema", novoTema);
  }

  const tema = temaInicial();
  aplicarTemaBase(tema);

  document.addEventListener("DOMContentLoaded", () => {
    aplicarTemaCompleto(tema);

    const btnTema = document.getElementById("btnTema");
    if (btnTema) {
      btnTema.addEventListener("click", ativarTema);
    }
  });