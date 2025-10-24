<?php

namespace controllers;

use Exception;
use Datetime;

/**
 * Class Helpers
 * @package controllers
 * Classe com métodos auxiliares para o projeto
 */

class Helpers extends Exception
{

    /**
     * Método para formatar datas.
     * @param string $date Data a ser formatada.
     * @param string $format Formato desejado.
     * @return string Data formatada.
     */

    public static function formatarDataPTBR($date): string
    {
        if (!empty($date)) {
            $dateTime = DateTime::createFromFormat('Y-m-d', $date);
            if ($dateTime !== false) {
                return $dateTime->format('d/m/Y');
            }
        }

        return '';
    }

    public static function extrairCampos(array $post, array $esperados): array|false
    {
        $dados = [];

        foreach ($esperados as $campo) {
            if (!array_key_exists($campo, $post)) {
                return false;
            }

            $dados[$campo] = is_string($post[$campo]) ? trim($post[$campo]) : '';
        }

        return $dados;
    }

    public static function extrairCamposSemObrigatoriedade(array $post, array $esperados): array
    {
        $dados = [];

        foreach ($esperados as $campo) {
            $dados[$campo] = isset($post[$campo]) && is_string($post[$campo]) ? trim($post[$campo]) : '';
        }

        return $dados;
    }

    public static function formatarDataEstrangeira(string $data): string
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $data);
        return $dateTime ? $dateTime->format('d/m/Y - H:i\h') : $data;
    }

    /**
     * Método para validar e-mail.
     * @param string $email E-mail a ser validado.
     * @return bool Retorna true se o e-mail for válido, false caso contrário.
     */
    public static function validarEmail($email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Método para gerar um token aleatório.
     * @param int $length Comprimento do token.
     * @return string Retorna o token gerado.
     */
    public static function gerarToken($length = 128): string
    {
        return mb_strtoupper(bin2hex(random_bytes($length / 2)));
    }

    /** 
     * Método para obter a URL base.
     * @return string Retorna a URL base.
     */
    public static function baseUrl(): string
    {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        return $protocolo . "://" . $_SERVER['HTTP_HOST'];
    }


    /**
     * Método para limpar uma string de caracteres indesejados.
     * @param string $string String a ser limpa.
     * @return string Retorna a string limpa.
     */
    public static function limparString($string): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $string);
    }

    /**
     * Método para verificar se uma string é JSON válido.
     * @param string $string String a ser verificada.
     * @return bool Retorna true se a string for JSON válido, false caso contrário.
     */
    public static function verificarJson($string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Método para converter um array em JSON.
     * @param array $array Array a ser convertido.
     * @return string Retorna o JSON gerado.
     */
    public static function arrayParaJson(array $array): string
    {
        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Método para converter JSON em array.
     * @param string $json JSON a ser convertido.
     * @return array Retorna o array gerado.
     */
    public static function jsonParaArray($json): array
    {
        $array = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
        }
        return $array;
    }

    /**
     * Método para gerar um UUID.
     * @return string Retorna o UUID gerado.
     */
    public static function gerarUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Método para formatar números.
     * @param float $number Número a ser formatado.
     * @param int $decimals Número de casas decimais.
     * @return string Retorna o número formatado.
     */
    public static function formatarNumero($number, $decimals = 2): string
    {
        return number_format($number, $decimals, ',', '.');
    }


    /**
     * Método para validar URLs.
     * @param string $url URL a ser validada.
     * @return bool Retorna true se a URL for válida, false caso contrário.
     */
    public static function validarURL($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Método para gerar um slug a partir de uma string.
     * @param string $string String a ser convertida em slug.
     * @return string Retorna o slug gerado.
     */
    public static function gerarSlug($string): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9-]+/', '-', strtolower(trim($string)));
        return trim($slug, '-');
    }


    /**
     * Método para verificar se uma string é um número.
     * @param mixed $value Valor a ser verificado.
     * @return bool Retorna true se o valor for um número, false caso contrário.
     */
    public static function validarNumero($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Método para verificar se uma string é um CPF válido.
     * @param string $cpf CPF a ser verificado.
     * @return bool Retorna true se o CPF for válido, false caso contrário.
     */
    public static function validarCpf($cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ($cpf[$t] != $digit) {
                return false;
            }
        }

        return true;
    }



    /**
     * Método para verificar se uma string é um CNPJ válido.
     * @param string $cnpj CNPJ a ser verificado.
     * @return bool Retorna true se o CNPJ for válido, false caso contrário.
     */
    public static function validarCnpj($cnpj): bool
    {
        return preg_match('/^\d{14}$/', $cnpj) === 1;
    }


    /**
     * Método para verificar se uma string é um telefone válido.
     * @param string $phone Telefone a ser verificado.
     * @return bool Retorna true se o telefone for válido, false caso contrário.
     */
    public static function validarTelefone($phone): bool
    {
        $phone = preg_replace('/\D/', '', $phone);

        return preg_match('/^\d{10,11}$/', $phone) === 1;
    }


    /**
     * Método para verificar se uma string é uma data válida.
     * @param string $date Data a ser verificada.
     * @return bool Retorna true se a data for válida, false caso contrário.
     */
    public static function validarData($date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }


    /**
     * Método para verificar se uma string é um horário válido.
     * @param string $time Horário a ser verificado.
     * @return bool Retorna true se o horário for válido, false caso contrário.
     */
    public static function validarHorario($time): bool
    {
        return preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }


    /**
     * Método para verificar se uma string é um CEP válido.
     * @param string $cep CEP a ser verificado.
     * @return bool Retorna true se o CEP for válido, false caso contrário.
     */
    public static function validarCep($cep): bool
    {
        return preg_match('/^\d{5}-?\d{3}$/', $cep) === 1;
    }

    /** 
     * Método para carregar arquivos CSS.
     * @param string $dir Diretório onde os arquivos CSS estão localizados.
     */

    public static function carregarCSS($dir = __DIR__ . '/../assets/css')
    {
        $baseURL = 'assets/css'; 

        foreach (glob($dir . '/*.css') as $file) {
            $filename = basename($file);
            echo "<link rel='stylesheet' href='{$baseURL}/{$filename}'>\n";
        }
    }

    /**
     * Método para carregar arquivos JavaScript.
     * @param string $dir Diretório onde os arquivos JavaScript estão localizados.
     */
    public static function carregarJS($dir = __DIR__ . '/../assets/js')
    {
        $baseURL = 'assets/js';
        foreach (glob($dir . '/*.js') as $file) {
            $filename = basename($file);
            echo "<script src='{$baseURL}/{$filename}'></script>\n";
        }
    }

    public static function ano(): int
    {
        return date('Y');
    }

    public static function redirecionar(string $url): void
    {
        echo '<script>location.href = "' . $url . '"</script>';
        exit;
    }

    public static function resumirTexto(string $texto, int $limite = 100): string
    {
        if (strlen($texto) <= $limite) {
            return $texto;
        }
        return substr($texto, 0, $limite) . '...';
    }

    public static function capitalizarPalavras(string $texto): string
    {
        if (strlen($texto) == 0) {
            return '';
        } else {
            $array_exc = ['da', 'de', 'di', 'do', 'du', 'a', 'e', 'i', 'o', 'u'];

            $texto_formatado = explode(" ", $texto);

            foreach ($texto_formatado as $key => $palavra) {
                if (!in_array($palavra, $array_exc)) {
                    $texto_formatado[$key] = ucfirst(strtolower($palavra));

                }
            }

            return mb_trim(implode(" ", $texto_formatado));
        }
    }

    public static function primeiraUltimaPalavra(string $texto): string
    {
        if (strlen($texto) == 0) {
            return '';
        } else {
            $arr_texto = explode(" ", $texto);
            $tamanho_texto = count($arr_texto);

            if ($tamanho_texto <= 1) {
                return mb_trim($texto);
            } else {
                $primeira = $arr_texto[0];
                $ultima = $arr_texto[$tamanho_texto - 1];
                return mb_trim($primeira . " " . $ultima);
            }
        }
    }

    public static function comprimirImagem(string $caminho_temp, string $caminho_destino, string $qualidade)
    {
        $info = getimagesize($caminho_temp);
        $mime = $info['mime'] ?? '';

        switch ($mime) {
            case 'image/jpeg':
            case 'image/pjpeg':
                $image = imagecreatefromjpeg($caminho_temp);
                break;
            case 'image/png':
            case 'image/x-png':
                $image = imagecreatefrompng($caminho_temp);
                break;
            default:
                return null;
        }

        imagejpeg($image, $caminho_destino, $qualidade);
        imagedestroy($image);

        return $caminho_destino;
    }

    public static function reduzirTamanhoImagem(string $origem, string $destino, float $maxLargura, float $maxAltura)
    {
        if (empty($origem) || !file_exists($origem)) {
            return false;
        }

        list($larguraOriginal, $alturaOriginal, $tipoImagem) = getimagesize($origem);

        $proporcao = min($maxLargura / $larguraOriginal, $maxAltura / $alturaOriginal);
        $novaLargura = intval($larguraOriginal * $proporcao);
        $novaAltura = intval($alturaOriginal * $proporcao);

        $novaImagem = imagecreatetruecolor($novaLargura, $novaAltura);

        switch ($tipoImagem) {
            case IMAGETYPE_JPEG:
                $imagemOriginal = imagecreatefromjpeg($origem);
                break;
            case IMAGETYPE_PNG:
                $imagemOriginal = imagecreatefrompng($origem);
                imagealphablending($novaImagem, false);
                imagesavealpha($novaImagem, true);
                break;
            default:
                return false;
        }

        imagecopyresampled($novaImagem, $imagemOriginal, 0, 0, 0, 0, $novaLargura, $novaAltura, $larguraOriginal, $alturaOriginal);
        imagejpeg($novaImagem, $destino, 50);

        imagedestroy($imagemOriginal);
        imagedestroy($novaImagem);

        return $destino;
    }

    public static function decryptData(string $encrypted_data)
    {
        $iv = substr(hash('sha256', constant("ENCRYPT_KEY")), 0, 16);
        $decoded_url = urldecode($encrypted_data);
        $decoded_base64 = base64_decode($decoded_url);
        $decrypted_data = openssl_decrypt($decoded_base64, 'aes-256-cbc', constant("ENCRYPT_KEY"), 0, $iv);

        return $decrypted_data;
    }

    public static function encryptData(string $data)
    {
        $iv = substr(hash('sha256', constant("ENCRYPT_KEY")), 0, 16);

        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', constant("ENCRYPT_KEY"), 0, $iv);

        $encoded_data = urlencode(base64_encode($encrypted_data));

        return $encoded_data;
    }

    public static function getBaseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . $host;
        
        return $base_url;
    }

    public static function getBaseUrlAlt()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        
        $script_path = dirname($_SERVER['SCRIPT_NAME']);
        $base_url = $protocol . $host . ($script_path !== '/' ? $script_path : '');
        
        return rtrim($base_url, '/');
    }
}
