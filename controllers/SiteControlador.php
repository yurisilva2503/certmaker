<?php

namespace controllers;

use controllers\Roteador;
use controllers\Helpers;
use models\ModeloCertificado;
use services\UsuarioServico;
use services\AutenticarServico;
use services\EmailServico;
use config\database as db;
use services\TokenServico;
use Exception;
use services\CertificadoServico;
/**
 * Classe SiteControlador
 * Responsável por gerenciar as rotas do site e renderizar as páginas correspondentes.
 * @package controllers
 */
class SiteControlador extends Roteador
{
    /**
     * Renderiza a página inicial.
     * @return void
     */
    public function index(): void
    {
        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }
        echo $this->template->renderizar("validar.html", [
            'pagina' => 'Página Inicial',
            'titulo' => 'Página Inicial',
            'ano' => Helpers::ano(),
            'aviso' => $aviso,
            'tipo_aviso' => $tipo_aviso
        ]);
    }

    /**
     * Renderiza a página de erro.
     * @return void
     */
    public function erro(): void
    {
        echo $this->template->renderizar("erro.html", [
            'pagina' => 'Erro',
            'titulo' => 'Erro',
            'ano' => Helpers::ano(),
        ]);
    }

    public function validar(string $codigoVerificacao): void
    {
        if (empty($codigoVerificacao)) {
            echo $this->template->renderizar("validar.html", [
                'pagina' => 'Validar',
                'titulo' => 'Validar',
                'ano' => Helpers::ano(),
            ]);
            exit;
        }

        $certificadoServico = new CertificadoServico();

        $certificado = $certificadoServico->buscarCertificadoPorCodigo($codigoVerificacao);
        if (!$certificado['status']) {
            echo $this->template->renderizar("validar.html", [
                'pagina' => 'Validar',
                'titulo' => 'Validar',
                'ano' => Helpers::ano(),
                'tipo_aviso' => 'danger',
                'aviso' => 'Certificado não encontrado.'
            ]);
            exit;
        }

        echo $this->template->renderizar("validar.html", [
            'pagina' => 'Validar',
            'titulo' => 'Validar',
            'codigo' => $codigoVerificacao,
            'certificado' => $certificado['certificado'],
            'ano' => Helpers::ano(),
        ]);
    }

    /**
     * Renderiza a página de registro de novos usuários.
     * @return void
     */
    public function registrar(): void
    {
        $aviso = $_SESSION['flash']['mensagem'] ?? null;
        $tipo_aviso = $_SESSION['flash']['tipo'] ?? null;

        unset($_SESSION['flash']);

        echo $this->template->renderizar("registrar/index.html", [
            'pagina' => 'Registrar',
            'titulo' => 'Registrar',
            'ano' => Helpers::ano(),
            'aviso' => $aviso,
            'tipo_aviso' => $tipo_aviso,
        ]);
    }

    /**
     * Processa o formulário de registro de novos usuários.
     * @return void
     */
    public function registrarForm(): void
    {
        $camposEsperados = ['inputNome', 'inputEmail', 'inputTelefone', 'inputDataNasc', 'inputSenha', 'ConfSenha'];
        $extracao = Helpers::extrairCampos($_POST, $camposEsperados);

        if (!Helpers::validarEmail($extracao['inputEmail'])) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'Email inválido!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            return;
        }

        if (!Helpers::validarTelefone($extracao['inputTelefone'])) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'Telefone inválido!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            return;
        }


        if (!Helpers::validarData($extracao['inputDataNasc'])) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'Data de nascimento inválida!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            return;
        }
        if ($extracao['inputSenha'] != $extracao['ConfSenha']) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'As senhas não coincidem!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            return;
        }

        if (count_chars($extracao['inputSenha']) < 8) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'A senha deve ter pelo menos 8 caracteres!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $usuarioServico = new UsuarioServico();

        if ($usuarioServico->buscarUsuarioPor('email', $extracao['inputEmail'])['status']) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'Email ja cadastrado!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }


        if ($usuarioServico->buscarUsuarioPor('telefone', $extracao['inputTelefone'])['status']) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => 'Telefone ja cadastrado!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $emailServico = new EmailServico();
        $tokenServico = new TokenServico();

        $codigo = Helpers::gerarToken();
        $codigo = $tokenServico->criar($codigo, null, 'cadastrar_conta');

        if (!$codigo['status']) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => $codigo['mensagem'],
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            return;
        }

        $dadosUsuario = [
            'nome' => $extracao['inputNome'],
            'email' => $extracao['inputEmail'],
            'telefone' => $extracao['inputTelefone'],
            'datanasc' => $extracao['inputDataNasc'],
            'senha' => password_hash($extracao['inputSenha'], PASSWORD_DEFAULT)
        ];

        $jsonUsuario = json_encode($dadosUsuario, JSON_UNESCAPED_UNICODE);
        $tokenUsuario = urlencode(base64_encode($jsonUsuario));


        $linkConfirmar = Helpers::baseUrl() . "/confirmar-conta?token=" . $tokenUsuario . "&codigo=" . urlencode($codigo['token']->getCodigo());
        $linkCancelar = Helpers::baseUrl() . "/cancelar-conta?token=" . $tokenUsuario . "&codigo=" . urlencode($codigo['token']->getCodigo());


        $envio = $emailServico->enviarEmailCriacaoConta($extracao['inputEmail'], $extracao['inputNome'], $linkConfirmar, $linkCancelar);

        if (!$envio['status']) {
            echo $this->template->renderizar("registrar/index.html", [
                'pagina' => 'Registrar',
                'titulo' => 'Registrar',
                'ano' => Helpers::ano(),
                'aviso' => $envio['mensagem'],
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            return;
        }

        $_SESSION['flash'] = ['mensagem' => 'Email enviado com sucesso! Confirme o código recebido no seu email para efetuar ou cancelar a criação da sua conta.', 'tipo' => 'success'];

        Helpers::redirecionar('/registrar');
    }

    public function confirmarConta(): void
    {

        $tokenUrl = $_GET['token'] ?? null;
        $codigoUrl = $_GET['codigo'] ?? null;

        if (!$tokenUrl || !$codigoUrl) {
            $_SESSION['flash'] = ['mensagem' => 'Link inválido ou expirado.', 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
            return;
        }

        $tokenServico = new TokenServico();
        $validarCodigo = $tokenServico->validarCodigo($codigoUrl, 'cadastrar_conta');

        if (!$validarCodigo['status']) {
            $_SESSION['flash'] = ['mensagem' => $validarCodigo['mensagem'], 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
            return;
        }

        $jsonUsuario = base64_decode(urldecode($tokenUrl));
        $dadosUsuario = json_decode($jsonUsuario, true);

        if (!$dadosUsuario) {
            $_SESSION['flash'] = ['mensagem' => 'Dados do usuário inválidos.', 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
            return;
        }

        $usuarioServico = new UsuarioServico();

        $criar = $usuarioServico->cadastrarUsuario($dadosUsuario['nome'], $dadosUsuario['email'], $dadosUsuario['telefone'], $dadosUsuario['senha'], $dadosUsuario['datanasc']); 

        if (!$criar['status']) {
            $_SESSION['flash'] = ['mensagem' => $criar['mensagem'], 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
            return;
        }

        $validarCodigo['token']->setUtilizado(true);
        $validarCodigo['token']->setIdUsuario($usuarioServico->buscarUsuarioPor('email', $dadosUsuario['email'])['usuario']->getId());
        $validarCodigo['token']->setUtilizadoEm(date('Y-m-d H:i:s'));

        $tokenServico->atualizarToken($validarCodigo['token']);

        $_SESSION['flash'] = ['mensagem' => 'Conta criada com sucesso! Você já pode fazer login.', 'tipo' => 'success'];
        Helpers::redirecionar('/');
    }


    public function cancelarConta(): void
    {
        $codigo = $_GET['token'] ?? null;

        if (!$codigo) {
            $_SESSION['flash'] = ['mensagem' => 'Token não fornecido.', 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
        }

        $tokenServico = new TokenServico();
        $token = $tokenServico->buscarPorCodigo($codigo);

        if (!$token['status'] || $token['token']->getAcao() != 'cadastrar_conta') {
            $_SESSION['flash'] = ['mensagem' => 'Token inválido!', 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
        }

        $token = $token['token'];

        if ($token->getUtilizado() == 1) {
            $_SESSION['flash'] = ['mensagem' => 'Token já utilizado!', 'tipo' => 'danger'];
            Helpers::redirecionar('/registrar');
        }

        $token->setUtilizado(true);
        $token->setUtilizadoEm(date('Y-m-d H:i:s'));
        $tokenServico->atualizarToken($token);

        $_SESSION['flash'] = ['mensagem' => 'Cadastro cancelado com sucesso!', 'tipo' => 'info'];
        Helpers::redirecionar('/registrar');
    }

    /**
     * Renderiza a página de login e gerencia autenticação via Google.
     * @return void
     */
    public function login(): void
    {

        $usuario = new AutenticarServico(db::getInstancia());

        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }

        $usuarioLogado = '';
        if ($usuario->verificarSessao()) {
            $usuarioLogado = new UsuarioServico();
            $usuarioLogado = $usuarioLogado->buscarUsuarioPor('id', $_SESSION['usuario_id']);
            if ($usuarioLogado['status']) {
                $usuarioLogado = $usuarioLogado['usuario'];

                if ($usuarioLogado->getPerfil() == 'admin') {
                    Helpers::redirecionar('/admin');
                    exit;
                }

                if ($usuarioLogado->getPerfil() == 'usuário') {
                    Helpers::redirecionar('/usuario');
                    exit;
                }
            }
        }

        echo $this->template->renderizar("login/index.html", [
            'pagina' => 'Login',
            'titulo' => 'Login',
            'ano' => Helpers::ano(),
            'usuario' => $usuarioLogado,
            'aviso' => $aviso,
            'tipo_aviso' => $tipo_aviso
        ]);
    }

    /**
     * Processa os dados do formulário de login.
     * @return void
     */
    public function loginForm(): void
    {
        $camposEsperados = ['inputEmail', 'inputSenha'];
        $extracao = Helpers::extrairCampos($_POST, $camposEsperados);

        if (empty($extracao)) {
            echo $this->template->renderizar("login/index.html", [
                'pagina' => 'Login',
                'titulo' => 'Login',
                'ano' => Helpers::ano(),
                'aviso' => 'Preencha todos os campos corretamente!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        if (!Helpers::validarEmail($extracao['inputEmail'])) {
            echo $this->template->renderizar("login/index.html", [
                'pagina' => 'Login',
                'titulo' => 'Login',
                'ano' => Helpers::ano(),
                'aviso' => 'Email inválido!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $autenticacao = new AutenticarServico(db::getInstancia());
        $resultado = $autenticacao->authLocal($extracao['inputEmail'], $extracao['inputSenha']);

        if ($resultado['status']) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status']) {
                $usuario = $usuario['usuario'];

                if ($usuario->getPerfil() == 'admin') {
                    Helpers::redirecionar('/admin');
                    exit;
                }

                if ($usuario->getPerfil() == 'usuário') {
                    Helpers::redirecionar('/usuario');
                    exit;
                }
            } else {
                Helpers::redirecionar('/');
            }
        } else {
            echo $this->template->renderizar("login/index.html", [
                'pagina' => 'Login',
                'titulo' => 'Login',
                'ano' => Helpers::ano(),
                'aviso' => $resultado['mensagem'],
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }
    }

    /**
     * Renderiza a página para redefinição de senha.
     * @return void
     */
    public function senha(): void
    {
        $aviso = $_SESSION['flash']['mensagem'] ?? null;
        $tipo_aviso = $_SESSION['flash']['tipo'] ?? null;
        unset($_SESSION['flash']);


        echo $this->template->renderizar("senha/index.html", [
            'pagina' => 'Redefinir Senha',
            'titulo' => 'Redefinir Senha',
            'ano' => Helpers::ano(),
            'aviso' => $aviso,
            'tipo_aviso' => $tipo_aviso
        ]);
    }

    public function senhaForm(): void
    {
        $camposEsperados = ['inputEmail'];
        $extracao = Helpers::extrairCampos($_POST, $camposEsperados);

        if (!Helpers::validarEmail($extracao['inputEmail'])) {
            echo $this->template->renderizar("senha/index.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Email inválido!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $usuarioServico = new UsuarioServico();
        $usuario = $usuarioServico->buscarUsuarioPor('email', $extracao['inputEmail']);

        if (!$usuario['status']) {
            echo $this->template->renderizar("senha/index.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Email não encontrado!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $usuario = $usuario['usuario'];

        if ($usuario->getTipoCadastro() != 'local') {
            echo $this->template->renderizar("senha/index.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Você se registrou com uma conta Google. Faça o login com ela!',
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $codigo = Helpers::gerarToken();
        $emailServico = new EmailServico();
        $tokenServico = new TokenServico();

        $codigo = $tokenServico->criar($codigo, $usuario->getId(), 'redefinir_senha');

        if (!$codigo['status']) {
            echo $this->template->renderizar("senha/index.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => $codigo['mensagem'],
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $link = Helpers::baseUrl() . "/codigo-senha?token=" . urlencode($codigo['token']->getCodigo());

        $envio = $emailServico->enviarEmailRedefinicaoSenha($usuario->getEmail(), $usuario, $link);

        if (!$envio['status']) {
            echo $this->template->renderizar("senha/index.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => $envio['mensagem'],
                'tipo_aviso' => 'danger',
                'campos' => $extracao
            ]);
            exit;
        }

        $_SESSION['flash'] = ['mensagem' => 'Email enviado com sucesso! Verifique sua caixa de entrada.', 'tipo' => 'success'];
        Helpers::redirecionar('/redefinir-senha');
    }


    public function redefinicaoSenhaCodigo(): void
    {
        $aviso = $_SESSION['flash']['mensagem'] ?? null;
        $tipo_aviso = $_SESSION['flash']['tipo'] ?? null;
        unset($_SESSION['flash']);

        $codigo = $_GET['token'] ?? null;

        if (!$codigo) {
            $_SESSION['flash'] = ['mensagem' => 'Token não fornecido.', 'tipo' => 'danger'];
            Helpers::redirecionar('/redefinir-senha');
        }

        $tokenServico = new TokenServico();
        $token = $tokenServico->buscarPorCodigo($codigo);

        if (!$token['status']) {
            $_SESSION['flash'] = ['mensagem' => 'Token inválido ou não encontrado.', 'tipo' => 'danger'];
            Helpers::redirecionar('/redefinir-senha');
        }

        $token = $token['token'];

        if ($token->getExpirado() == 1) {
            $_SESSION['flash'] = ['mensagem' => 'Token expirado!', 'tipo' => 'danger'];
            Helpers::redirecionar('/redefinir-senha');
        }

        if ($token->getUtilizado() == 1) {
            $_SESSION['flash'] = ['mensagem' => 'Token já utilizado!', 'tipo' => 'danger'];
            Helpers::redirecionar('/redefinir-senha');
        }

        echo $this->template->renderizar("senha/codigo.html", [
            'pagina' => 'Redefinir Senha',
            'titulo' => 'Redefinir Senha',
            'ano' => Helpers::ano(),
            'codigo' => $codigo,
            'aviso' => $aviso,
            'tipo_aviso' => $tipo_aviso
        ]);
    }

    public function redefinicaoSenhaForm(): void
    {
        $camposEsperados = ['inputSenha', 'inputConfirmarSenha', 'inputCodigo'];
        $extracao = Helpers::extrairCampos($_POST, $camposEsperados);

        $codigo = $extracao['inputCodigo'];

        $tokenServico = new TokenServico();
        $usuarioServico = new UsuarioServico();

        $token = $tokenServico->buscarPorCodigo($codigo);

        if (!$token['status']) {
            echo $this->template->renderizar("senha/codigo.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Token inválido!',
                'tipo_aviso' => 'danger',
                'codigo' => $codigo
            ]);
            exit;
        }

        $token = $token['token'];

        if ($token->getExpirado() == 1 || $token->getUtilizado() == 1 || $token->getAcao() != 'redefinir_senha') {
            echo $this->template->renderizar("senha/codigo.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Token inválido ou já utilizado.',
                'tipo_aviso' => 'danger',
                'codigo' => $codigo
            ]);
            exit;
        }

        if ($extracao['inputSenha'] != $extracao['inputConfirmarSenha']) {
            echo $this->template->renderizar("senha/codigo.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Senhas diferentes!',
                'tipo_aviso' => 'danger',
                'codigo' => $codigo
            ]);
            exit;
        }

        if (count_chars($extracao['inputSenha']) < 8) {
            echo $this->template->renderizar("senha/codigo.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'A senha deve conter ao menos 8 caracteres.',
                'tipo_aviso' => 'danger',
                'codigo' => $codigo
            ]);
            exit;
        }

        $usuario = $usuarioServico->buscarUsuarioPor('id', $token->getIdUsuario());

        if (!$usuario['status']) {
            echo $this->template->renderizar("senha/codigo.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => 'Usuário não encontrado!',
                'tipo_aviso' => 'danger',
                'codigo' => $codigo
            ]);
            exit;
        }

        $usuario = $usuario['usuario'];
        $usuario->setSenha(password_hash($extracao['inputSenha'], PASSWORD_DEFAULT));

        $alterar = $usuarioServico->atualizarUsuarioSenha($usuario);

        if (!$alterar['status']) {
            echo $this->template->renderizar("senha/codigo.html", [
                'pagina' => 'Redefinir Senha',
                'titulo' => 'Redefinir Senha',
                'ano' => Helpers::ano(),
                'aviso' => $alterar['mensagem'],
                'tipo_aviso' => 'danger',
                'codigo' => $codigo
            ]);
            exit;
        }

        $token->setUtilizado(true);
        $token->setUtilizadoEm(date('Y-m-d H:i:s'));
        $tokenServico->atualizarToken($token);

        $_SESSION['flash'] = ['mensagem' => 'Senha alterada com sucesso! Realize o login.', 'tipo' => 'success'];
        Helpers::redirecionar('/');
    }


    /**
     * Renderiza a página inicial do administrador.
     * @return void
     */
    public function admin(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {

                $certificadoServico = new CertificadoServico();

                $dadosDashboard = $certificadoServico->buscarDadosDashboard($_SESSION['usuario_id']);

                echo $this->template->renderizar("admin/index.html", [
                    'pagina' => 'Admin / Dashboard',
                    'titulo' => 'Admin / Dashboard',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'sidebar' => 'Dashboard',
                    'dados' => $dadosDashboard
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function perfilAdmin(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }
        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                echo $this->template->renderizar("admin/perfil.html", [
                    'pagina' => 'Admin / Perfil',
                    'titulo' => 'Admin / Perfil',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'aviso' => $aviso,
                    'tipo_aviso' => $tipo_aviso
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function perfilAdminForm(): void
    {
        $usuarioServico = new UsuarioServico();
        $usuarioLogado = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

        if (!$usuarioLogado['status']) {
            Helpers::redirecionar('/');
            exit;
        }

        $usuario = $usuarioLogado['usuario'];

        $camposEsperados = ['inputNome', 'inputTelefone', 'inputDataNasc'];
        $dados = Helpers::extrairCampos($_POST, $camposEsperados);

        if (empty($dados)) {
            echo $this->template->renderizar("admin/perfil.html", [
                'pagina' => 'Admin / Perfil',
                'titulo' => 'Admin / Perfil',
                'ano' => Helpers::ano(),
                'aviso' => 'Preencha todos os campos obrigatórios.',
                'tipo_aviso' => 'danger',
                'campos' => $dados,
                'usuario' => $usuario
            ]);
            exit;
        }

        if (!Helpers::validarTelefone($dados['inputTelefone'])) {
            echo $this->template->renderizar("admin/perfil.html", [
                'pagina' => 'Admin / Perfil',
                'titulo' => 'Admin / Perfil',
                'ano' => Helpers::ano(),
                'aviso' => 'Telefone inválido.',
                'tipo_aviso' => 'danger',
                'campos' => $dados,
                'usuario' => $usuario
            ]);
            exit;
        }

        if (!Helpers::validarData($dados['inputDataNasc'])) {
            echo $this->template->renderizar("admin/perfil.html", [
                'pagina' => 'Admin / Perfil',
                'titulo' => 'Admin / Perfil',
                'ano' => Helpers::ano(),
                'aviso' => 'Data de nascimento inválida.',
                'tipo_aviso' => 'danger',
                'campos' => $dados,
                'usuario' => $usuario
            ]);
            exit;
        }

        $arquivo = $_FILES['inputImagemPerfil'] ?? null;
        $imagemBase64 = $usuario->getImg();

        if (!empty($arquivo) && $arquivo['error'] === 0) {
            $tiposPermitidos = ["image/png", "image/jpeg"];
            if (!in_array($arquivo['type'], $tiposPermitidos)) {
                echo $this->template->renderizar("admin/perfil.html", [
                    'pagina' => 'Admin / Perfil',
                    'titulo' => 'Admin / Perfil',
                    'ano' => Helpers::ano(),
                    'aviso' => 'Formato de imagem inválido. Use PNG ou JPEG.',
                    'tipo_aviso' => 'danger',
                    'campos' => $dados,
                    'usuario' => $usuario
                ]);
                exit;
            } elseif ($arquivo['size'] > 5000000) {
                echo $this->template->renderizar("admin/perfil.html", [
                    'pagina' => 'Admin / Perfil',
                    'titulo' => 'Admin / Perfil',
                    'ano' => Helpers::ano(),
                    'aviso' => 'Imagem muito grande. Máximo 5MB.',
                    'tipo_aviso' => 'danger',
                    'campos' => $dados,
                    'usuario' => $usuario
                ]);
                exit;
            }

            $caminho_temp = $arquivo['tmp_name'];
            $novo_caminho = sys_get_temp_dir() . '/' . uniqid('img_', true) . '.jpg';

            $imagemComprimida = Helpers::comprimirImagem($caminho_temp, $novo_caminho, 90);
            if ($imagemComprimida) {
                $imagemReduzida = Helpers::reduzirTamanhoImagem($imagemComprimida, $novo_caminho, 300, 300);
                if ($imagemReduzida) {
                    $imagemBase64 = base64_encode(file_get_contents($imagemReduzida));
                }
            }
        }

        $verificacaoDuplicidade = $usuarioServico->buscarDuplicidadePor(
            'telefone',
            $dados['inputTelefone'],
            $usuario->getId()
        );

        if ($verificacaoDuplicidade['status']) {
            echo $this->template->renderizar("admin/perfil.html", [
                'pagina' => 'Admin / Perfil',
                'titulo' => 'Admin / Perfil',
                'ano' => Helpers::ano(),
                'aviso' => $verificacaoDuplicidade['mensagem'],
                'tipo_aviso' => 'danger',
                'campos' => $dados,
                'usuario' => $usuario
            ]);
            exit;
        }

        $usuario->setNome($dados['inputNome']);
        $usuario->setTelefone($dados['inputTelefone']);
        $usuario->setDatanasc($dados['inputDataNasc']);
        $usuario->setImg($imagemBase64);

        $resultado = $usuarioServico->atualizarUsuario($usuario);

        if ($resultado['status']) {
            $_SESSION['flash'] = ['mensagem' => 'Perfil atualizado com sucesso.', 'tipo' => 'success'];
            Helpers::redirecionar('/admin/perfil');
            exit;
        } else {
            $_SESSION['flash'] = ['mensagem' => 'Erro ao atualizar perfil. Tente novamente.', 'tipo' => 'danger'];
            Helpers::redirecionar('/admin/perfil');
            exit;
        }
    }

    public function modeloCertificado(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }
        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                echo $this->template->renderizar("admin/certificados/gerar-modelo.html", [
                    'pagina' => 'Certificado / Gerar Modelo',
                    'titulo' => 'Certificado / Gerar Modelo',
                    'sidebar' => 'Certificados',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'aviso' => $aviso,
                    'tipo_aviso' => $tipo_aviso,
                    'baseUrlValidacao' => '<script> const baseUrlValidacao = "' . Helpers::baseUrl() . '"</script>'
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function salvarModeloCertificado(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['nome'])) {
                    throw new Exception('Nome do modelo é obrigatório');
                }

                if (empty($input['orientacao'])) {
                    throw new Exception('Orientação do modelo é obrigatório');
                }

                $elementos = $input['elementos'] ?? '[]';
                if (is_array($elementos)) {
                    $elementos = json_encode($elementos);
                }

                $paginas = $input['paginas'] ?? '[]';
                if (is_array($paginas)) {
                    $paginas = json_encode($paginas);
                }

                $highResPreview = $input['highResPreview'] ?? null;

                $certificadoServico = new CertificadoServico();

                try {
                    $resultado = $certificadoServico->criar(
                        $usuario['usuario']->getId(),
                        $input['nome'],
                        $input['orientacao'],
                        $elementos,
                        $paginas,
                        null, 
                    );

                    if ($resultado['status']) {
                        echo json_encode([
                            'status' => true,
                            'mensagem' => 'Modelo salvo com sucesso!'
                        ]);
                        exit;
                    } else {
                        echo json_encode(['status' => false, 'mensagem' => $resultado['mensagem']]);
                        exit;
                    }
                } catch (Exception $e) {
                    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
                    exit;
                }
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function listarModelosCertificados(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }
        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                $certificadoServico = new CertificadoServico();
                $modelos = $certificadoServico->buscarModelosParaDatatables($usuario['usuario']->getId());

                echo $this->template->renderizar("admin/certificados/listar-modelos.html", [
                    'pagina' => 'Certificado / Listar Modelos',
                    'titulo' => 'Certificado / Listar Modelos',
                    'sidebar' => 'Certificados',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'aviso' => $aviso,
                    'tipo_aviso' => $tipo_aviso,
                    'modelos' => '<script> const modelos = ' . json_encode($modelos['certificados']) . '</script>'
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function gerarCertificados(string $id): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        $aviso = null;
        $tipo_aviso = null;
        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }
        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                $id = Helpers::decryptData($id);

                $certificadoServico = new CertificadoServico();
                $modelo = $certificadoServico->buscarPorId($id);

                if (!$modelo['status']) {
                    $_SESSION['flash'] = [
                        'mensagem' => $modelo['mensagem'],
                        'tipo' => 'danger'
                    ];
                    Helpers::redirecionar('/certificados/listar-modelos');
                }


                if ($modelo['certificado']->getIdUsuario() != $usuario['usuario']->getId()) {
                    $_SESSION['flash'] = [
                        'mensagem' => 'Você não possui permissão para gerar certificados deste modelo',
                        'tipo' => 'danger'
                    ];
                    Helpers::redirecionar('/certificados/listar-modelos');
                }
                echo $this->template->renderizar("admin/certificados/gerar-certificados.html", [
                    'pagina' => 'Certificado / Gerar Certificados',
                    'titulo' => 'Certificado / Gerar Certificados',
                    'sidebar' => 'Certificados',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'aviso' => $aviso,
                    'tipo_aviso' => $tipo_aviso,
                    'modelo' => $modelo['certificado'],
                    'modeloRaw' => '<script> const modeloRaw = ' . json_encode($modelo['certificado']->toArray()) . '</script>',
                    'baseUrlValidacao' => '<script> const baseUrlValidacao = "' . Helpers::baseUrl() . '"</script>'
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function salvarCertificado(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $json = file_get_contents("php://input");
            $data = json_decode($json, true);

            if (!$data) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Dados inválidos recebidos.'
                ]);
                return;
            }

            $servico = new CertificadoServico();
            $resultado = $servico->salvarCertificadoGerado($data, $_SESSION['usuario_id']);

            echo json_encode($resultado);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'mensagem' => "Erro no servidor: " . $e->getMessage()
            ]);
        }
    }

    public function obterModeloExistente(string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $autenticacao = new AutenticarServico(db::getInstancia());
            $verificacaoSessao = $autenticacao->verificarSessao();

            if (!$verificacaoSessao) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Sessão expirada. Faça login novamente.'
                ]);
                return;
            }

            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if (!$usuario['status'] || $usuario['usuario']->getPerfil() != 'admin') {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Acesso não autorizado.'
                ]);
                return;
            }

            $idDecriptado = Helpers::decryptData($id);

            $certificadoServico = new CertificadoServico();
            $modelo = $certificadoServico->buscarPorId($idDecriptado);

            if (!$modelo['status']) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => $modelo['mensagem']
                ]);
                return;
            }

            if ($modelo['certificado']->getIdUsuario() != $usuario['usuario']->getId()) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Você não possui permissão para acessar este modelo'
                ]);
                return;
            }

            echo json_encode([
                'status' => true,
                'mensagem' => 'Modelo encontrado com sucesso',
                'modelo' => $modelo['certificado']->toArray()
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'mensagem' => "Erro no servidor: " . $e->getMessage()
            ]);
        }
    }

    public function atualizarModeloCertificado(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $autenticacao = new AutenticarServico(db::getInstancia());
            $verificacaoSessao = $autenticacao->verificarSessao();

            if (!$verificacaoSessao) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Sessão expirada. Faça login novamente.'
                ]);
                return;
            }

            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if (!$usuario['status'] || $usuario['usuario']->getPerfil() != 'admin') {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Acesso não autorizado.'
                ]);
                return;
            }

            $json = file_get_contents("php://input");
            $data = json_decode($json, true);

            if (!$data) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Dados inválidos recebidos.'
                ]);
                return;
            }

            if (
                !isset($data['id']) || !isset($data['nome']) || !isset($data['orientacao']) ||
                !isset($data['elementos']) || !isset($data['paginas'])
            ) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Dados incompletos para atualização.'
                ]);
                return;
            }

            $idDecriptado = Helpers::decryptData($data['id']);

            $certificadoServico = new CertificadoServico();
            $modeloAtual = $certificadoServico->buscarPorId($idDecriptado);

            if (!$modeloAtual['status']) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => $modeloAtual['mensagem']
                ]);
                return;
            }

            if ($modeloAtual['certificado']->getIdUsuario() != $usuario['usuario']->getId()) {
                echo json_encode([
                    'status' => false,
                    'mensagem' => 'Você não possui permissão para editar este modelo'
                ]);
                return;
            }

            $certificado = new ModeloCertificado(
                $data['id'], // ID já criptografado
                $usuario['usuario']->getId(),
                $data['nome'],
                $data['orientacao'],
                $data['elementos'],
                $data['paginas'],
                $data['background'] ?? $modeloAtual['certificado']->getBackground(),
                $modeloAtual['certificado']->getCriadoEm(),
                date('Y-m-d H:i:s')
            );

            $resultado = $certificadoServico->atualizarCertificado($certificado);

            if ($resultado['status']) {
                echo json_encode([
                    'status' => true,
                    'mensagem' => 'Modelo atualizado com sucesso!',
                    'modelo' => $resultado['certificado']->toArray()
                ]);
            } else {
                echo json_encode([
                    'status' => false,
                    'mensagem' => $resultado['mensagem']
                ]);
            }

        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'mensagem' => "Erro no servidor: " . $e->getMessage()
            ]);
        }
    }

    public function excluirModeloCertificado(string $id): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {

                if (empty($id)) {
                    echo json_encode([
                        'status' => false,
                        'mensagem' => 'Dados inválidos recebidos. O ID do modelo precisa ser informado.'
                    ]);
                    exit;
                }
                $certificadoServico = new CertificadoServico();
                try {
                    $resultado = $certificadoServico->removerModeloCertificado($id);
                    if ($resultado['status']) {
                        echo json_encode([
                            'status' => true,
                            'mensagem' => 'Modelo removido com sucesso!'
                        ]);
                        exit;
                    } else {
                        echo json_encode(['status' => false, 'mensagem' => $resultado['mensagem']]);
                        exit;
                    }
                } catch (Exception $e) {
                    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
                    exit;
                }
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function excluirCertificado(string $id): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {

                if (empty($id)) {
                    echo json_encode([
                        'status' => false,
                        'mensagem' => 'Dados inválidos recebidos. O ID do modelo precisa ser informado.'
                    ]);
                    exit;
                }
                $certificadoServico = new CertificadoServico();
                try {
                    $resultado = $certificadoServico->removerCertificado($id);
                    if ($resultado['status']) {
                        echo json_encode([
                            'status' => true,
                            'mensagem' => 'Modelo removido com sucesso!'
                        ]);
                        exit;
                    } else {
                        echo json_encode(['status' => false, 'mensagem' => $resultado['mensagem']]);
                        exit;
                    }
                } catch (Exception $e) {
                    echo json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
                    exit;
                }
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function listarCertificados(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }
        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                $certificadoServico = new CertificadoServico();
                $certificados = $certificadoServico->buscarCertificadosPorIdUsuario($usuario['usuario']->getId());
                echo $this->template->renderizar("admin/certificados/listar-certificados.html", [
                    'pagina' => 'Certificado / Certificados Emitidos',
                    'titulo' => 'Certificado / Certificados Emitidos',
                    'sidebar' => 'Certificados',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'aviso' => $aviso,
                    'tipo_aviso' => $tipo_aviso,
                    'certificados' => '<script> const certificados = ' . json_encode($certificados['certificados']) . '</script>'
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    /**
     * Encerra a sessão do usuário e redireciona para a página de login.
     * @return void
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: /');
        exit;
    }

    public function obterDadosCompletosCertificado()
    {
        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                return json_encode(['status' => false, 'mensagem' => 'ID não fornecido']);
            }

            $certificadoServico = new CertificadoServico();
            $resultadoCertificado = $certificadoServico->buscarCertificadosPorId(Helpers::decryptData($id));


            if (!$resultadoCertificado['status']) {
                throw new Exception('Certificado não encontrado');
            }

            $certificado = $resultadoCertificado['certificados'][0];

            $resultadoModelo = $certificadoServico->buscarPorId($certificado->getIdModelo());

            if (!$resultadoModelo['status']) {
                throw new Exception('Modelo não encontrado');
            }

            $modelo = $resultadoModelo['certificado'];

            $dados = [
                'nome' => $certificado->getNomeAluno(),
                'cpf' => $certificado->getCpfAluno(),
                'curso' => $certificado->getCurso(),
                'cargahoraria' => $certificado->getCargaHoraria(),
                'qrCode' => $certificado->getCodigoVerificacao(),
                'dataEmissao' => $certificado->getDataEmissao(),
                ...(json_decode($certificado->getCamposPersonalizados(), true) ?? [])
            ];

            return json_encode([
                'status' => true,
                'modelo' => [
                    'id' => $modelo->getId(),
                    'nome' => $modelo->getNome(),
                    'orientacao' => $modelo->getOrientacao(),
                    'elementos' => $modelo->getElementos(),
                    'paginas' => $modelo->getPaginas(),
                    'background' => $modelo->getBackground()
                ],
                'dados' => $dados
            ]);

        } catch (Exception $e) {
            return json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
        }
    }

    public function obterDadosCompletosCertificadoVisualizar()
    {
        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                return json_encode(['status' => false, 'mensagem' => 'ID não fornecido']);
            }

            $certificadoServico = new CertificadoServico();

            $id = $certificadoServico->buscarCertificadoPorCodigo($id);

            if (!$id['status']) {
                return json_encode(['status' => false, 'mensagem' => 'Não foi possível encontrar este certificado.']);
            }

            $resultadoCertificado = $certificadoServico->buscarCertificadosPorId($id['certificado']['id']);


            if (!$resultadoCertificado['status']) {
                throw new Exception('Certificado não encontrado');
            }

            $certificado = $resultadoCertificado['certificados'][0];

            $resultadoModelo = $certificadoServico->buscarPorId($certificado->getIdModelo());

            if (!$resultadoModelo['status']) {
                throw new Exception('Modelo não encontrado');
            }

            $modelo = $resultadoModelo['certificado'];

            $dados = [
                'nome' => $certificado->getNomeAluno(),
                'cpf' => $certificado->getCpfAluno(),
                'curso' => $certificado->getCurso(),
                'cargahoraria' => $certificado->getCargaHoraria(),
                'qrCode' => $certificado->getCodigoVerificacao(),
                'dataEmissao' => $certificado->getDataEmissao(),
                ...(json_decode($certificado->getCamposPersonalizados(), true) ?? [])
            ];

            return json_encode([
                'status' => true,
                'modelo' => [
                    'id' => $modelo->getId(),
                    'nome' => $modelo->getNome(),
                    'orientacao' => $modelo->getOrientacao(),
                    'elementos' => $modelo->getElementos(),
                    'paginas' => $modelo->getPaginas(),
                    'background' => $modelo->getBackground()
                ],
                'dados' => $dados
            ]);

        } catch (Exception $e) {
            return json_encode(['status' => false, 'mensagem' => $e->getMessage()]);
        }
    }

    public function suporte(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        $aviso = null;
        $tipo_aviso = null;

        if (isset($_SESSION['flash'])) {
            $aviso = $_SESSION['flash']['mensagem'];
            $tipo_aviso = $_SESSION['flash']['tipo'];
            unset($_SESSION['flash']);
        }

        if ($verificacaoSessao) {
            $usuarioServico = new UsuarioServico();
            $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

            if ($usuario['status'] && $usuario['usuario']->getPerfil() == 'admin') {
                echo $this->template->renderizar("admin/suporte.html", [
                    'pagina' => 'Admin / Suporte',
                    'titulo' => 'Admin / Suporte',
                    'sidebar' => 'Suporte',
                    'ano' => Helpers::ano(),
                    'usuario' => $usuario['usuario'],
                    'aviso' => $aviso,
                    'tipo_aviso' => $tipo_aviso,
                ]);
                exit;
            } else {
                Helpers::redirecionar('/logout');
            }
        } else {
            Helpers::redirecionar('/logout');
        }
    }

    public function suporteForm(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        if (!$verificacaoSessao) {
            Helpers::redirecionar('/logout');
            return;
        }

        $usuarioServico = new UsuarioServico();
        $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

        if (!$usuario['status'] || $usuario['usuario']->getPerfil() != 'admin') {
            Helpers::redirecionar('/logout');
            return;
        }

        $camposEsperados = ['assunto', 'prioridade', 'mensagem', 'tipo_contato'];
        $extracao = Helpers::extrairCampos($_POST, $camposEsperados);

        if (empty($extracao['assunto'])) {
            $_SESSION['flash'] = ['mensagem' => 'O assunto é obrigatório!', 'tipo' => 'danger'];
            Helpers::redirecionar('/admin/suporte');
            return;
        }

        if (empty($extracao['mensagem'])) {
            $_SESSION['flash'] = ['mensagem' => 'A mensagem é obrigatória!', 'tipo' => 'danger'];
            Helpers::redirecionar('/admin/suporte');
            return;
        }

        if (strlen($extracao['mensagem']) < 10) {
            $_SESSION['flash'] = ['mensagem' => 'A mensagem deve ter pelo menos 10 caracteres!', 'tipo' => 'danger'];
            Helpers::redirecionar('/admin/suporte');
            return;
        }

        $emailServico = new EmailServico();
        $envio = $emailServico->enviarEmailSuporte(
            $extracao['assunto'],
            $extracao['mensagem'],
            $extracao['prioridade'] ?? 'normal',
            $extracao['tipo_contato'] ?? 'geral',
            $usuario['usuario']
        );

        if ($envio['status']) {
            $_SESSION['flash'] = ['mensagem' => 'Mensagem de suporte enviada com sucesso! Responderemos em breve.', 'tipo' => 'success'];
        } else {
            $_SESSION['flash'] = ['mensagem' => 'Erro ao enviar mensagem: ' . $envio['mensagem'], 'tipo' => 'danger'];
        }

        Helpers::redirecionar('/admin/suporte');
    }
    public function copiarModelo(): void
    {
        $autenticacao = new AutenticarServico(db::getInstancia());
        $verificacaoSessao = $autenticacao->verificarSessao();

        if (!$verificacaoSessao) {
            echo json_encode(['status' => false, 'mensagem' => 'Sessão expirada!']);
            return;
        }

        $usuarioServico = new UsuarioServico();
        $usuario = $usuarioServico->buscarUsuarioPor('id', $_SESSION['usuario_id']);

        if (!$usuario['status']) {
            echo json_encode(['status' => false, 'mensagem' => 'Usuário não encontrado!']);
            return;
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        if (!isset($dados['id_modelo']) || !isset($dados['novo_nome'])) {
            echo json_encode(['status' => false, 'mensagem' => 'Dados incompletos!']);
            return;
        }

        $certificadoServico = new CertificadoServico();
        $resultado = $certificadoServico->copiarModelo(
            Helpers::decryptData($dados['id_modelo']),
            $dados['novo_nome'],
            $usuario['usuario']->getId()
        );

        echo json_encode($resultado);
    }
}
