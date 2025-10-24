<?php

namespace controllers;

use controllers\Template;

/**
 * Class Roteador
 * @package controllers
 * Classe para renderizar templates
 */ 

class Roteador
{
    protected Template $template;

    /**
     * Método mágico construtor da classe
     * @param Template $template Objeto da classe Template
     */
    public function __construct()
    {
        $this->template = new Template();
    }

    /**
     * Método para renderizar templates
     * @param string $template
     * @param array $dados
     * @return string
     */
    public function renderizar(string $template, array $dados = []): string
    {
        return $this->template->renderizar($template, $dados);
    }
}