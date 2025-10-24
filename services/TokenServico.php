<?php

namespace services;

use PDO;
use PDOException;
use models\Token;
use config\database as db;

class TokenServico
{
    private $table = 'tokens';

    public function criar($token, $id_usuario, $acao): array
    {
        try {

            $token = new Token(null, $token, $id_usuario, $acao, date('Y-m-d H:i:s'), 0, null, 0);

            $sql = "INSERT INTO {$this->table} (codigo, id_usuario, acao, criado_em, utilizado, utilizado_em, expirado)
                    VALUES (:codigo, :id_usuario, :acao, :criado_em, :utilizado, :utilizado_em, :expirado)";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([
                ':codigo' => $token->getCodigo(),
                ':id_usuario' => $token->getIdUsuario(),
                ':acao' => $token->getAcao(),
                ':criado_em' => $token->getCriadoEm(),
                ':utilizado' => $token->getUtilizado(),
                ':utilizado_em' => $token->getUtilizadoEm(),
                ':expirado' => $token->getExpirado()
            ]);

            $token->setId(db::getInstancia()->lastInsertId());
            return ['status' => true, 'mensagem' => 'Token criado com sucesso!', 'token' => $token];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao criar token: " . $e->getMessage()];
        }
    }

    public function buscarPorId(int $id): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Token não encontrado!'];
            }

            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            $token = new Token(
                $dados['id'],
                $dados['codigo'],
                $dados['id_usuario'],
                $dados['acao'],
                $dados['criado_em'],
                $dados['utilizado'],
                $dados['utilizado_em'],
                $dados['expirado']
            );

            return ['status' => true, 'mensagem' => 'Token encontrado!', 'token' => $token];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao buscar token: " . $e->getMessage()];
        }
    }

    public function buscarPorCodigo(string $codigo): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE codigo = :codigo";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':codigo' => $codigo]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Token não encontrado!'];
            }

            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            $token = new Token(
                $dados['id'],
                $dados['codigo'],
                $dados['id_usuario'],
                $dados['acao'],
                $dados['criado_em'],
                $dados['utilizado'],
                $dados['utilizado_em'],
                $dados['expirado']
            );

            return ['status' => true, 'mensagem' => 'Token encontrado!', 'token' => $token];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao buscar token: " . $e->getMessage()];
        }
    }

    public function listarTodos(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $stmt = db::getInstancia()->query($sql);

            $tokens = [];
            while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tokens[] = new Token(
                    $dados['id'],
                    $dados['codigo'],
                    $dados['id_usuario'],
                    $dados['acao'],
                    $dados['criado_em'],
                    $dados['utilizado'],
                    $dados['utilizado_em'],
                    $dados['expirado']
                );
            }

            return ['status' => true, 'mensagem' => 'Tokens listados!', 'tokens' => $tokens];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao listar tokens: " . $e->getMessage()];
        }
    }

    public function atualizarToken(Token $token): array
    {
        try {
            $sql = "UPDATE {$this->table} SET codigo = :codigo, id_usuario = :id_usuario, acao = :acao, criado_em = :criado_em, utilizado = :utilizado, utilizado_em = :utilizado_em, expirado = :expirado WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([
                ':id' => $token->getId(),
                ':codigo' => $token->getCodigo(),
                ':id_usuario' => $token->getIdUsuario(),
                ':acao' => $token->getAcao(),
                ':criado_em' => $token->getCriadoEm(),
                ':utilizado' => $token->getUtilizado(),
                ':utilizado_em' => $token->getUtilizadoEm(),
                ':expirado' => $token->getExpirado()
            ]);

            return ['status' => true, 'mensagem' => 'Token atualizado com sucesso!', 'token' => $token];
        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao atualizar token: " . $e->getMessage()];
        }
    }

    public function removerToken(int $id): array
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Token não encontrado!'];
            }

            return ['status' => true, 'mensagem' => 'Token deletado com sucesso!'];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao deletar token: " . $e->getMessage()];
        }
    }

    public function validarCodigo(string $codigo, string $acao): array
    {
        $buscar = $this->buscarPorCodigo($codigo);
        if (!$buscar['status']) {
            return ['status' => false, 'mensagem' => 'Código inválido!'];
        }

        $token = $buscar['token'];

        if ($token->getAcao() !== $acao) {
            return ['status' => false, 'mensagem' => 'Código inválido para esta ação!'];
        }

        if ($token->getExpirado() || $token->getUtilizado()) {
            return ['status' => false, 'mensagem' => 'Código já utilizado ou expirado!'];
        }

        return ['status' => true, 'mensagem' => 'Código válido!', 'token' => $token];
    }

}
