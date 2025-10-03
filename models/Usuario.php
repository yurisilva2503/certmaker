<?php

namespace models;

/**
 * Classe Usuario
 * @package models
 * 
 * @property int $id
 * @property string $nome
 * @property string $email
 * @property string $telefone
 * @property string $datanasc
 * @property string $senha
 * @property string $tipo_cadastro
 * @property string|null $google_id
 * @property string|null $session_token
 * @property string $criado_em
 * @property string|null $editado_em
 * @property int $primeiro_login
 */
class Usuario
{
    private $id;
    private $nome;
    private $email;
    private $telefone;
    private $datanasc;
    private $senha;
    private $tipo_cadastro;
    private $google_id;
    private $session_token;
    private $criado_em;
    private $editado_em;
    private $perfil;
    private $img;

    public function __construct(
        $id,
        $nome,
        $email,
        $telefone,
        $datanasc,
        $senha,
        $tipo_cadastro,
        $google_id,
        $session_token,
        $criado_em,
        $editado_em,
        $perfil,
        $img
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->telefone = $telefone;
        $this->datanasc = $datanasc;
        $this->senha = $senha;
        $this->tipo_cadastro = $tipo_cadastro;
        $this->google_id = $google_id;
        $this->session_token = $session_token;
        $this->criado_em = $criado_em;
        $this->editado_em = $editado_em;
        $this->perfil = $perfil;
        $this->img = $img;
    }

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getNome()
    {
        return $this->nome;
    }
    public function setNome($nome)
    {
        $this->nome = $nome;
    }
    
    public function getEmail()
    {
        return $this->email;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getTelefone()
    {
        return $this->telefone;
    }
    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;
    }

    public function getDatanasc()
    {
        return $this->datanasc;
    }
    public function setDatanasc($datanasc)
    {
        $this->datanasc = $datanasc;
    }

    public function getSenha()
    {
        return $this->senha;
    }
    public function setSenha($senha)
    {
        $this->senha = $senha;
    }

    public function getTipoCadastro()
    {
        return $this->tipo_cadastro;
    }
    public function setTipoCadastro($tipo_cadastro)
    {
        $this->tipo_cadastro = $tipo_cadastro;
    }

    public function getGoogleId()
    {
        return $this->google_id;
    }
    public function setGoogleId($google_id)
    {
        $this->google_id = $google_id;
    }

    public function getSessionToken()
    {
        return $this->session_token;
    }
    public function setSessionToken($session_token)
    {
        $this->session_token = $session_token;
    }

    public function getCriadoEm()
    {
        return $this->criado_em;
    }
    public function setCriadoEm($criado_em)
    {
        $this->criado_em = $criado_em;
    }

    public function getEditadoEm()
    {
        return $this->editado_em;
    }
    public function setEditadoEm($editado_em)
    {
        $this->editado_em = $editado_em;
    }

    public function getPerfil()
    {
        return $this->perfil;
    }
    public function setPerfil($perfil)
    {
        $this->perfil = $perfil;
    }

    public function getImg()
    {
        return $this->img;
    }
    public function setImg($img)
    {
        $this->img = $img;
    }
}
