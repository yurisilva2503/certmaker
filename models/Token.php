<?php

namespace models;

/**
 * Classe Token
 * @package models
 *
 * @property int $id
 * @property string $codigo
 * @property int $id_usuario
 * @property string $acao
 * @property string $criado_em
 * @property int $utilizado
 * @property string|null $utilizado_em
 */
class Token
{
    private $id;
    private $codigo;
    private $id_usuario;
    private $acao;
    private $criado_em;
    private $utilizado;
    private $utilizado_em;
    private $expirado;

    public function __construct($id = null, $codigo = null, $id_usuario = null, $acao = null, $criado_em = null, $utilizado = null, $utilizado_em = null, $expirado = null)
    {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->id_usuario = $id_usuario;
        $this->acao = $acao;
        $this->criado_em = $criado_em;
        $this->utilizado = $utilizado;
        $this->utilizado_em = $utilizado_em;
        $this->expirado = $expirado;
    }

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;
    }

    public function getIdUsuario()
    {
        return $this->id_usuario;
    }
    public function setIdUsuario($id_usuario)
    {
        $this->id_usuario = $id_usuario;
    }

    public function getAcao()
    {
        return $this->acao;
    }
    public function setAcao($acao)
    {
        $this->acao = $acao;
    }

    public function getCriadoEm()
    {
        return $this->criado_em;
    }
    public function setCriadoEm($criado_em)
    {
        $this->criado_em = $criado_em;
    }

    public function getUtilizado()
    {
        return $this->utilizado;
    }
    public function setUtilizado($utilizado)
    {
        $this->utilizado = $utilizado;
    }

    public function getUtilizadoEm()
    {
        return $this->utilizado_em;
    }
    public function setUtilizadoEm($utilizado_em)
    {
        $this->utilizado_em = $utilizado_em;
    }

    public function getExpirado()
    {
        return $this->expirado;
    }
    public function setExpirado($expirado)
    {
        $this->expirado = $expirado;
    }
}
