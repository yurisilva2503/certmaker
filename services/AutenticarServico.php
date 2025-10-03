<?php

namespace services;

use models\Usuario;
use PDO;
use Google\Service\Oauth2\Userinfo;
use controllers\Helpers;
class AutenticarServico
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function authLocal(string $email, string $senha): array
    {
        // SELECT para ver se o usuário existe no banco
        $stmt = $this->pdo->prepare("SELECT id, email, senha, tipo_cadastro FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuarioBanco = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioBanco) {
            // Se não existe, retorna erro
            return ['status' => false, 'mensagem' => 'Usuário não encontrado.'];
        }

        if ($usuarioBanco['tipo_cadastro'] != 'local') {
            return ['status' => false, 'mensagem' => 'Você se registrou com uma conta google. Faça o login com ela!'];
        }

        if (!password_verify($senha, $usuarioBanco['senha'])) {
            return ['status' => false, 'mensagem' => 'Usuário e/ou senha incorretos.'];
        } else {
            $usuarioID = $usuarioBanco['id'];
            // Gera um token de sessão único
            $sessionToken = bin2hex(random_bytes(32));

            // Salva no banco o token atual
            $stmt = $this->pdo->prepare("UPDATE usuarios SET session_token = ? WHERE id = ?");
            $stmt->execute([$sessionToken, $usuarioID]);

            // Regenerar ID da sessão
            session_regenerate_id(true);

            // Guarda os dados de autenticação na sessão
            $_SESSION['usuario_id'] = $usuarioID;
            $_SESSION['autenticado'] = true;
            $_SESSION['session_token'] = $sessionToken;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            return ['status' => true, 'mensagem' => 'Usuário logado com sucesso'];
        }
    }

    public function verificarSessao(): bool
    {
        if (!isset($_SESSION['autenticado'], $_SESSION['usuario_id'], $_SESSION['session_token'])) {
            return false;
        }

        // Busca token no banco
        $stmt = $this->pdo->prepare("SELECT session_token FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $tokenBanco = $stmt->fetchColumn();

        if (!$tokenBanco)
            return false;

        // Valida token e user agent
        if (
            $tokenBanco !== $_SESSION['session_token'] ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] ||
            $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']
        ) {
            Helpers::redirecionar('/logout');
            return false;
        }

        return true;
    }

}
