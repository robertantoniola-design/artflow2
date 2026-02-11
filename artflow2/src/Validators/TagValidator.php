<?php

namespace App\Validators;

/**
 * ============================================
 * TAG VALIDATOR (Melhoria 3 — + Descrição e Ícone)
 * ============================================
 * 
 * Valida dados de entrada para criação/edição de tags.
 * Usa $this->data que é preenchido pelo BaseValidator.
 * 
 * ALTERAÇÕES Melhoria 3:
 * - [07/02/2026] Validação de descricao (opcional, max 500 chars)
 * - [07/02/2026] Validação de icone (opcional, formato classe CSS Bootstrap Icons)
 * - [07/02/2026] getIconesDisponiveis() — lista de ícones para seletor na UI
 */
class TagValidator extends BaseValidator
{
    /**
     * Implementa validação específica de Tag
     * Chamado pelo BaseValidator::isValid() / validate()
     */
    protected function doValidation(): void
    {
        // ==========================================
        // NOME (obrigatório)
        // ==========================================
        $this->required('nome', 'O nome da tag é obrigatório');
        
        if (!empty($this->data['nome'])) {
            $this->minLength('nome', 2, 'O nome deve ter pelo menos 2 caracteres');
            $this->maxLength('nome', 50, 'O nome deve ter no máximo 50 caracteres');
            
            if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', $this->data['nome'])) {
                $this->addError('nome', 'O nome deve conter apenas letras, números, espaços e hífens');
            }
        }
        
        // ==========================================
        // COR (opcional, mas valida formato se fornecida)
        // ==========================================
        if (!empty($this->data['cor'])) {
            $this->validateCorHex($this->data['cor']);
        }
        
        // ==========================================
        // MELHORIA 3: DESCRIÇÃO (opcional, max 500 chars)
        // ==========================================
        if (!empty($this->data['descricao'])) {
            if (mb_strlen($this->data['descricao']) > 500) {
                $this->addError('descricao', 'A descrição deve ter no máximo 500 caracteres');
            }
        }
        
        // ==========================================
        // MELHORIA 3: ÍCONE (opcional, valida formato classe CSS)
        // ==========================================
        if (!empty($this->data['icone'])) {
            $this->validateIconeClass($this->data['icone']);
        }
    }
    
    /**
     * Valida formato de cor hexadecimal
     */
    private function validateCorHex(string $cor): void
    {
        if (strpos($cor, '#') !== 0) {
            $cor = '#' . $cor;
        }
        
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $cor)) {
            $this->addError('cor', 'Cor inválida. Use formato hexadecimal (#RRGGBB ou #RGB)');
        }
    }
    
    /**
     * MELHORIA 3: Valida formato de classe de ícone Bootstrap Icons
     * 
     * Aceita formato: "bi bi-nome-do-icone" ou "bi-nome-do-icone"
     * Exemplos válidos: "bi bi-palette", "bi bi-brush", "bi-star-fill"
     * Rejeita: strings com <, >, ", ' (proteção XSS)
     * 
     * @param string $icone Classe CSS do ícone
     */
    private function validateIconeClass(string $icone): void
    {
        // Proteção XSS: rejeita caracteres perigosos em atributo class
        if (preg_match('/[<>"\'&;]/', $icone)) {
            $this->addError('icone', 'O ícone contém caracteres inválidos');
            return;
        }
        
        // Valida formato: letras, números, hífens e espaços (para "bi bi-xxx")
        // Máximo 100 chars para evitar abuso
        if (!preg_match('/^[a-zA-Z0-9\s\-]{1,100}$/', $icone)) {
            $this->addError('icone', 'Formato de ícone inválido. Use classes Bootstrap Icons (ex: bi bi-palette)');
        }
    }
    
    /**
     * Valida dados para criação
     */
    public function validateCreate(array $data): bool
    {
        return $this->isValid($data);
    }
    
    /**
     * Valida dados para atualização (campos opcionais)
     */
    public function validateUpdate(array $data): bool
    {
        $this->data = $data;
        $this->errors = [];
        
        // Nome: valida apenas se fornecido
        if (isset($this->data['nome'])) {
            if (empty($this->data['nome'])) {
                $this->addError('nome', 'O nome não pode ficar vazio');
            } else {
                $this->minLength('nome', 2, 'O nome deve ter pelo menos 2 caracteres');
                $this->maxLength('nome', 50, 'O nome deve ter no máximo 50 caracteres');
                
                if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', $this->data['nome'])) {
                    $this->addError('nome', 'O nome deve conter apenas letras, números, espaços e hífens');
                }
            }
        }
        
        // Cor: valida apenas se fornecida
        if (isset($this->data['cor']) && !empty($this->data['cor'])) {
            $this->validateCorHex($this->data['cor']);
        }
        
        // MELHORIA 3: Descrição — valida apenas se fornecida
        if (isset($this->data['descricao']) && !empty($this->data['descricao'])) {
            if (mb_strlen($this->data['descricao']) > 500) {
                $this->addError('descricao', 'A descrição deve ter no máximo 500 caracteres');
            }
        }
        
        // MELHORIA 3: Ícone — valida apenas se fornecido
        if (isset($this->data['icone']) && !empty($this->data['icone'])) {
            $this->validateIconeClass($this->data['icone']);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Normaliza cor para formato padrão (#RRGGBB)
     */
    public static function normalizeCor(string $cor): string
    {
        $cor = ltrim($cor, '#');
        
        // Expande #RGB para #RRGGBB
        if (strlen($cor) === 3) {
            $cor = $cor[0] . $cor[0] . $cor[1] . $cor[1] . $cor[2] . $cor[2];
        }
        
        return '#' . strtoupper($cor);
    }
    
    /**
     * Retorna paleta de cores predefinidas para seletor
     */
    public static function getCoresPredefinidas(): array
    {
        return [
            '#dc3545' => 'Vermelho',
            '#fd7e14' => 'Laranja',
            '#ffc107' => 'Amarelo',
            '#28a745' => 'Verde',
            '#20c997' => 'Teal',
            '#17a2b8' => 'Ciano',
            '#007bff' => 'Azul',
            '#6610f2' => 'Índigo',
            '#6f42c1' => 'Roxo',
            '#e83e8c' => 'Rosa',
            '#6c757d' => 'Cinza',
            '#343a40' => 'Escuro',
        ];
    }
    
    /**
     * MELHORIA 3: Retorna lista de ícones Bootstrap Icons disponíveis
     * 
     * Seleção curada dos ícones mais relevantes para um sistema de arte.
     * Formato: 'classe_completa' => 'Nome amigável'
     * 
     * Referência completa: https://icons.getbootstrap.com/
     * 
     * @return array
     */
    public static function getIconesDisponiveis(): array
    {
        return [
            // Arte e Criatividade
            'bi bi-palette'         => 'Paleta',
            'bi bi-brush'           => 'Pincel',
            'bi bi-pencil'          => 'Lápis',
            'bi bi-pen'             => 'Caneta',
            'bi bi-vector-pen'      => 'Caneta Vetorial',
            'bi bi-easel'           => 'Cavalete',
            'bi bi-image'           => 'Imagem',
            'bi bi-images'          => 'Imagens',
            'bi bi-camera'          => 'Câmera',
            
            // Categorias / Tipos
            'bi bi-tag'             => 'Tag',
            'bi bi-tags'            => 'Tags',
            'bi bi-bookmark'        => 'Marcador',
            'bi bi-star'            => 'Estrela',
            'bi bi-star-fill'       => 'Estrela Cheia',
            'bi bi-heart'           => 'Coração',
            'bi bi-heart-fill'      => 'Coração Cheio',
            'bi bi-award'           => 'Prêmio',
            'bi bi-trophy'          => 'Troféu',
            
            // Natureza / Paisagem
            'bi bi-tree'            => 'Árvore',
            'bi bi-flower1'         => 'Flor 1',
            'bi bi-flower2'         => 'Flor 2',
            'bi bi-sun'             => 'Sol',
            'bi bi-moon'            => 'Lua',
            'bi bi-water'           => 'Água',
            'bi bi-snow'            => 'Neve',
            
            // Objetos / Elementos
            'bi bi-gem'             => 'Gema',
            'bi bi-lightning'       => 'Raio',
            'bi bi-fire'            => 'Fogo',
            'bi bi-droplet'         => 'Gota',
            'bi bi-circle-fill'     => 'Círculo',
            'bi bi-square-fill'     => 'Quadrado',
            'bi bi-triangle-fill'   => 'Triângulo',
            'bi bi-diamond-fill'    => 'Diamante',
            
            // Negócios / Vendas
            'bi bi-cart'            => 'Carrinho',
            'bi bi-bag'             => 'Sacola',
            'bi bi-currency-dollar' => 'Dólar',
            'bi bi-cash'            => 'Dinheiro',
            'bi bi-gift'            => 'Presente',
            'bi bi-box'             => 'Caixa',
            'bi bi-shop'            => 'Loja',
            
            // Pessoas
            'bi bi-person'          => 'Pessoa',
            'bi bi-people'          => 'Pessoas',
            'bi bi-person-heart'    => 'Favorito',
            'bi bi-emoji-smile'     => 'Sorriso',
            
            // Status / Indicadores
            'bi bi-check-circle'    => 'Aprovado',
            'bi bi-exclamation-triangle' => 'Alerta',
            'bi bi-clock'           => 'Relógio',
            'bi bi-flag'            => 'Bandeira',
            'bi bi-pin-map'         => 'Localização',
        ];
    }
}
