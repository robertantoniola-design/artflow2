<?php
/**
 * Testes Unitários do Model Arte
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Arte;

class ArteTest extends TestCase
{
    /**
     * Testa criação de Arte a partir de array
     */
    public function testCriarArteDeArray(): void
    {
        $data = [
            'id' => 1,
            'nome' => 'Teste Arte',
            'descricao' => 'Uma descrição de teste',
            'tempo_medio_horas' => 10.5,
            'complexidade' => 'media',
            'preco_custo' => 150.00,
            'horas_trabalhadas' => 8.0,
            'status' => 'disponivel'
        ];
        
        $arte = Arte::fromArray($data);
        
        $this->assertEquals(1, $arte->getId());
        $this->assertEquals('Teste Arte', $arte->getNome());
        $this->assertEquals('media', $arte->getComplexidade());
        $this->assertEquals(150.00, $arte->getPrecoCusto());
        $this->assertEquals('disponivel', $arte->getStatus());
    }
    
    /**
     * Testa conversão de Arte para array
     */
    public function testConverterArteParaArray(): void
    {
        $data = [
            'nome' => 'Arte Teste',
            'complexidade' => 'alta',
            'preco_custo' => 200.00,
            'status' => 'em_producao'
        ];
        
        $arte = Arte::fromArray($data);
        $array = $arte->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('Arte Teste', $array['nome']);
        $this->assertEquals('alta', $array['complexidade']);
    }
    
    /**
     * Testa status válidos
     */
    public function testStatusValidos(): void
    {
        $arte = Arte::fromArray([
            'nome' => 'Arte',
            'status' => 'disponivel'
        ]);
        
        $this->assertEquals('disponivel', $arte->getStatus());
        
        $arte->setStatus('em_producao');
        $this->assertEquals('em_producao', $arte->getStatus());
        
        $arte->setStatus('vendida');
        $this->assertEquals('vendida', $arte->getStatus());
    }
    
    /**
     * Testa exceção para status inválido
     */
    public function testStatusInvalidoLancaExcecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $arte = Arte::fromArray(['nome' => 'Arte']);
        $arte->setStatus('invalido');
    }
}
