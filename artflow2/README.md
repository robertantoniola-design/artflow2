# 🧪 ArtFlow 2.0 - Sistema de Testes e Diagnóstico

Sistema completo de testes para verificar a integridade do ArtFlow 2.0.

## 📋 Funcionalidades

O sistema testa 7 áreas principais:

| Categoria | O que testa |
|-----------|-------------|
| **Ambiente** | PHP, extensões, diretórios, arquivos de configuração |
| **Banco de Dados** | Conexão, tabelas, estrutura, integridade referencial |
| **Rotas** | Todas as URLs do sistema (GET/POST) |
| **Segurança** | CSRF, sessão, arquivos sensíveis, XSS |
| **Módulos** | Controllers, Services, Repositories, Models |
| **Views** | Existência de todos os arquivos de view |
| **Helpers** | Funções auxiliares (url, money, date_br, etc) |

---

## 🚀 Instalação

### Opção 1: Arquivo Standalone (Recomendado)

Basta copiar o arquivo `tests.php` para a raiz do projeto:

```batch
copy tests.php C:\xampp\htdocs\artflow2\
```

Acesse: **http://localhost/artflow2/tests.php**

### Opção 2: Integração Completa

1. **Copie os arquivos:**

```batch
cd C:\xampp\htdocs\artflow2

REM TestService
copy "artflow2_testes\src\Services\TestService.php" "src\Services\"

REM TestController
copy "artflow2_testes\src\Controllers\TestController.php" "src\Controllers\"

REM View de testes
mkdir views\testes
copy "artflow2_testes\views\testes\index.php" "views\testes\"
```

2. **Adicione as rotas ao `config/routes.php`:**

```php
// No final do arquivo config/routes.php, adicione:

use App\Controllers\TestController;

// Rotas de Testes (REMOVER EM PRODUÇÃO!)
$router->get('/testes', [TestController::class, 'index']);
$router->get('/testes/api', [TestController::class, 'api']);
```

3. **Acesse:** http://localhost/artflow2/testes

---

## 📁 Estrutura de Arquivos

```
artflow2_testes/
├── src/
│   ├── Controllers/
│   │   └── TestController.php    ← Controller da página de testes
│   └── Services/
│       └── TestService.php       ← Lógica de todos os testes
├── views/
│   └── testes/
│       └── index.php             ← Interface visual
├── config/
│   └── routes_testes.php         ← Rotas para adicionar
├── tests.php                     ← Arquivo STANDALONE (não requer integração)
└── README.md
```

---

## 🔍 O Que É Testado

### 1. Ambiente
- ✅ Versão do PHP (mínimo 8.1)
- ✅ Extensões: pdo, pdo_mysql, mbstring, json, curl, session
- ✅ Diretórios: storage, storage/logs, public/uploads
- ✅ Arquivos: .env, config/routes.php, .htaccess

### 2. Banco de Dados
- ✅ Conexão com MySQL
- ✅ Tabelas: artes, clientes, vendas, metas, tags, arte_tags
- ✅ Estrutura das colunas principais
- ✅ Integridade referencial (foreign keys)

### 3. Rotas HTTP
- ✅ Dashboard: /, /dashboard
- ✅ Artes: /artes, /artes/criar
- ✅ Clientes: /clientes, /clientes/criar
- ✅ Vendas: /vendas, /vendas/criar, /vendas/relatorio
- ✅ Metas: /metas, /metas/criar
- ✅ Tags: /tags, /tags/criar
- ✅ Rota 404 (inexistente)

### 4. Segurança
- ✅ Sessão PHP ativa
- ✅ Cookie HttpOnly
- ✅ Função csrf_token()
- ✅ Proteção de arquivos sensíveis (.env, config/)
- ✅ Função e() para escape XSS

### 5. Módulos
- ✅ Core: Application, Router, Request, Response, Database, View
- ✅ Artes: Controller, Service, Repository, Model
- ✅ Clientes: Controller, Service, Repository, Model
- ✅ Vendas: Controller, Service, Repository, Model
- ✅ Metas: Controller, Service, Repository, Model
- ✅ Tags: Controller, Service, Repository, Model

### 6. Views
- ✅ Layout principal (layouts/main.php)
- ✅ Todas as views de cada módulo (index, create, show, edit)

### 7. Helpers
- ✅ URL: url(), asset(), redirect()
- ✅ Formatação: money(), date_br(), datetime_br(), e()
- ✅ Formulário: csrf_token(), old(), has_error(), errors()
- ✅ Flash: flash(), flash_success(), flash_error()

---

## 🎨 Interface

A interface mostra:

1. **Cards de Resumo** - Total passou/falhou/avisos
2. **Barra de Progresso** - Visual do status geral
3. **Navegação por Módulo** - Filtra por categoria
4. **Tabelas de Resultados** - Detalhes de cada teste
5. **Lista de Problemas** - Falhas destacadas

---

## ⚠️ Segurança

**IMPORTANTE:** Este sistema de testes deve ser:

1. **Removido** em produção, ou
2. **Protegido** com autenticação

O arquivo expõe informações sensíveis do sistema!

---

## 🔧 Personalização

### Adicionar novos testes

No `TestService.php`, adicione métodos no formato:

```php
public function testMeuTeste(): array
{
    $testes = [];
    
    $testes['nome_teste'] = [
        'nome' => 'Descrição do Teste',
        'status' => 'pass', // pass, fail, warn, skip
        'mensagem' => 'Resultado'
    ];
    
    return $testes;
}
```

### Testar via API

```javascript
fetch('/testes/api?modulo=banco')
    .then(r => r.json())
    .then(data => console.log(data));
```

---

## 📊 Interpretação dos Resultados

| Status | Significado | Ação |
|--------|-------------|------|
| ✅ **PASS** | Teste passou | Nenhuma |
| ❌ **FAIL** | Teste falhou | Corrigir urgente |
| ⚠️ **WARN** | Aviso | Avaliar necessidade |
| ⏭️ **SKIP** | Pulado | Verificar dependências |

---

## 📝 Changelog

### v1.0.0 (30/01/2026)
- Versão inicial
- 7 categorias de testes
- Interface visual com Bootstrap 5
- Arquivo standalone independente
- Integração opcional com sistema

---

*Sistema de Testes criado por Claude AI para ArtFlow 2.0*
