<?php

use Pecee\SimpleRouter\SimpleRouter;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException as NotFoundHttpException;
use controllers\Helpers;

try {
    SimpleRouter::setDefaultNamespace('controllers');

    // GET
    SimpleRouter::get("/", 'SiteControlador@index');
    SimpleRouter::get("/login", 'SiteControlador@login');
    SimpleRouter::get("/registrar", 'SiteControlador@registrar');
    SimpleRouter::get("/redefinir-senha", 'SiteControlador@senha');
    SimpleRouter::get("/admin", 'SiteControlador@admin');
    SimpleRouter::get("/logout", 'SiteControlador@logout');
    SimpleRouter::get("/email", 'SiteControlador@email');
    SimpleRouter::get("/codigo-senha", 'SiteControlador@redefinicaoSenhaCodigo');
    SimpleRouter::get("/codigo-email", 'SiteControlador@registrarCodigo');
    SimpleRouter::get("/confirmar-conta", 'SiteControlador@confirmarConta');
    SimpleRouter::get("/cancelar-conta", 'SiteControlador@cancelarConta');
    SimpleRouter::get('/admin/perfil', 'SiteControlador@perfilAdmin');
    SimpleRouter::get('/admin/certificados/gerar-modelo', 'SiteControlador@modeloCertificado');
    SimpleRouter::get('/erro', 'SiteControlador@erro');
    SimpleRouter::get('/validar/{codigoVerificacao}', 'SiteControlador@validar');
    SimpleRouter::get('/admin/certificados/listar-modelos', 'SiteControlador@listarModelosCertificados');
    SimpleRouter::get('/admin/certificados/gerar/{id}', 'SiteControlador@gerarCertificados');
    SimpleRouter::get("/admin/certificados/obter-modelo/{id}", 'SiteControlador@obterModeloExistente');
    SimpleRouter::get("/admin/certificados/listar-certificados", 'SiteControlador@listarCertificados');
    SimpleRouter::get("/admin/certificados/visualizar", 'SiteControlador@visualizarCertificado');
    SimpleRouter::get("/admin/certificados/obter-dados-completos", 'SiteControlador@obterDadosCompletosCertificado');        
    SimpleRouter::get("/admin/suporte", 'SiteControlador@suporte');

    SimpleRouter::get("/certificados/obter-dados-completos-visualizar", 'SiteControlador@obterDadosCompletosCertificadoVisualizar');  

    // POST
    SimpleRouter::post("/registrar", 'SiteControlador@registrarForm');
    SimpleRouter::post("/login", 'SiteControlador@loginForm');
    SimpleRouter::post("/redefinir-senha", 'SiteControlador@senhaForm');
    SimpleRouter::post("/codigo-senha", 'SiteControlador@redefinicaoSenhaForm');
    SimpleRouter::post("/codigo-email", 'SiteControlador@registrarCodigoForm');
    SimpleRouter::post("/admin/perfil", 'SiteControlador@perfilAdminForm');
    SimpleRouter::post("/admin/certificados/salvar-modelo", 'SiteControlador@salvarModeloCertificado');
    SimpleRouter::post("/admin/certificados/salvar-certificado", 'SiteControlador@salvarCertificado');
    SimpleRouter::post("/admin/certificados/atualizar-modelo", 'SiteControlador@atualizarModeloCertificado');
    SimpleRouter::post("/admin/certificados/excluir/{id}", 'SiteControlador@excluirModeloCertificado');
    SimpleRouter::post("/admin/certificado/excluir/{id}", 'SiteControlador@excluirCertificado');
    SimpleRouter::post("/admin/suporte", 'SiteControlador@suporteForm');
        SimpleRouter::post("/admin/certificados/copiar-modelo", 'SiteControlador@copiarModelo');

    SimpleRouter::start();
} catch (NotFoundHttpException $e) {
    Helpers::redirecionar('/erro');
    // echo $e;
}
