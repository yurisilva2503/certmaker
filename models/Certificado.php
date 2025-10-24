<?php

namespace models;

/**
 * Classe Certificado
 * @package models
 *
 * @property int $id
 * @property string $codigo_verificacao
 * @property int $id_modelo
 * @property string $nome_aluno
 * @property string $cpf_aluno
 * @property string $curso
 * @property string $carga_horaria
 * @property string $data_emissao
 * @property string|null $data_validade
 * @property string $url_qr_code
 * @property string|null $caminho_pdf
 * @property string $campos_personalizados
 * @property string $status
 * @property string $criado_em
 * @property string|null $atualizado_em
 * @property int $id_gerador
 */
class Certificado implements \JsonSerializable
{
    private $id;
    private $codigo_verificacao;
    private $id_modelo;
    private $nome_aluno;
    private $cpf_aluno;
    private $curso;
    private $carga_horaria;
    private $data_emissao;
    private $data_validade;
    private $url_qr_code;
    private $caminho_pdf;
    private $campos_personalizados;
    private $status;
    private $criado_em;
    private $atualizado_em;
    private $id_gerador;

    public function __construct(
        $id = null,
        $codigo_verificacao = null,
        $id_modelo = null,
        $nome_aluno = null,
        $cpf_aluno = null,
        $curso = null,
        $carga_horaria = null,
        $data_emissao = null,
        $data_validade = null,
        $url_qr_code = null,
        $caminho_pdf = null,
        $campos_personalizados = null,
        $status = null,
        $criado_em = null,
        $atualizado_em = null,
        $id_gerador = null
    ) {
        $this->id = $id;
        $this->codigo_verificacao = $codigo_verificacao;
        $this->id_modelo = $id_modelo;
        $this->nome_aluno = $nome_aluno;
        $this->cpf_aluno = $cpf_aluno;
        $this->curso = $curso;
        $this->carga_horaria = $carga_horaria;
        $this->data_emissao = $data_emissao;
        $this->data_validade = $data_validade;
        $this->url_qr_code = $url_qr_code;
        $this->caminho_pdf = $caminho_pdf;
        $this->campos_personalizados = $campos_personalizados;
        $this->status = $status;
        $this->criado_em = $criado_em;
        $this->atualizado_em = $atualizado_em;
        $this->id_gerador = $id_gerador;
    }

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getCodigoVerificacao()
    {
        return $this->codigo_verificacao;
    }
    public function setCodigoVerificacao($codigo_verificacao)
    {
        $this->codigo_verificacao = $codigo_verificacao;
    }

    public function getIdModelo()
    {
        return $this->id_modelo;
    }
    public function setIdModelo($id_modelo)
    {
        $this->id_modelo = $id_modelo;
    }

    public function getNomeAluno()
    {
        return $this->nome_aluno;
    }
    public function setNomeAluno($nome_aluno)
    {
        $this->nome_aluno = $nome_aluno;
    }

    public function getCpfAluno()
    {
        return $this->cpf_aluno;
    }
    public function setCpfAluno($cpf_aluno)
    {
        $this->cpf_aluno = $cpf_aluno;
    }

    public function getCurso()
    {
        return $this->curso;
    }
    public function setCurso($curso)
    {
        $this->curso = $curso;
    }

    public function getCargaHoraria()
    {
        return $this->carga_horaria;
    }
    public function setCargaHoraria($carga_horaria)
    {
        $this->carga_horaria = $carga_horaria;
    }

    public function getDataEmissao()
    {
        return $this->data_emissao;
    }
    public function setDataEmissao($data_emissao)
    {
        $this->data_emissao = $data_emissao;
    }

    public function getDataValidade()
    {
        return $this->data_validade;
    }
    public function setDataValidade($data_validade)
    {
        $this->data_validade = $data_validade;
    }

    public function getUrlQrCode()
    {
        return $this->url_qr_code;
    }
    public function setUrlQrCode($url_qr_code)
    {
        $this->url_qr_code = $url_qr_code;
    }

    public function getCaminhoPdf()
    {
        return $this->caminho_pdf;
    }
    public function setCaminhoPdf($caminho_pdf)
    {
        $this->caminho_pdf = $caminho_pdf;
    }

    public function getCamposPersonalizados()
    {
        return $this->campos_personalizados;
    }
    public function setCamposPersonalizados($campos_personalizados)
    {
        $this->campos_personalizados = $campos_personalizados;
    }

    public function getStatus()
    {
        return $this->status;
    }
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getCriadoEm()
    {
        return $this->criado_em;
    }
    public function setCriadoEm($criado_em)
    {
        $this->criado_em = $criado_em;
    }

    public function getAtualizadoEm()
    {
        return $this->atualizado_em;
    }
    public function setAtualizadoEm($atualizado_em)
    {
        $this->atualizado_em = $atualizado_em;
    }

    public function getIdGerador()
    {
        return $this->id_gerador;
    }
    public function setIdGerador($id_gerador)
    {
        $this->id_gerador = $id_gerador;
    }

    /**
     * Converte o JSON de campos_personalizados para array
     */
    public function getCamposPersonalizadosArray()
    {
        return json_decode($this->campos_personalizados, true);
    }

    /**
     * Define os campos personalizados como JSON
     */
    public function setCamposPersonalizadosArray($array)
    {
        $this->campos_personalizados = json_encode($array);
    }

    /**
     * Verifica se o certificado está ativo
     */
    public function isAtivo()
    {
        return $this->status === 'ativo';
    }

    /**
     * Verifica se o certificado está expirado
     */
    public function isExpirado()
    {
        if ($this->data_validade === null) {
            return false;
        }
        
        $dataValidade = new \DateTime($this->data_validade);
        $dataAtual = new \DateTime();
        
        return $dataAtual > $dataValidade;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo_verificacao' => $this->codigo_verificacao,
            'id_modelo' => $this->id_modelo,
            'nome_aluno' => $this->nome_aluno,
            'cpf_aluno' => $this->cpf_aluno,
            'curso' => $this->curso,
            'carga_horaria' => $this->carga_horaria,
            'data_emissao' => $this->data_emissao,
            'data_validade' => $this->data_validade,
            'url_qr_code' => $this->url_qr_code,
            'caminho_pdf' => $this->caminho_pdf,
            'campos_personalizados' => $this->getCamposPersonalizadosArray(),
            'status' => $this->status,
            'criado_em' => $this->criado_em,
            'atualizado_em' => $this->atualizado_em,
            'id_gerador' => $this->id_gerador,
        ];
    }

     public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}