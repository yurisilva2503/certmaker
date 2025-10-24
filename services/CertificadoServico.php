<?php

namespace services;

use models\Certificado;
use PDO;
use PDOException;
use models\ModeloCertificado;
use config\database as db;
use controllers\Helpers;

class CertificadoServico
{
    private $table = 'modelos_certificado';
    private $tableCertificados = 'certificados';

    public function criar(int $id_usuario, string $nome, string $orientacao, string $elementos, string $paginas = null, string $background = null): array
    {
        try {
            $certificado = new ModeloCertificado(null, $id_usuario, $nome, $orientacao, $elementos, $paginas, $background, date('Y-m-d H:i:s'), null);

            $sql = "INSERT INTO {$this->table} (id_usuario, nome, orientacao, elementos, paginas, background, criado_em, atualizado_em)
                    VALUES (:id_usuario, :nome, :orientacao, :elementos, :paginas, :background, :criado_em, :atualizado_em)";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $certificado->getIdUsuario(),
                ':nome' => $certificado->getNome(),
                ':orientacao' => $certificado->getOrientacao(),
                ':elementos' => $certificado->getElementosAsJson(),
                ':paginas' => $certificado->getPaginasAsJson(),
                ':background' => $certificado->getBackground(),
                ':criado_em' => $certificado->getCriadoEm(),
                ':atualizado_em' => $certificado->getAtualizadoEm()
            ]);

            return ['status' => true, 'mensagem' => 'Certificado criado com sucesso!'];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao criar certificado: " . $e->getMessage()];
        }
    }

   public function salvarCertificadoGerado(array $data, int $id_gerador): array
{
    try {
        $sql = "INSERT INTO certificados 
                (codigo_verificacao, id_modelo, nome_aluno, cpf_aluno, curso, 
                 carga_horaria, data_emissao, data_validade, url_qr_code, campos_personalizados, id_gerador) 
                VALUES 
                (:codigo_verificacao, :id_modelo, :nome_aluno, :cpf_aluno, :curso, 
                 :carga_horaria, NOW() , :data_validade, :url_qr_code, :campos_personalizados, :id_gerador)";
        
        $stmt = db::getInstancia()->prepare($sql);

        $data['id_modelo'] = Helpers::decryptData($data['id_modelo']);
        
        $camposPersonalizados = isset($data['campos_personalizados']) 
            ? (is_array($data['campos_personalizados']) 
                ? json_encode($data['campos_personalizados'], JSON_UNESCAPED_UNICODE)
                : $data['campos_personalizados'])
            : json_encode([], JSON_UNESCAPED_UNICODE);

        $stmt->bindParam(':codigo_verificacao', $data['codigo_verificacao']);
        $stmt->bindParam(':id_modelo', $data['id_modelo']);
        $stmt->bindParam(':nome_aluno', $data['nome_aluno']);
        $stmt->bindParam(':cpf_aluno', $data['cpf_aluno']);
        $stmt->bindParam(':curso', $data['curso']);
        $stmt->bindParam(':carga_horaria', $data['carga_horaria']);
        $stmt->bindParam(':data_validade', $data['data_validade']);
        $stmt->bindParam(':url_qr_code', $data['url_qr_code']);
        $stmt->bindParam(':campos_personalizados', $camposPersonalizados);
        $stmt->bindParam(':id_gerador', $id_gerador);
        
        if ($stmt->execute()) {
            return [
                'status' => true,
                'mensagem' => 'Certificado gerado e salvo com sucesso!',
                'id' => db::getInstancia()->lastInsertId(),
                'codigo_verificacao' => $data['codigo_verificacao']
            ];
        } else {
            return [
                'status' => false,
                'mensagem' => 'Erro ao salvar certificado no banco de dados'
            ];
        }
    } catch (PDOException $e) {
        return [
            'status' => false,
            'mensagem' => "Erro ao salvar certificado: " . $e->getMessage()
        ];
    }
}
public function buscarCertificadoPorCodigo(string $codigoVerificacao): ?array
{
    try {
        $sql = "SELECT *, 
                JSON_UNQUOTE(JSON_EXTRACT(campos_personalizados, '$')) as campos_personalizados 
                FROM certificados 
                WHERE codigo_verificacao = :codigo";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->bindParam(':codigo', $codigoVerificacao);
        $stmt->execute();
        
        $certificado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($certificado) {
            $certificado['campos_personalizados'] = json_decode(
                $certificado['campos_personalizados'], 
                true
            );

            return ['status' => true, 'certificado' => $certificado];
        }
        
        return ['status' => false, 'mensagem' => 'Certificado não encontrado'];
        
    } catch (PDOException $e) {
        return null;
    }
}

    public function buscarPorId(int $id): array
{
    try {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return ['status' => false, 'mensagem' => 'Certificado não encontrado!'];
        }

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        $certificado = new ModeloCertificado(
            Helpers::encryptData($dados['id']),
            $dados['id_usuario'],
            $dados['nome'],
            $dados['orientacao'],
            $dados['elementos'],
            $dados['paginas'],  
            $dados['background'],
            $dados['criado_em'],
            $dados['atualizado_em']
        );
        
        $certificado->syncElementosToPaginas();

        return ['status' => true, 'mensagem' => 'Certificado encontrado!', 'certificado' => $certificado];

    } catch (PDOException $e) {
        return ['status' => false, 'mensagem' => "Erro ao buscar certificado: " . $e->getMessage()];
    }
}

    public function buscarPorIdUsuario(int $id_usuario): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id_usuario = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Certificados não encontrados!'];
            }

            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $certificados = [];
            foreach ($dados as $certificado) {
                $certificados[] = new ModeloCertificado(
                    Helpers::encryptData($certificado['id']),
                    $certificado['id_usuario'],
                    $certificado['nome'],
                    $certificado['orientacao'],
                    $certificado['elementos'],
                    $certificado['paginas'],
                    $certificado['background'],
                    $certificado['criado_em'],
                    $certificado['atualizado_em']
                );
            }
            
            return ['status' => true, 'mensagem' => 'Certificado encontrado!', 'certificados' => $certificados];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao buscar certificado: " . $e->getMessage()];
        }
    }

    public function buscarCertificadosPorIdUsuario(int $id_usuario): array
    {
        try {
            $sql = "SELECT * FROM {$this->tableCertificados} WHERE id_gerador = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Certificados não encontrados!', 'certificados' => []];
            }

            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $certificados = [];
            foreach ($dados as $certificado) {
                $certificados[] = new Certificado(
                    Helpers::encryptData($certificado['id']),
                    $certificado['codigo_verificacao'],
                    Helpers::encryptData($certificado['id_modelo']),
                    $certificado['nome_aluno'],
                    $certificado['cpf_aluno'],
                    $certificado['curso'],
                    $certificado['carga_horaria'],
                    $certificado['data_emissao'],
                    $certificado['data_validade'],
                    $certificado['url_qr_code'],
                    $certificado['caminho_pdf'],
                    $certificado['campos_personalizados'],
                    $certificado['status'],
                    $certificado['criado_em'],
                    $certificado['atualizado_em'],
                    Helpers::encryptData($certificado['id_gerador']),
                );
            }

            return ['status' => true, 'mensagem' => 'Certificado encontrado!', 'certificados' => $certificados];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao buscar certificado: " . $e->getMessage()];
        }
    }

    public function buscarCertificadosPorId(int $id): array
    {
        try {
            $sql = "SELECT * FROM {$this->tableCertificados} WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Certificados não encontrados!', 'certificados' => []];
            }

            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $certificados = [];
            foreach ($dados as $certificado) {
                $certificados[] = new Certificado(
                    Helpers::encryptData($certificado['id']),
                    $certificado['codigo_verificacao'],
                    $certificado['id_modelo'],
                    $certificado['nome_aluno'],
                    $certificado['cpf_aluno'],
                    $certificado['curso'],
                    $certificado['carga_horaria'],
                    $certificado['data_emissao'],
                    $certificado['data_validade'],
                    $certificado['url_qr_code'],
                    $certificado['caminho_pdf'],
                    $certificado['campos_personalizados'],
                    $certificado['status'],
                    $certificado['criado_em'],
                    $certificado['atualizado_em'],
                    $certificado['id_gerador']
                );
            }

            return ['status' => true, 'mensagem' => 'Certificado encontrado!', 'certificados' => $certificados];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao buscar certificado: " . $e->getMessage()];
        }
    }
    

    public function buscarModelosParaDatatables(int $id_usuario): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id_usuario = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Certificados não encontrados!', 'certificados' => []];
            }

            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $certificados = [];
            foreach ($dados as $certificado) {
                $certificados[] = new ModeloCertificado(
                    Helpers::encryptData($certificado['id']),
                    $certificado['id_usuario'],
                    $certificado['nome'],
                    $certificado['orientacao'],
                    $certificado['elementos'],
                    $certificado['paginas'],
                    $certificado['background'],
                    Helpers::formatarDataEstrangeira($certificado['criado_em']),
                    $certificado['atualizado_em'] ? Helpers::formatarDataEstrangeira($certificado['atualizado_em']) : null
                );
            }

            return ['status' => true, 'mensagem' => 'Certificado encontrado!', 'certificados' => $certificados];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao buscar certificado: " . $e->getMessage()];
        }
    }

    public function listarTodos(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $stmt = db::getInstancia()->query($sql);

            $certificados = [];
            while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $certificados[] = new ModeloCertificado(
                    Helpers::encryptData($dados['id']),
                    $dados['id_usuario'],
                    $dados['nome'],
                    $dados['orientacao'],
                    $dados['elementos'],
                    $dados['paginas'],
                    $dados['background'],
                    $dados['criado_em'],
                    $dados['atualizado_em']
                );
            }

            return ['status' => true, 'mensagem' => 'Certificados listados!', 'certificados' => $certificados];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao listar certificados: " . $e->getMessage()];
        }
    }

    public function atualizarCertificado(ModeloCertificado $certificado): array
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET nome = :nome, orientacao = :orientacao, elementos = :elementos, paginas = :paginas, background = :background, atualizado_em = :atualizado_em
                    WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([
                ':id' => Helpers::decryptData($certificado->getId()),
                ':nome' => $certificado->getNome(),
                ':orientacao' => $certificado->getOrientacao(),
                ':elementos' => $certificado->getElementosAsJson(),
                ':paginas' => $certificado->getPaginasAsJson(),
                ':background' => $certificado->getBackground(),
                ':atualizado_em' => date('Y-m-d H:i:s')
            ]);

            return ['status' => true, 'mensagem' => 'Certificado atualizado com sucesso!', 'certificado' => $certificado];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao atualizar certificado: " . $e->getMessage()];
        }
    }

    public function removerModeloCertificado(string $id): array
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => Helpers::decryptData($id)]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Certificado não encontrado!'];
            }

            return ['status' => true, 'mensagem' => 'Certificado deletado com sucesso!'];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao deletar certificado: " . $e->getMessage()];
        }
    }

    public function removerCertificado(string $id): array
    {
        try {
            $sql = "DELETE FROM {$this->tableCertificados} WHERE id = :id";
            $stmt = db::getInstancia()->prepare($sql);
            $stmt->execute([':id' => Helpers::decryptData($id)]);

            if ($stmt->rowCount() === 0) {
                return ['status' => false, 'mensagem' => 'Certificado não encontrado!'];
            }

            return ['status' => true, 'mensagem' => 'Certificado deletado com sucesso!'];

        } catch (PDOException $e) {
            return ['status' => false, 'mensagem' => "Erro ao deletar certificado: " . $e->getMessage()];
        }
    }


public function copiarModelo(int $id_modelo, string $novo_nome, int $id_usuario): array
{
    try {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id' => $id_modelo]);
        $modelo_original = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$modelo_original) {
            return ['status' => false, 'mensagem' => 'Modelo não encontrado!'];
        }

        if ($modelo_original['id_usuario'] != $id_usuario) {
            return ['status' => false, 'mensagem' => 'Você não tem permissão para copiar este modelo!'];
        }

        $sql = "INSERT INTO {$this->table} (id_usuario, nome, orientacao, elementos, paginas, background, criado_em, atualizado_em)
                VALUES (:id_usuario, :nome, :orientacao, :elementos, :paginas, :background, :criado_em, :atualizado_em)";
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':nome' => $novo_nome,
            ':orientacao' => $modelo_original['orientacao'],
            ':elementos' => $modelo_original['elementos'],
            ':paginas' => $modelo_original['paginas'],
            ':background' => $modelo_original['background'],
            ':criado_em' => date('Y-m-d H:i:s'),
            ':atualizado_em' => null
        ]);

        $id_novo_modelo = db::getInstancia()->lastInsertId();

        return [
            'status' => true, 
            'mensagem' => 'Modelo copiado com sucesso!', 
            'id_novo_modelo' => Helpers::encryptData($id_novo_modelo)
        ];

    } catch (PDOException $e) {
        return ['status' => false, 'mensagem' => "Erro ao copiar modelo: " . $e->getMessage()];
    }
}
public function buscarDadosDashboard(int $id_usuario): array
{
    return [
        'resumo' => $this->buscarResumo($id_usuario),
        'evolucaoMensal' => $this->buscarEvolucaoMensal($id_usuario),
        'distribuicaoCursos' => $this->buscarDistribuicaoCursos($id_usuario),
        'modelosMaisUsados' => $this->buscarModelosMaisUsados($id_usuario),
        'atividadeRecente' => $this->buscarAtividadeRecente($id_usuario),
        'certificadosPorStatus' => $this->buscarCertificadosPorStatus($id_usuario)
    ];
}

private function buscarResumo(int $id_usuario): array
{
    try {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM certificados WHERE id_gerador = :id_usuario) as total_certificados,
                (SELECT COUNT(*) FROM modelos_certificado WHERE id_usuario = :id_usuario) as total_modelos,
                (SELECT COUNT(*) FROM certificados WHERE id_gerador = :id_usuario AND DATE(criado_em) = CURDATE()) as certificados_hoje,
                (SELECT COUNT(*) FROM certificados WHERE id_gerador = :id_usuario AND MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())) as certificados_mes";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}

private function buscarEvolucaoMensal(int $id_usuario): array
{
    try {
        $sql = "SELECT 
                DATE_FORMAT(criado_em, '%Y-%m') as mes,
                COUNT(*) as total
                FROM certificados 
                WHERE id_gerador = :id_usuario 
                AND criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(criado_em, '%Y-%m')
                ORDER BY mes";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $valores = [];
        
        foreach ($dados as $item) {
            $labels[] = date('M/Y', strtotime($item['mes'] . '-01'));
            $valores[] = (int)$item['total'];
        }
        
        return ['labels' => $labels, 'data' => $valores];
        
    } catch (PDOException $e) {
        return ['labels' => [], 'data' => []];
    }
}

private function buscarDistribuicaoCursos(int $id_usuario): array
{
    try {
        $sql = "SELECT 
                curso,
                COUNT(*) as total
                FROM certificados 
                WHERE id_gerador = :id_usuario 
                AND curso IS NOT NULL AND curso != ''
                GROUP BY curso
                ORDER BY total DESC
                LIMIT 10";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $valores = [];
        
        foreach ($dados as $item) {
            $labels[] = $item['curso'];
            $valores[] = (int)$item['total'];
        }
        
        return ['labels' => $labels, 'data' => $valores];
        
    } catch (PDOException $e) {
        return ['labels' => [], 'data' => []];
    }
}

private function buscarModelosMaisUsados(int $id_usuario): array
{
    try {
        $sql = "SELECT 
                m.nome as modelo,
                COUNT(c.id) as total
                FROM modelos_certificado m
                LEFT JOIN certificados c ON m.id = c.id_modelo
                WHERE m.id_usuario = :id_usuario
                GROUP BY m.id, m.nome
                ORDER BY total DESC
                LIMIT 8";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $valores = [];
        
        foreach ($dados as $item) {
            $labels[] = $item['modelo'] ?: 'Sem nome';
            $valores[] = (int)$item['total'];
        }
        
        return ['labels' => $labels, 'data' => $valores];
        
    } catch (PDOException $e) {
        return ['labels' => [], 'data' => []];
    }
}

private function buscarAtividadeRecente(int $id_usuario): array
{
    try {
        $sql = "SELECT 
                nome_aluno,
                curso,
                data_emissao,
                codigo_verificacao
                FROM certificados 
                WHERE id_gerador = :id_usuario
                ORDER BY criado_em DESC 
                LIMIT 8";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}

private function buscarCertificadosPorStatus(int $id_usuario): array
{
    try {
        $sql = "SELECT 
                status,
                COUNT(*) as total
                FROM certificados 
                WHERE id_gerador = :id_usuario
                GROUP BY status";
        
        $stmt = db::getInstancia()->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $valores = [];
        $cores = [];
        
        foreach ($dados as $item) {
            $labels[] = ucfirst($item['status']);
            $valores[] = (int)$item['total'];
            $cores[] = $item['status'] == 'ativo' ? '#28a745' : '#dc3545';
        }
        
        return ['labels' => $labels, 'data' => $valores, 'colors' => $cores];
        
    } catch (PDOException $e) {
        return ['labels' => [], 'data' => [], 'colors' => []];
    }
}
}