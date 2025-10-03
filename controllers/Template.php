<?php

namespace controllers;

use controllers\Helpers;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Template
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $this->twig = new Environment($loader, [
            'debug' => true,
        ]);

        // Ativa o modo de depuração do Twig
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());

        // Registra todas as funções estáticas de Helpers como funções Twig
        foreach (get_class_methods(Helpers::class) as $method) {
            // Só registra métodos estáticos públicos
            $this->twig->addFunction(new TwigFunction($method, [Helpers::class, $method]));
        }
    }

    /**
     * Renderiza um template Twig com dados passados
     * @param string $template Nome do template (ex: 'home.twig')
     * @param array $dados Dados a serem passados ao template
     * @return string HTML renderizado
     */
    public function renderizar(string $template, array $dados = []): string
    {
        return $this->twig->render($template, $dados);
    }
    
}
