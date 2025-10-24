<?php
namespace models;

/**
 * Classe Certificado
 * @package models
 *
 * @property int $id
 * @property int $id_usuario
 * @property string $nome
 * @property string $orientacao
 * @property array $elementos (array de elementos)
 * @property array $paginas (array de páginas)
 * @property string|null $background
 * @property string $criado_em
 * @property string $atualizado_em
 */
class ModeloCertificado implements \JsonSerializable
{
    private $id;
    private $id_usuario;
    private $nome;
    private $orientacao;
    private $elementos;
    private $paginas; 
    private $background;
    private $criado_em;
    private $atualizado_em;

    public function __construct($id = null, $id_usuario = null, $nome = null, $orientacao = 'portrait', $elementos = null, $paginas = null, $background = null, $criado_em = null, $atualizado_em = null)
{
    $this->id = $id;
    $this->id_usuario = $id_usuario;
    $this->nome = $nome;
    $this->orientacao = $orientacao;
    
    if (is_string($elementos)) {
        $this->elementos = json_decode($elementos, true) ?: [];
    } else {
        $this->elementos = $elementos ?: [];
    }
    
    if (is_string($paginas)) {
        $decoded = json_decode($paginas, true);
        $this->paginas = $decoded ?: [];
    } else {
        $this->paginas = $paginas ?: [];
    }
    
    if (empty($this->paginas) && !empty($this->elementos)) {
        $this->paginas = [[
            'elementos' => $this->elementos,
            'background' => $background
        ]];
    }
    else if (!empty($this->paginas) && empty($this->paginas[0]['elementos']) && !empty($this->elementos)) {
        $this->paginas[0]['elementos'] = $this->elementos;
        if ($background && empty($this->paginas[0]['background'])) {
            $this->paginas[0]['background'] = $background;
        }
    }
    
    $this->background = $background;
    $this->criado_em = $criado_em;
    $this->atualizado_em = $atualizado_em;
}

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdUsuario() { return $this->id_usuario; }
    public function setIdUsuario($id_usuario) { $this->id_usuario = $id_usuario; }

    public function getNome() { return $this->nome; }
    public function setNome($nome) { $this->nome = $nome; }

    public function getOrientacao() { return $this->orientacao; }
    public function setOrientacao($orientacao) { $this->orientacao = $orientacao; }

    public function getElementos() { return $this->elementos; }
    public function setElementos($elementos) { 
        if (is_string($elementos)) {
            $this->elementos = json_decode($elementos, true) ?: [];
        } else {
            $this->elementos = $elementos ?: [];
        }
    }

    public function getPaginas() { return $this->paginas; }
    public function setPaginas($paginas) { 
        if (is_string($paginas)) {
            $this->paginas = json_decode($paginas, true) ?: [];
        } else {
            $this->paginas = $paginas ?: [];
        }
    }

    public function getElementosAsJson() {
        return json_encode($this->elementos);
    }

    public function getPaginasAsJson() {
        return json_encode($this->paginas);
    }

    public function getBackground() { return $this->background; }
    public function setBackground($background) { $this->background = $background; }

    public function getCriadoEm() { return $this->criado_em; }
    public function setCriadoEm($criado_em) { $this->criado_em = $criado_em; }

    public function getAtualizadoEm() { return $this->atualizado_em; }
    public function setAtualizadoEm($atualizado_em) { $this->atualizado_em = $atualizado_em; }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'id_usuario' => $this->id_usuario,
            'nome' => $this->nome,
            'orientacao' => $this->orientacao,
            'elementos' => $this->elementos,
            'paginas' => $this->paginas,
            'background' => $this->background,
            'criado_em' => $this->criado_em,
            'atualizado_em' => $this->atualizado_em
        ];
    }

    public function syncElementosToPaginas()
{
    if (!empty($this->elementos) && (empty($this->paginas) || empty($this->paginas[0]['elementos']))) {
        if (empty($this->paginas)) {
            $this->paginas = [[
                'elementos' => $this->elementos,
                'background' => $this->background
            ]];
        } else {
            $this->paginas[0]['elementos'] = $this->elementos;
            if (empty($this->paginas[0]['background'])) {
                $this->paginas[0]['background'] = $this->background;
            }
        }
    }
    
    if (!empty($this->paginas) && empty($this->elementos) && !empty($this->paginas[0]['elementos'])) {
        $this->elementos = $this->paginas[0]['elementos'];
        if (empty($this->background)) {
            $this->background = $this->paginas[0]['background'] ?? null;
        }
    }
}
}