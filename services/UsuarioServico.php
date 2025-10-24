<?php

namespace services;

use config\database as db;
use models\Usuario;
use PDO;
use PDOException;
use Exception;
use controllers\Helpers;

class UsuarioServico
{

    private $table;

    public function __construct()
    {
        $this->table = "usuarios";
    }

    public function cadastrarUsuario(string $nome, string $email, string $telefone, string $senha, string $datanasc): array
    {
        try {
            $checarDadosEmUso = $this->buscarUsuarioPor('email', $email);

            if ($checarDadosEmUso['usuario']) {
                return [
                    "status" => false,
                    "mensagem" => "Erro ao cadastrar usuário! Este email já está em uso."
                ];
            }


            $inSql = "INSERT INTO usuarios (nome, email, senha, telefone, datanasc, tipo_cadastro, perfil, criado_em) VALUES (:nome, :email, :senha, :telefone, :datanasc, 'local', 'admin', :criado_em)";
            $criado_em = date('Y-m-d H:i:s');
            $stmt = db::getInstancia()->prepare($inSql);
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':senha', $senha, PDO::PARAM_STR);
            $stmt->bindParam(':telefone', $telefone, PDO::PARAM_STR);
            $stmt->bindParam(':datanasc', $datanasc, PDO::PARAM_STR);
            $stmt->bindParam(':criado_em', $criado_em, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return [
                    "status" => true,
                    "mensagem" => "Usuário cadastrado com sucesso! Agora você pode fazer login."
                ];
            } else {
                return [
                    "status" => false,
                    "mensagem" => "Erro desconhecido ao cadastrar usuário!"
                ];
            }
        } catch (PDOException $e) {
            return [
                "status" => false,
                "mensagem" => $e->getMessage()
            ];
        }
    }

    public function buscarUsuarioPor(string $field, mixed $value): array
    {
        try {
            $allowedFields = ['id', 'email', 'nome', 'telefone'];
            if (!in_array($field, $allowedFields)) {
                return ['status' => false, 'mensagem' => "Campo inválido"];
            }

            $sql = "SELECT id, nome, email, telefone, datanasc, tipo_cadastro, google_id, session_token, criado_em, editado_em, perfil, img FROM {$this->table} WHERE {$field} = :value";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->bindParam(":value", $value);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                return [
                    "status" => false,
                    "mensagem" => "Usuário não encontrado!"
                ];
            }

            $array_usuario = $stmt->fetch(mode: PDO::FETCH_ASSOC);
            return [
                'status' => true,
                'mensagem' => 'Usuário encontrado!',
                'usuario' => new Usuario($array_usuario['id'], $array_usuario['nome'],  $array_usuario['email'], $array_usuario['telefone'], $array_usuario['datanasc'], '', $array_usuario['tipo_cadastro'], $array_usuario['google_id'], $array_usuario['session_token'], $array_usuario['criado_em'], $array_usuario['editado_em'], $array_usuario['perfil'], $array_usuario['img']),
            ];
        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function buscarDuplicidadePor(string $field, mixed $value, int $id): array
    {
        try {
            $allowedFields = ['email', 'telefone'];
            if (!in_array($field, $allowedFields)) {
                return [
                    'status' => false,
                    'mensagem' => 'Campo inválido para verificação.'
                ];
            }

            $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value AND id != :id LIMIT 1";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return [
                    'status' => true,
                    'mensagem' => ucfirst($field) . ' já está em uso!',
                ];
            }

            return [
                'status' => false,
                'mensagem' => ucfirst($field) . ' disponível.'
            ];

        } catch (PDOException $e) {
            return [
                'status' => false,
                'mensagem' => 'Erro ao verificar duplicidade: ' . $e->getMessage()
            ];
        }
    }


    public function atualizarUsuario(Usuario $usuario): array
    {
        try {

            $inSql = "UPDATE usuarios 
            SET nome = :nome, 
                email = :email, 
                telefone = :telefone, 
                datanasc = :datanasc, 
                perfil = :perfil, 
                editado_em = :editado_em,
                img = :img
            WHERE id = :id";
            $stmt = db::getInstancia()->prepare($inSql);
            $stmt->execute([
                ':nome' => $usuario->getNome(),
                ':email' => $usuario->getEmail(),
                ':telefone' => $usuario->getTelefone(),
                ':datanasc' => $usuario->getDatanasc(),
                ':perfil' => $usuario->getPerfil(),
                ':id' => $usuario->getId(),
                ':editado_em' => date('Y-m-d H:i:s'),
                ':img' => $usuario->getImg()
            ]);
            if ($stmt->rowCount() > 0) {
                return [
                    "status" => true,
                    "mensagem" => "Usuário atualizado com sucesso!"
                ];
            } else {
                return [
                    "status" => false,
                    "mensagem" => "Erro desconhecido ao atualizar usuário!"
                ];
            }
        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => $e->getMessage()];
        }
    }

    public function atualizarUsuarioSenha(Usuario $usuario): array
    {
        try {

            $inSql = "UPDATE usuarios 
            SET senha = :senha, editado_em = :editado_em 
            WHERE id = :id";
            $stmt = db::getInstancia()->prepare($inSql);
            $stmt->execute([
                ':senha' => $usuario->getSenha(),
                ':editado_em' => date('Y-m-d H:i:s'),
                ':id' => $usuario->getId()
            ]);
            if ($stmt->rowCount() > 0) {
                return [
                    "status" => true,
                    "mensagem" => "Usuário atualizado com sucesso!"
                ];
            } else {
                return [
                    "status" => false,
                    "mensagem" => "Erro desconhecido ao atualizar usuário!"
                ];
            }
        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => $e->getMessage()];
        }
    }
}
