window.Helpers = {
  
  mascaraCPF(value) {
    if (!value) return "";
    return value
      .replace(/\D/g, "")
      .replace(/(\d{3})(\d)/, "$1.$2")
      .replace(/(\d{3})(\d)/, "$1.$2")
      .replace(/(\d{3})(\d{1,2})$/, "$1-$2")
      .slice(0, 14);
  },

  mascaraTelefone(value) {
    if (!value) return "";
    return value
      .replace(/\D/g, "")
      .replace(/^(\d{2})(\d)/g, "($1) $2")
      .replace(/(\d{5})(\d{1,4})$/, "$1-$2")
      .slice(0, 15);
  },


  validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  },

  validarNome(name) {
    const regex = /^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/;
    return regex.test(name);
  },


  removerNumeros(text) {
    return text.replace(/[0-9]/g, "");
  },

  gerarCodigoVerificacao () {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let codigo = '';
    
    for (let i = 0; i < 12; i++) {
        codigo += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    return codigo.slice(0,4) + '-' + codigo.slice(4,8) + '-' + codigo.slice(8,12);
  },

  primeiraLetraMaiuscula(text) {
    if (!text) return "";
    return text
      .toLowerCase()
      .replace(/\b\w/g, (char) => char.toUpperCase());
  },

};
