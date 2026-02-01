<?php

namespace App\Core;

use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * ============================================
 * CONTAINER - Dependency Injection Container
 * ============================================
 * 
 * O Container resolve automaticamente as dependências
 * das classes, eliminando a necessidade de criar
 * objetos manualmente com "new Classe()".
 * 
 * BENEFÍCIOS:
 * - Desacoplamento: classes não criam suas dependências
 * - Testabilidade: fácil substituir por mocks em testes
 * - Flexibilidade: troca implementações facilmente
 * 
 * EXEMPLO:
 * // Registra binding
 * $container->bind(ArteRepository::class);
 * 
 * // Resolve automaticamente
 * $repo = $container->make(ArteRepository::class);
 */
class Container
{
    /**
     * Bindings registrados
     * [NomeClasse => ['concrete' => Closure|string, 'singleton' => bool]]
     */
    private array $bindings = [];
    
    /**
     * Instâncias singleton já criadas
     */
    private array $instances = [];
    
    /**
     * Registra um binding no container
     * 
     * @param string $abstract Nome da classe/interface
     * @param mixed $concrete Implementação (Closure ou nome da classe)
     * @param bool $singleton Se true, mesma instância sempre
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        // Se não passou concrete, usa o próprio abstract
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }
    
    /**
     * Registra um singleton (mesma instância sempre)
     * 
     * @param string $abstract
     * @param mixed $concrete
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Registra uma instância já criada
     * 
     * @param string $abstract
     * @param object $instance
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
    
    /**
     * Resolve uma dependência
     * 
     * @param string $abstract Nome da classe/interface
     * @return object
     * @throws Exception
     */
    public function make(string $abstract): object
    {
        // 1. Verifica se já existe instância (singleton)
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // 2. Verifica se tem binding registrado
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];
            $singleton = $binding['singleton'];
            
            // Se é Closure, executa passando o container
            if ($concrete instanceof \Closure) {
                $object = $concrete($this);
            } else {
                // Se é string (nome de classe), resolve
                $object = $this->resolve($concrete);
            }
            
            // Se é singleton, guarda instância
            if ($singleton) {
                $this->instances[$abstract] = $object;
            }
            
            return $object;
        }
        
        // 3. Tenta resolver a classe diretamente via Reflection
        return $this->resolve($abstract);
    }
    
    /**
     * Resolve uma classe usando Reflection
     * 
     * Reflection permite "inspecionar" classes em tempo de execução,
     * descobrindo construtores, parâmetros, tipos, etc.
     * 
     * @param string $class
     * @return object
     * @throws Exception
     */
    private function resolve(string $class): object
    {
        // Verifica se a classe existe
        if (!class_exists($class)) {
            throw new Exception("Classe '{$class}' não encontrada");
        }
        
        // Usa Reflection para analisar a classe
        $reflector = new ReflectionClass($class);
        
        // Verifica se pode ser instanciada (não é abstract/interface)
        if (!$reflector->isInstantiable()) {
            throw new Exception("Classe '{$class}' não pode ser instanciada");
        }
        
        // Obtém o construtor
        $constructor = $reflector->getConstructor();
        
        // Se não tem construtor, instancia direto
        if ($constructor === null) {
            return new $class;
        }
        
        // Obtém parâmetros do construtor
        $parameters = $constructor->getParameters();
        
        // Resolve cada dependência
        $dependencies = $this->resolveDependencies($parameters);
        
        // Cria instância com as dependências
        return $reflector->newInstanceArgs($dependencies);
    }
    
    /**
     * Resolve dependências de um array de parâmetros
     * 
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws Exception
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            $paramName = $parameter->getName();
            
            // Se não tem tipo ou é tipo primitivo (int, string, etc)
            if ($type === null || $type->isBuiltin()) {
                // Tenta usar valor padrão
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception(
                        "Não é possível resolver parâmetro '{$paramName}' " .
                        "sem tipo definido ou valor padrão"
                    );
                }
            } else {
                // Tipo é uma classe, resolve recursivamente
                $typeName = $type->getName();
                $dependencies[] = $this->make($typeName);
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Verifica se existe binding para uma classe
     * 
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * Remove um binding/instância
     * 
     * @param string $abstract
     */
    public function forget(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }
    
    /**
     * Limpa todos os bindings e instâncias
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }
}
