<?php
namespace services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use controllers\Helpers;
use models\Usuario;

class EmailServico
{

    public static function enviarEmailRedefinicaoSenha(string $email, Usuario $usuario, string $link): array
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = constant('EMAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = constant('EMAIL_USER');
            $mail->Password = constant('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = constant('EMAIL_PORT');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(constant('EMAIL_USER'), constant('EMAIL_NAME'));
            $mail->addAddress($email, $usuario->getNome());

            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de Senha - ' . constant('EMAIL_NAME');

            $nome = Helpers::primeiraUltimaPalavra(Helpers::capitalizarPalavras(htmlspecialchars($usuario->getNome(), ENT_QUOTES, 'UTF-8')));
            $ano = date('Y');

            $mensagem = "
              <div style='max-width:600px; margin:auto; background:#f5f5f5; border:1px solid #ccc; border-radius:8px; overflow:hidden; font-family: monospace, sans-serif;'>
                <div style='text-align:center; background:#000000; padding:20px;'>
                            <p style='font-family: monospace; font-size: 3rem; margin: 0; color: #fff'>CertMaker</p>
                </div>
                <div style='padding:15px; padding-top: 1px; background:#fff; text-align:left;'>
                    <p style='font-size:16px; color:#333;'>Olá <strong>$nome</strong>, você solicitou a redefinição de sua senha de acesso.</p>
                    <p style='font-size:16px; color:#333;'>Para continuar, clique no botão  abaixo para ir até a página de redefinição de senha:</p>
                    <a href='$link' style='font-size:20px; color:#1c72f3; font-weight:bold; text-align:center; margin:20px 0; padding:10px; display:block; text-decoration:none; border:2px solid #1c72f3; border-radius:8px;'>Clique aqui</a>
                    <p style='font-size:14px; color:#666;'>Caso você não tenha solicitado, por favor, ignore este e-mail.</p>
                </div>
                <div style='text-align:center; padding:10px; background:#1c72f3; color:#fff; font-size:14px; font-weight:bold;'>&copy;" . constant('SITE_NOME') . " $ano</div>
            </div>";

            $mail->Body = $mensagem;
            $mail->AltBody = 'Olá, ' . $nome . ', você solicitou a redefinição de sua senha.';

            $mail->send();

            return ['status' => true, 'mensagem' => 'Email enviado com sucesso! Confirme o código recebido no seu e-mail.'];
        } catch (Exception $e) {
            return ['status' => false, 'mensagem' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo];
        }
    }

    public static function enviarEmailCriacaoConta(string $email, string $nome, string $linkConfirmar, string $linkCancelar): array
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = constant('EMAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = constant('EMAIL_USER');
            $mail->Password = constant('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = constant('EMAIL_PORT');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(constant('EMAIL_USER'), constant('EMAIL_NAME'));
            $mail->addAddress($email, $nome);

            $mail->AddEmbeddedImage(__DIR__ . '/../public' . constant('SITE_ICON_B'), 'logoimg');

            $mail->isHTML(true);
            $mail->Subject = 'Criação de Conta - ' . constant('EMAIL_NAME');

            $nome = Helpers::primeiraUltimaPalavra(Helpers::capitalizarPalavras(htmlspecialchars($nome, ENT_QUOTES, 'UTF-8')));
            $ano = date('Y');

            $mensagem = "
            <div style='max-width:600px; margin:auto; background:#f5f5f5; border:1px solid #ccc; border-radius:8px; overflow:hidden; font-family: monospace, sans-serif;'>
                <div style='text-align:center; background:#000000; padding:20px;'>
                            <p style='font-family: monospace; font-size: 3rem; margin: 0; color: #fff'>CertMaker</p>
                </div>
                <div style='padding:15px; background:#fff; text-align:left;'>
                    <p style='font-size:16px; color:#333;'>Olá <strong>$nome</strong>,</p>
                    <p style='font-size:16px; color:#333;'>Você solicitou a criação da sua conta. Para confirmar ou cancelar, utilize os botões abaixo:</p>
                    
                    <div style='text-align:center; margin:25px 0;'>
                        <a href='$linkConfirmar' style='display:inline-block; background:#28a745; color:#fff; font-size:16px; font-weight:bold; padding:12px 24px; border-radius:6px; text-decoration:none; margin-right:10px;'>Confirmar Criação</a>
                        <a href='$linkCancelar' style='display:inline-block; background:#dc3545; color:#fff; font-size:16px; font-weight:bold; padding:12px 24px; border-radius:6px; text-decoration:none;'>Cancelar Criação</a>
                    </div>
                    
                    <p style='font-size:14px; color:#666;'>Caso você não tenha solicitado, apenas ignore este e-mail.</p>
                </div>
                <div style='text-align:center; padding:10px; background:#1c72f3; color:#fff; font-size:14px; font-weight:bold;'>&copy;" . constant('SITE_NOME') . " $ano</div>
            </div>";


            $mail->Body = $mensagem;
            $mail->AltBody = 'Olá, ' . $nome . ', você solicitou a criação da sua conta.';

            $mail->send();

            return ['status' => true, 'mensagem' => 'Email enviado com sucesso! Confirme o código recebido no seu e-mail.'];
        } catch (Exception $e) {
            return ['status' => false, 'mensagem' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo];
        }
    }
public static function enviarEmailSuporte(string $assunto, string $mensagem, string $prioridade, string $tipoContato, Usuario $usuario): array
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = constant('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = constant('EMAIL_USER');
        $mail->Password = constant('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = constant('EMAIL_PORT');
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(constant('EMAIL_USER'), constant('EMAIL_NAME'));
        $mail->addAddress(constant('SITE_ADMIN_EMAIL'), 'Suporte - ' . constant('SITE_NOME'));
        
        $mail->addReplyTo($usuario->getEmail(), $usuario->getNome());

        $mail->isHTML(true);
        
        $prioridadeLabel = '';
        if ($prioridade === 'alta') {
            $prioridadeLabel = '[URGENTE] ';
            $mail->Priority = 1;
        } elseif ($prioridade === 'baixa') {
            $prioridadeLabel = '[BAIXA] ';
            $mail->Priority = 5;
        }
        
        $mail->Subject = $prioridadeLabel . 'Suporte: ' . $assunto . ' - ' . constant('SITE_NOME');

        $nomeUsuario = Helpers::primeiraUltimaPalavra(Helpers::capitalizarPalavras(htmlspecialchars($usuario->getNome(), ENT_QUOTES, 'UTF-8')));
        $emailUsuario = htmlspecialchars($usuario->getEmail(), ENT_QUOTES, 'UTF-8');
        $telefoneUsuario = htmlspecialchars($usuario->getTelefone(), ENT_QUOTES, 'UTF-8');
        $mensagemFormatada = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));
        
        $prioridades = [
            'baixa' => ['label' => 'Baixa', 'cor' => '#28a745'],
            'normal' => ['label' => 'Normal', 'cor' => '#007bff'],
            'alta' => ['label' => 'Alta', 'cor' => '#dc3545']
        ];
        
        $tiposContato = [
            'duvida' => 'Dúvida',
            'problema' => 'Problema Técnico',
            'sugestao' => 'Sugestão',
            'outro' => 'Outro',
            'geral' => 'Geral'
        ];

        $prioridadeInfo = $prioridades[$prioridade] ?? $prioridades['normal'];
        $tipoContatoInfo = $tiposContato[$tipoContato] ?? 'Geral';

        $ano = date('Y');

        $mensagemHTML = "
            <div style='max-width:600px; margin:auto; background:#f5f5f5; border:1px solid #ccc; border-radius:8px; overflow:hidden; font-family: monospace, sans-serif;'>
                <div style='text-align:center; background:#000000; padding:20px;'>
                    <p style='font-family: monospace; font-size: 3rem; margin: 0; color: #fff'>CertMaker</p>
                </div>
                
                <div style='padding:20px; background:#fff;'>
                    <h2 style='color:#333; margin-top:0;'>Nova Mensagem de Suporte</h2>
                    
                    <div style='background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px;'>
                        <h3 style='color:#333; margin-top:0;'>Informações do Usuário</h3>
                        <p style='margin:5px 0;'><strong>Nome:</strong> {$nomeUsuario}</p>
                        <p style='margin:5px 0;'><strong>Email:</strong> {$emailUsuario}</p>
                        <p style='margin:5px 0;'><strong>Telefone:</strong> {$telefoneUsuario}</p>
                    </div>
                    
                    <div style='display:flex; gap:15px; margin-bottom:20px;'>
                        <div style='flex:1; background:#f8f9fa; padding:15px; border-radius:5px;'>
                            <strong>Prioridade:</strong> 
                            <span style='color:{$prioridadeInfo['cor']}; font-weight:bold;'>{$prioridadeInfo['label']}</span>
                        </div>
                        <div style='flex:1; background:#f8f9fa; padding:15px; border-radius:5px;'>
                            <strong>Tipo:</strong> {$tipoContatoInfo}
                        </div>
                    </div>
                    
                    <div style='background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px;'>
                        <h3 style='color:#333; margin-top:0;'>Assunto</h3>
                        <p style='color:#333; margin:0;'>{$assunto}</p>
                    </div>
                    
                    <div style='background:#f8f9fa; padding:15px; border-radius:5px;'>
                        <h3 style='color:#333; margin-top:0;'>Mensagem</h3>
                        <div style='font-size:16px; color:#333; line-height:1.6;'>{$mensagemFormatada}</div>
                    </div>
                </div>
                
                <div style='text-align:center; padding:15px; background:#1c72f3; color:#fff;'>
                    <p style='margin:0; font-size:14px;'><strong>&copy; " . constant('SITE_NOME') . " {$ano}</strong></p>
                    <p style='margin:5px 0 0 0; font-size:12px;'>Esta é uma mensagem automática do sistema de suporte</p>
                </div>
            </div>";

        $mail->Body = $mensagemHTML;
        
        $mail->AltBody = "NOVA MENSAGEM DE SUPORTE\n\n" .
                        "De: {$nomeUsuario} ({$emailUsuario})\n" .
                        "Telefone: {$telefoneUsuario}\n" .
                        "Prioridade: {$prioridadeInfo['label']}\n" .
                        "Tipo: {$tipoContatoInfo}\n" .
                        "Assunto: {$assunto}\n\n" .
                        "Mensagem:\n{$mensagem}\n\n" .
                        "---\n" . constant('SITE_NOME') . " - {$ano}";

        $mail->send();

        return ['status' => true, 'mensagem' => 'Mensagem de suporte enviada com sucesso!'];
    } catch (Exception $e) {
        return ['status' => false, 'mensagem' => 'Erro ao enviar e-mail de suporte: ' . $e->getMessage()];
    }
}
}
