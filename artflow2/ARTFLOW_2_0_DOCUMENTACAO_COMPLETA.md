# ArtFlow 2.0 — Documentação Técnica Completa

> **Versão:** 2.0.0-beta  
> **Atualizado em:** 01/02/2026  
> **Ambiente:** XAMPP (Apache + MySQL + PHP 8.1+)  
> **Status Geral:** Sistema funcional — tests.php 100% aprovado  
> **Próxima Etapa:** Teste individual de cada módulo (CRUD real no navegador)

---

## 1. VISÃO GERAL

O ArtFlow 2.0 é um sistema de gestão artística para profissionais de arte digital. Gerencia portfólio de artes, base de clientes, vendas com cálculo automático de lucro/rentabilidade, metas mensais e categorização por tags.

**Objetivo do documento:** Servir como referência única e atualizada para trabalhar cada módulo em conversas separadas, sem perder contexto.

### 1.1 Stack Tecnológica

| Componente | Tecnologia | Versão |
|---|---|---|
| Linguagem | PHP | 8.1+ |
| Banco de Dados | MySQL / MariaDB | 5.7+ / 10.3+ |
| Servidor Web | Apache (XAMPP) | mod_rewrite habilitado |
| Autoload | Composer PSR-4 | `App\` → `src/` |
| Frontend | Bootstrap 5 | CDN |
| Gráficos | Chart.js | CDN |
| Padrão | MVC + Repository + Service Layer | Customizado (sem framework) |

### 1.2 URL de Acesso

```
http://localhost/artflow2/           → Dashboard
http://localhost/artflow2/tests.php  → Sistema de testes
http://localhost/artflow2/seeds.php  → Populador de dados
http://localhost/artflow2/install.php → Instalação limpa
```

---

## 2. ARQUITETURA DO SISTEMA

### 2.1 Fluxo de Requisição

```
Navegador → Apache (.htaccess rewrite)
         → public/index.php (Front Controller)
         → Application::__construct() (carrega .env, DI Container, sessão)
         → Router::dispatch() (match de rota + extrai parâmetros)
         → Container::make(Controller) (resolve dependências via DI)
         → Controller::método(Request, ...params)
            → Service (lógica de negócio + validação)
               → Repository (SQL + hydrate Model)
                  → Database (PDO Singleton)
            → View::render() (template PHP + layout)
         → Response::send() (HTTP status + headers + conteúdo)
```

### 2.2 Padrões Utilizados

| Padrão | Onde | Por quê |
|---|---|---|
| **Front Controller** | `public/index.php` | Ponto de entrada único; toda requisição passa por aqui |
| **MVC** | Controllers / Views / Models | Separação de responsabilidades |
| **Repository** | `src/Repositories/` | Abstrai acesso ao banco; cada tabela tem seu Repository |
| **Service Layer** | `src/Services/` | Lógica de negócio isolada; orquestra múltiplos Repositories |
| **Dependency Injection** | `src/Core/Container.php` | Resolve dependências automaticamente via Reflection |
| **Active Record Simplificado** | `src/Models/` | Models com `fromArray()` / `toArray()` + getters/setters |

### 2.3 Camadas — Regras

```
Controller  → Recebe Request, chama Service, retorna Response
              NÃO faz lógica de negócio
              NÃO escreve SQL
              
Service     → Valida dados, executa regras de negócio, orquestra Repositories
              NÃO conhece HTTP (Request/Response)
              NÃO escreve SQL diretamente
              
Repository  → Executa SQL, faz hydrate de Models, retorna dados
              NÃO faz validação
              NÃO conhece HTTP
              
Model       → Entidade do domínio, getters/setters, conversão array⟷objeto
              NÃO acessa banco
              NÃO contém lógica de negócio complexa
              
View        → Template PHP com dados recebidos do Controller
              NÃO acessa Service ou Repository
              Usa helpers: e(), money(), url(), csrf_token(), old()
```

---

## 3. ESTRUTURA DE DIRETÓRIOS

```
C:\xampp\htdocs\artflow2\
│
├── .env                           # Configuração de ambiente (DB, APP_URL, etc.)
├── .htaccess                      # Redireciona tudo para public/index.php
├── composer.json                  # PSR-4 autoload: App\ → src/
├── install.php                    # Script de instalação (cria DB, migrations, seeds)
├── tests.php                      # Sistema de testes standalone (7 categorias)
├── seeds.php                      # Populador de dados de teste
├── test_forms.php                 # Testes de formulários
│
├── config/
│   ├── routes.php                 # Todas as rotas do sistema
│   └── routes_testes.php          # Rotas do módulo de testes (opcional)
│
├── public/
│   ├── index.php                  # FRONT CONTROLLER — ponto de entrada
│   ├── .htaccess                  # Rewrite para index.php
│   └── assets/
│       ├── css/                   # Estilos customizados
│       ├── js/                    # Scripts customizados
│       └── images/                # Imagens do sistema
│
├── src/
│   ├── Core/                      # Núcleo — NÃO modificar sem necessidade
│   │   ├── Application.php        # Bootstrap: .env, Container, Router, sessão
│   │   ├── Router.php             # Sistema de rotas com parâmetros dinâmicos
│   │   ├── Request.php            # Encapsula $_GET, $_POST, $_SERVER, $_FILES
│   │   ├── Response.php           # HTTP response: status, headers, redirect, JSON
│   │   ├── Database.php           # PDO Singleton com configuração do .env
│   │   ├── Container.php          # DI Container com auto-resolução via Reflection
│   │   ├── View.php               # Motor de templates: render, layout, variáveis
│   │   ├── Migration.php          # Classe base para migrations
│   │   └── Schema.php             # Builder de tabelas para migrations
│   │
│   ├── Controllers/
│   │   ├── BaseController.php     # Classe base: view(), json(), redirect(), validação CSRF
│   │   ├── DashboardController.php
│   │   ├── ArteController.php
│   │   ├── ClienteController.php
│   │   ├── VendaController.php
│   │   ├── MetaController.php
│   │   ├── TagController.php
│   │   └── TestController.php     # Controller do sistema de testes
│   │
│   ├── Services/
│   │   ├── ArteService.php
│   │   ├── ClienteService.php
│   │   ├── VendaService.php       # Calcula lucro, rentabilidade, atualiza metas
│   │   ├── MetaService.php        # Progresso automático, projeções
│   │   ├── TagService.php
│   │   ├── TimerService.php       # Timer de horas (estrutura existe, lógica parcial)
│   │   └── TestService.php        # 7 categorias de testes automatizados
│   │
│   ├── Repositories/
│   │   ├── BaseRepository.php     # CRUD genérico: find, findAll, create, update, delete
│   │   ├── ArteRepository.php
│   │   ├── ClienteRepository.php  # topClientes() retorna arrays (não objetos)
│   │   ├── VendaRepository.php
│   │   ├── MetaRepository.php
│   │   └── TagRepository.php
│   │
│   ├── Models/
│   │   ├── Arte.php
│   │   ├── Cliente.php
│   │   ├── Venda.php
│   │   ├── Meta.php
│   │   └── Tag.php
│   │
│   ├── Validators/
│   │   ├── ArteValidator.php
│   │   ├── ClienteValidator.php
│   │   ├── VendaValidator.php
│   │   ├── MetaValidator.php
│   │   └── TagValidator.php
│   │
│   ├── Exceptions/
│   │   ├── ValidationException.php
│   │   └── NotFoundException.php
│   │
│   └── Helpers/
│       └── functions.php          # Funções globais: url(), money(), csrf_token(), etc.
│
├── views/
│   ├── layouts/
│   │   ├── main.php               # Layout principal com sidebar, navbar, dark mode
│   │   └── error.php              # Layout de erro (404, 500)
│   │
│   ├── components/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   ├── footer.php
│   │   ├── alerts.php             # Flash messages
│   │   └── pagination.php
│   │
│   ├── dashboard/
│   │   └── index.php              # Dashboard com 4 gráficos Chart.js
│   │
│   ├── artes/
│   │   ├── index.php, create.php, show.php, edit.php
│   │
│   ├── clientes/
│   │   ├── index.php, create.php, show.php, edit.php
│   │
│   ├── vendas/
│   │   ├── index.php, create.php, show.php, edit.php, relatorio.php
│   │
│   ├── metas/
│   │   ├── index.php, create.php, show.php, edit.php
│   │
│   └── tags/
│       ├── index.php, create.php, show.php, edit.php
│
├── database/
│   ├── migrations/                # 8 migrations numeradas (001-008)
│   │   ├── 001_create_artes_table.php
│   │   ├── 002_create_clientes_table.php
│   │   ├── 003_create_vendas_table.php
│   │   ├── 004_create_metas_table.php
│   │   ├── 005_create_tags_table.php
│   │   ├── 006_create_arte_tags_table.php
│   │   ├── 007_create_timer_sessoes_table.php
│   │   └── 008_create_security_tables.php
│   └── seeds/
│
├── storage/
│   ├── logs/                      # Logs de erro
│   └── cache/
│
└── vendor/
    └── autoload.php               # Composer autoloader (PSR-4)
```

---

## 4. BANCO DE DADOS

### 4.1 Diagrama de Relacionamentos

```
┌──────────────────┐       ┌──────────────────┐
│   estilos_arte   │       │      tags        │
│ (PENDENTE)       │       │                  │
│──────────────────│       │──────────────────│
│ id PK            │       │ id PK            │
│ nome UNIQUE      │       │ nome UNIQUE      │
│ preco_base       │       │ cor              │
│ tempo_estimado   │       │ icone            │
│ cor              │       │ timestamps       │
│ ativo            │       └────────┬─────────┘
│ ordem            │                │
└────────┬─────────┘                │ N:N
         │ 1:N (futuro)             │
         │                  ┌───────┴─────────┐
┌────────┴─────────┐       │   arte_tags     │
│      artes       │       │  (tabela pivot) │
│──────────────────│       │─────────────────│
│ id PK            │◄──────┤ arte_id FK      │
│ nome             │       │ tag_id FK       │
│ descricao        │       │ PK(arte,tag)    │
│ tempo_medio_horas│       └─────────────────┘
│ complexidade     │
│ preco_custo      │
│ horas_trabalhadas│
│ status           │
│ imagem           │
│ timestamps       │
└─────┬────────────┘
      │ 1:N                 1:N
      │              ┌──────────────────┐
      ├──────────────┤     vendas       │
      │              │──────────────────│
      │              │ id PK            │
      │              │ arte_id FK ──────┘  (SET NULL)
      │              │ cliente_id FK ───┐  (SET NULL)
      │              │ valor            │
      │              │ data_venda       │
      │              │ lucro_calculado  │  ← automático: valor - preco_custo
      │              │ rentabilidade_hora│ ← automático: lucro / horas
      │              │ forma_pagamento  │
      │              │ observacoes      │
      │              │ timestamps       │
      │              └─────────────────┘
      │
      │ 1:N          ┌──────────────────┐
      └──────────────┤  timer_sessoes   │
                     │──────────────────│
                     │ id PK            │
                     │ arte_id FK ──────┘  (CASCADE)
                     │ inicio           │
                     │ fim              │
                     │ duracao_segundos │
                     │ status           │
                     │ pausas (JSON)    │
                     │ timestamps       │
                     └─────────────────┘

┌──────────────────┐       ┌──────────────────┐
│    clientes      │       │     metas        │
│──────────────────│       │──────────────────│
│ id PK            │       │ id PK            │
│ nome             │       │ mes_ano UNIQUE   │
│ email UNIQUE     │       │ valor_meta       │
│ telefone         │       │ horas_diarias    │
│ cidade           │       │ dias_trabalho    │
│ estado           │       │ valor_realizado  │ ← atualizado por VendaService
│ timestamps       │       │ porcentagem      │ ← calculado automaticamente
└──────────────────┘       │ observacoes      │
    ▲                      │ timestamps       │
    │ 1:N                  └──────────────────┘
    └── vendas.cliente_id
```

### 4.2 Tabelas — Detalhamento

#### `artes` (Migration 001)
| Coluna | Tipo | Restrição | Descrição |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador |
| nome | VARCHAR(150) | NOT NULL, INDEX | Nome da arte |
| descricao | TEXT | NULL | Descrição detalhada |
| tempo_medio_horas | DECIMAL(6,2) | NULL | Tempo estimado |
| complexidade | ENUM('baixa','media','alta') | DEFAULT 'media' | Nível de dificuldade |
| preco_custo | DECIMAL(10,2) | DEFAULT 0 | Custo de produção em R$ |
| horas_trabalhadas | DECIMAL(8,2) | DEFAULT 0 | Horas investidas |
| status | ENUM('disponivel','em_producao','vendida','reservada') | DEFAULT 'disponivel' | Estado atual |
| imagem | VARCHAR(255) | NULL | Caminho do arquivo (upload NÃO implementado) |
| created_at | TIMESTAMP | DEFAULT CURRENT | Criação |
| updated_at | TIMESTAMP | NULL ON UPDATE | Última alteração |

#### `clientes` (Migration 002)
| Coluna | Tipo | Restrição | Descrição |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador |
| nome | VARCHAR(150) | NOT NULL, INDEX | Nome completo |
| email | VARCHAR(150) | UNIQUE, NULL, INDEX | E-mail |
| telefone | VARCHAR(20) | NULL | Telefone/WhatsApp |
| cidade | VARCHAR(100) | NULL | Cidade (adicionado nos seeds) |
| estado | VARCHAR(2) | NULL | UF (adicionado nos seeds) |
| created_at | TIMESTAMP | DEFAULT CURRENT | Cadastro |
| updated_at | TIMESTAMP | NULL ON UPDATE | Última alteração |

**Nota:** A migration original usava `empresa` em vez de `cidade/estado`. O seeds.php usa `cidade` e `estado`. Verificar se a migration foi atualizada ou se o campo no banco difere do esperado.

#### `vendas` (Migration 003)
| Coluna | Tipo | Restrição | Descrição |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador |
| arte_id | INT UNSIGNED | FK → artes(id) SET NULL | Arte vendida |
| cliente_id | INT UNSIGNED | FK → clientes(id) SET NULL | Comprador |
| valor | DECIMAL(10,2) | NOT NULL | Valor da venda em R$ |
| data_venda | DATE | NOT NULL, INDEX | Data da transação |
| lucro_calculado | DECIMAL(10,2) | NULL | `valor - preco_custo` (calculado no Service) |
| rentabilidade_hora | DECIMAL(10,2) | NULL | `lucro / horas_trabalhadas` (calculado) |
| forma_pagamento | ENUM('dinheiro','pix','cartao_credito','cartao_debito','transferencia','outro') | DEFAULT 'pix' | Método de pagamento |
| observacoes | TEXT | NULL | Notas da venda |
| created_at | TIMESTAMP | DEFAULT CURRENT | Registro |
| updated_at | TIMESTAMP | NULL ON UPDATE | Última alteração |

**Comportamento das FKs:** SET NULL garante que se a arte ou cliente for excluído, a venda permanece no histórico (sem perder dados financeiros).

#### `metas` (Migration 004)
| Coluna | Tipo | Restrição | Descrição |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador |
| mes_ano | DATE | UNIQUE, INDEX | Primeiro dia do mês (ex: 2026-02-01) |
| valor_meta | DECIMAL(10,2) | NOT NULL | Meta de faturamento em R$ |
| horas_diarias_ideal | INT | DEFAULT 8 | Meta de horas/dia |
| dias_trabalho_semana | INT | DEFAULT 5 | Dias úteis/semana |
| valor_realizado | DECIMAL(10,2) | DEFAULT 0 | Soma vendas do mês (atualizado por VendaService) |
| porcentagem_atingida | DECIMAL(5,2) | DEFAULT 0 | `(realizado/meta)*100` |
| observacoes | TEXT | NULL | Anotações |
| created_at | TIMESTAMP | DEFAULT CURRENT | Criação |
| updated_at | TIMESTAMP | NULL ON UPDATE | Última alteração |

#### `tags` (Migration 005)
| Coluna | Tipo | Restrição | Descrição |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador |
| nome | VARCHAR(50) | UNIQUE, NOT NULL | Nome da tag |
| cor | VARCHAR(7) | DEFAULT '#6c757d' | Cor hex para badges |
| icone | VARCHAR(50) | NULL | Ícone Bootstrap Icons |
| created_at | TIMESTAMP | DEFAULT CURRENT | Criação |
| updated_at | TIMESTAMP | NULL ON UPDATE | Última alteração |

#### `arte_tags` (Migration 006 — Pivot N:N)
| Coluna | Tipo | Restrição |
|---|---|---|
| arte_id | INT UNSIGNED | PK composta, FK → artes(id) CASCADE |
| tag_id | INT UNSIGNED | PK composta, FK → tags(id) CASCADE |

CASCADE: ao deletar arte ou tag, remove automaticamente a associação.

#### `timer_sessoes` (Migration 007)
| Coluna | Tipo | Restrição | Descrição |
|---|---|---|---|
| id | INT UNSIGNED | PK, AUTO_INCREMENT | Identificador |
| arte_id | INT UNSIGNED | FK → artes(id) CASCADE, INDEX | Arte sendo trabalhada |
| inicio | DATETIME | NOT NULL | Início da sessão |
| fim | DATETIME | NULL | Fim (null = rodando) |
| duracao_segundos | INT | DEFAULT 0 | Duração total |
| status | ENUM('rodando','pausado','finalizado') | DEFAULT 'rodando' | Estado |
| pausas | TEXT/JSON | NULL | Histórico de pausas em JSON |
| tempo_pausado_segundos | INT | DEFAULT 0 | Total pausado |
| observacoes | TEXT | NULL | Notas da sessão |

**Status:** Tabela criada pela migration, mas funcionalidade do timer NÃO implementada na UI.

#### `security tables` (Migration 008)
Cria tabelas auxiliares: `csrf_tokens`, `activity_log`, `configuracoes`.

---

## 5. SISTEMA DE ROTAS

### 5.1 Rotas Completas

O método `$router->resource()` gera automaticamente 7 rotas RESTful:

```
GET    /{modulo}              → index()       (listar)
GET    /{modulo}/criar        → create()      (formulário criação)
POST   /{modulo}              → store()       (salvar novo)
GET    /{modulo}/{id}         → show()        (detalhes)
GET    /{modulo}/{id}/editar  → edit()        (formulário edição)
PUT    /{modulo}/{id}         → update()      (atualizar)
POST   /{modulo}/{id}/atualizar → update()    (fallback compatibilidade)
DELETE /{modulo}/{id}         → destroy()     (excluir)
POST   /{modulo}/{id}/deletar  → destroy()   (fallback compatibilidade)
```

**Suporte a _method:** PUT e DELETE são enviados via POST com campo hidden `_method`.

### 5.2 Mapa Completo de Rotas

```
DASHBOARD
  GET  /                            → DashboardController@index
  GET  /dashboard                   → DashboardController@index
  GET  /dashboard/refresh           → DashboardController@refresh      (AJAX)
  GET  /dashboard/artes             → DashboardController@estatisticasArtes
  GET  /dashboard/vendas            → DashboardController@estatisticasVendas
  GET  /dashboard/meta              → DashboardController@progressoMeta
  GET  /dashboard/atividades        → DashboardController@atividadesRecentes
  GET  /dashboard/busca             → DashboardController@busca

ARTES
  [resource /artes]                 → ArteController (7 rotas automáticas)
  POST /artes/{id}/status           → ArteController@alterarStatus
  POST /artes/{id}/horas            → ArteController@adicionarHoras

CLIENTES
  GET  /clientes/buscar             → ClienteController@buscar        (ANTES do resource!)
  [resource /clientes]              → ClienteController (7 rotas)

VENDAS
  GET  /vendas/relatorio            → VendaController@relatorio       (ANTES do resource!)
  [resource /vendas]                → VendaController (7 rotas)

METAS
  GET  /metas/resumo                → MetaController@resumo           (ANTES do resource!)
  POST /metas/{id}/recalcular       → MetaController@recalcular
  [resource /metas]                 → MetaController (7 rotas)

TAGS
  GET  /tags/buscar                 → TagController@buscar            (ANTES do resource!)
  GET  /tags/select                 → TagController@select
  POST /tags/rapida                 → TagController@criarRapida
  [resource /tags]                  → TagController (7 rotas)

BUSCA GLOBAL
  GET  /busca?q={termo}             → Closure (TODO: implementar SearchController)

TESTE
  GET  /teste                       → Closure (página de verificação)
```

**REGRA CRÍTICA:** Rotas específicas (como `/vendas/relatorio`) DEVEM ser declaradas ANTES de `$router->resource()`, senão o Router interpreta "relatorio" como `{id}` e chama `show()`.

---

## 6. MÓDULOS — DETALHAMENTO INDIVIDUAL

---

### 6.1 MÓDULO: DASHBOARD

**Rota:** `GET /`  
**Controller:** `DashboardController`  
**Dependências:** ArteService, VendaService, MetaService, ClienteService

#### O que faz

Agrega dados de TODOS os módulos em uma página de visão geral:

1. **Cards de Estatísticas:** Total de artes, artes em produção, vendas do mês, meta do mês
2. **4 Gráficos Chart.js:**
   - Faturamento Mensal (barras) — últimos 6 meses
   - Status das Artes (doughnut) — disponível/em produção/vendida
   - Meta do Mês (semi-doughnut) — % atingida com cores dinâmicas
   - Evolução de Vendas (linha + barras) — faturamento vs quantidade
3. **Top 5 Clientes** — por valor total de compras
4. **Artes Disponíveis** — prontas para venda
5. **Ranking de Rentabilidade** — artes com melhor R$/hora
6. **Modo Escuro** — toggle no sidebar com persistência via localStorage

#### Dependências entre módulos

```
DashboardController
├── ArteService.getEstatisticas()        → contagem por status
├── ArteService.getDisponiveisParaVenda() → artes com status 'disponivel'
├── VendaService.getVendasMesAtual()     → vendas do mês corrente
├── VendaService.getTotalMes()           → faturamento mensal
├── VendaService.getVendasMensais(6)     → dados para gráfico (6 meses)
├── VendaService.getRankingRentabilidade(5) → top 5 mais rentáveis
├── MetaService.getResumoDashboard()     → meta atual + % + projeção
└── ClienteService.getTopClientes(5)     → top 5 compradores
```

#### Bugs Corrigidos

| Bug | Causa | Correção |
|---|---|---|
| Top Clientes zerado | `ClienteRepository::topClientes()` retornava objetos hydrated que perdiam campos calculados (total_compras, total_gasto) | Alterado para retornar arrays brutos em vez de objetos |
| Gráficos infinitos | Canvas com `responsive:true` dentro de card flex sem altura fixa → loop de redimensionamento | Cada `<canvas>` envolvido em `<div style="position:relative; height:280px;">` |
| Dashboard quebrado | Objeto Cliente usado como array | Verificação defensiva `is_object()` / `is_array()` implementada |

#### Status de Teste: ✅ Testado e Funcional

---

### 6.2 MÓDULO: ARTES

**Rotas:** `resource /artes` + 2 rotas extras  
**Controller:** `ArteController`  
**Service:** `ArteService`  
**Repository:** `ArteRepository`  
**Model:** `Arte`  
**Validator:** `ArteValidator`  
**Views:** `artes/index.php`, `create.php`, `show.php`, `edit.php`

#### Estrutura de Classes

```
ArteController
├── __construct(ArteService, TagService)   ← precisa de TagService para seletor de tags
├── index(Request)        → lista com filtros (status, tag, busca)
├── create(Request)       → formulário com tags disponíveis
├── store(Request)        → valida + cria + associa tags
├── show(Request, id)     → detalhes + tags da arte
├── edit(Request, id)     → formulário edição + tags
├── update(Request, id)   → valida + atualiza + sync tags
├── destroy(Request, id)  → exclui arte
├── alterarStatus(Request, id)  → muda status sem editar tudo
└── adicionarHoras(Request, id) → incrementa horas_trabalhadas

ArteService
├── listar(filtros)               → delega para Repository com filtros
├── buscar(id)                    → findOrFail
├── criar(dados)                  → valida + cria + associa tags
├── atualizar(id, dados)          → valida + atualiza + sync tags
├── excluir(id)                   → remove
├── alterarStatus(id, status)     → valida enum + atualiza
├── adicionarHoras(id, horas)     → incrementa
├── getEstatisticas()             → contagem por status para dashboard
├── getDisponiveisParaVenda()     → WHERE status = 'disponivel'
└── pesquisar(termo)              → busca por nome/descrição

ArteRepository extends BaseRepository
├── (herdados) find, findAll, create, update, delete, findOrFail
├── findByStatus(status)
├── findByTag(tag_id)
├── pesquisar(termo)              → LIKE no nome + descrição
├── countByStatus()               → GROUP BY status
├── getDisponiveisParaVenda()
└── sincronizarTags(arte_id, tag_ids[])  → DELETE + INSERT na arte_tags
```

#### Dependências

```
ArteController → ArteService + TagService
ArteService   → ArteRepository + ArteValidator
(Independente: NÃO depende de VendaService ou MetaService)
```

**Quem depende de Artes:**
- VendaService usa ArteRepository para buscar arte e atualizar status para 'vendida'
- DashboardController usa ArteService para estatísticas

#### Campos do Formulário

| Campo | Tipo HTML | Validação | Obrigatório |
|---|---|---|---|
| nome | text | max:150 | ✅ |
| descricao | textarea | — | ❌ |
| tempo_medio_horas | number (step 0.5) | min:0 | ❌ |
| complexidade | select | in:baixa,media,alta | ✅ |
| preco_custo | number (step 0.01) | min:0 | ✅ |
| horas_trabalhadas | number (step 0.5) | min:0 | ❌ |
| status | select | in:disponivel,em_producao,vendida,reservada | ✅ |
| tags[] | checkbox multiple | IDs existentes | ❌ |

#### Status de Teste: ❌ CRUD NÃO TESTADO no navegador

---

### 6.3 MÓDULO: CLIENTES

**Rotas:** `resource /clientes` + busca  
**Controller:** `ClienteController`  
**Service:** `ClienteService`  
**Repository:** `ClienteRepository`  
**Model:** `Cliente`  
**Validator:** `ClienteValidator`  
**Views:** `clientes/index.php`, `create.php`, `show.php`, `edit.php`

#### Estrutura de Classes

```
ClienteController
├── __construct(ClienteService)
├── index(Request)        → lista com busca
├── create(Request)       → formulário
├── store(Request)        → valida + cria
├── show(Request, id)     → detalhes + histórico de compras
├── edit(Request, id)     → formulário edição
├── update(Request, id)   → valida + atualiza
├── destroy(Request, id)  → exclui (vendas ficam com cliente_id NULL)
└── buscar(Request)       → busca AJAX para selects

ClienteService
├── listar(filtros)
├── buscar(id)
├── criar(dados)
├── atualizar(id, dados)
├── excluir(id)
├── getTopClientes(limite)        → top compradores por valor total
└── buscarPorTermo(termo)         → para autocomplete/select

ClienteRepository extends BaseRepository
├── (herdados) CRUD genérico
├── pesquisar(termo)              → nome ou email LIKE
├── topClientes(limite)           → JOIN vendas, SUM(valor), retorna ARRAYS (não objetos)
├── getHistoricoCompras(cliente_id)
└── findByEmail(email)
```

#### Dependências

```
ClienteController → ClienteService
ClienteService   → ClienteRepository + ClienteValidator
(Independente: NÃO depende de outros módulos)
```

**Quem depende de Clientes:**
- VendaController precisa listar clientes para select no formulário de venda
- DashboardController usa ClienteService.getTopClientes()

#### Campos do Formulário

| Campo | Tipo HTML | Validação | Obrigatório |
|---|---|---|---|
| nome | text | max:150 | ✅ |
| email | email | unique, max:150 | ❌ |
| telefone | text | max:20 | ❌ |
| cidade | text | max:100 | ❌ |
| estado | text | max:2 | ❌ |

**Nota sobre campos:** A migration original define `empresa` (VARCHAR 100). O seeds.php usa `cidade` e `estado`. Verificar se há discrepância no banco real.

#### Status de Teste: ⚠️ Parcial (listagem e criação ✅; editar/excluir ⚠️)

---

### 6.4 MÓDULO: VENDAS

**Rotas:** `resource /vendas` + relatório  
**Controller:** `VendaController`  
**Service:** `VendaService`  
**Repository:** `VendaRepository`  
**Model:** `Venda`  
**Validator:** `VendaValidator`  
**Views:** `vendas/index.php`, `create.php`, `show.php`, `edit.php`, `relatorio.php`

#### Estrutura de Classes

```
VendaController
├── __construct(VendaService, ArteService, ClienteService)  ← 3 dependências!
├── index(Request)        → lista com filtros (período, mês, cliente)
├── create(Request)       → formulário com selects de artes disponíveis e clientes
├── store(Request)        → valida + registra + calcula lucro + atualiza meta
├── show(Request, id)     → detalhes com arte e cliente associados
├── edit(Request, id)     → formulário edição
├── update(Request, id)   → atualiza
├── destroy(Request, id)  → exclui + recalcula meta do mês
└── relatorio(Request)    → relatório com filtros de período

VendaService  ← MÓDULO MAIS COMPLEXO (orquestra 3 repositories)
├── listar(filtros)                → filtros por período, mês, cliente
├── buscar(id)
├── registrar(dados)               → FLUXO PRINCIPAL:
│   ├── 1. Valida dados (VendaValidator)
│   ├── 2. Busca arte (ArteRepository)
│   ├── 3. Calcula: lucro = valor - preco_custo
│   ├── 4. Calcula: rentabilidade = lucro / horas_trabalhadas
│   ├── 5. Registra venda (VendaRepository)
│   ├── 6. Atualiza arte.status → 'vendida' (ArteRepository)
│   └── 7. Atualiza meta do mês (MetaRepository)
├── atualizar(id, dados)
├── excluir(id)                    → recalcula meta
├── getVendasMesAtual()
├── getTotalMes()                  → SUM(valor) do mês corrente
├── getVendasMensais(meses)        → dados para gráfico de barras
├── getRankingRentabilidade(limite) → ORDER BY rentabilidade_hora DESC
├── getRelatorioPeriodo(inicio, fim)
└── recalcularMetaMes(mes_ano)     → re-soma vendas para o mês

VendaRepository extends BaseRepository
├── findByPeriodo(data_inicio, data_fim)
├── findByMes(mes_ano)             → WHERE YEAR/MONTH match
├── findByCliente(cliente_id)
├── getRecentes(limite)
├── getTotalMes(mes_ano)           → SUM(valor)
├── getVendasMensais(meses)        → GROUP BY mês com SUM
├── getRankingRentabilidade(limite) → JOIN artes, ORDER BY rentabilidade
└── getRelatorioPeriodo(inicio, fim) → dados agrupados para relatório
```

#### Dependências (MÓDULO MAIS ACOPLADO)

```
VendaController → VendaService + ArteService + ClienteService
VendaService   → VendaRepository + ArteRepository + MetaRepository + VendaValidator
```

**Impacto ao registrar venda:**
1. Cria registro em `vendas`
2. Atualiza `artes.status` → 'vendida'
3. Soma `vendas.valor` do mês → atualiza `metas.valor_realizado` e `porcentagem_atingida`

**Quem depende de Vendas:**
- DashboardController usa VendaService para gráficos e estatísticas
- MetaService pode consultar VendaRepository para recalcular progresso

#### Variáveis do Controller para Views

```php
// create.php e edit.php esperam:
'artesDisponiveis' => array de Arte (artes com status != 'vendida')
'clientesSelect'   => array de Cliente (todos os clientes)

// ⚠️ BUG CORRIGIDO: Antes passava 'artes'/'clientes', view esperava nomes diferentes
```

#### Campos do Formulário

| Campo | Tipo HTML | Validação | Obrigatório |
|---|---|---|---|
| arte_id | select | exists:artes | ✅ |
| cliente_id | select | exists:clientes | ✅ |
| valor | number (step 0.01) | min:0.01 | ✅ |
| data_venda | date | date_format | ✅ |
| forma_pagamento | select | in:dinheiro,pix,... | ✅ |
| observacoes | textarea | — | ❌ |

#### Status de Teste: ❌ CRUD NÃO TESTADO no navegador

---

### 6.5 MÓDULO: METAS

**Rotas:** `resource /metas` + resumo + recalcular  
**Controller:** `MetaController`  
**Service:** `MetaService`  
**Repository:** `MetaRepository`  
**Model:** `Meta`  
**Validator:** `MetaValidator`  
**Views:** `metas/index.php`, `create.php`, `show.php`, `edit.php`

#### Estrutura de Classes

```
MetaController
├── __construct(MetaService)
├── index(Request)        → lista de metas (pode filtrar por ano)
├── create(Request)       → formulário
├── store(Request)        → valida + cria (1 meta por mês, UNIQUE mes_ano)
├── show(Request, id)     → detalhes + barra de progresso + projeção
├── edit(Request, id)     → formulário edição
├── update(Request, id)   → atualiza
├── destroy(Request, id)  → exclui
├── resumo(Request)       → resumo da meta atual (AJAX)
└── recalcular(Request, id) → re-soma vendas do mês

MetaService
├── listar(filtros)                → filtro por ano
├── buscar(id)
├── buscarMesAtual()               → WHERE mes_ano = primeiro dia do mês atual
├── criar(dados)                   → valida + verifica unicidade mes_ano
├── atualizar(id, dados)
├── excluir(id)
├── getResumoDashboard()           → meta atual + % + valor falta + projeção
├── recalcularProgresso(id)        → soma vendas do mês via VendaRepository
└── getProjecao(meta)              → projeção linear baseada nos dias restantes

MetaRepository extends BaseRepository
├── findMesAtual()                 → meta do mês corrente
├── findByAno(ano)
├── existsMesAno(mes_ano)          → verifica duplicata
├── getRecentes(limite)            → ORDER BY mes_ano DESC
└── atualizarProgresso(id, valor_realizado, porcentagem)  → retorna bool (NÃO Meta)
```

#### Dependências

```
MetaController → MetaService
MetaService   → MetaRepository + VendaRepository + MetaValidator
```

**Quem atualiza Metas:**
- VendaService, ao registrar/excluir venda, chama `MetaRepository::atualizarProgresso()`
- MetaController::recalcular() força re-soma manual

#### Bug Corrigido

| Bug | Causa | Correção |
|---|---|---|
| TypeError atualizarProgresso | `MetaRepository::atualizarProgresso()` retornava objeto Meta ao invés de bool | Alterado return type para `bool` |

#### Campos do Formulário

| Campo | Tipo HTML | Validação | Obrigatório |
|---|---|---|---|
| mes_ano | month | unique, date_format | ✅ |
| valor_meta | number (step 0.01) | min:0.01 | ✅ |
| horas_diarias_ideal | number | min:1, max:24 | ❌ (default 8) |
| dias_trabalho_semana | number | min:1, max:7 | ❌ (default 5) |
| observacoes | textarea | — | ❌ |

#### Status de Teste: ❌ CRUD NÃO TESTADO no navegador

---

### 6.6 MÓDULO: TAGS

**Rotas:** `resource /tags` + buscar + select + criarRapida  
**Controller:** `TagController`  
**Service:** `TagService`  
**Repository:** `TagRepository`  
**Model:** `Tag`  
**Validator:** `TagValidator`  
**Views:** `tags/index.php`, `create.php`, `show.php`, `edit.php`

#### Estrutura de Classes

```
TagController
├── __construct(TagService)
├── index(Request)        → lista com contagem de artes por tag
├── create(Request)       → formulário com seletor de cores
├── store(Request)        → valida + cria
├── show(Request, id)     → detalhes + artes que usam a tag
├── edit(Request, id)     → formulário edição
├── update(Request, id)   → atualiza
├── destroy(Request, id)  → exclui (CASCADE remove relações em arte_tags)
├── buscar(Request)       → busca AJAX
├── select(Request)       → JSON com todas tags para selects dinâmicos
└── criarRapida(Request)  → cria tag via AJAX (sem recarregar página)

TagService
├── listar()
├── buscar(id)
├── criar(dados)
├── atualizar(id, dados)
├── excluir(id)
├── listarComContagem()            → JOIN arte_tags, COUNT por tag
└── buscarPorTermo(termo)

TagRepository extends BaseRepository
├── findComContagem()              → LEFT JOIN arte_tags GROUP BY
├── findByNome(nome)               → para verificar unicidade
├── getTagsDaArte(arte_id)         → tags de uma arte específica
└── getMaisUsadas(limite)          → ORDER BY contagem DESC
```

#### Dependências

```
TagController → TagService
TagService   → TagRepository + TagValidator
(Independente: NÃO depende de outros módulos)
```

**Quem depende de Tags:**
- ArteController injeta TagService para seletor de tags nos formulários
- ArteService usa ArteRepository::sincronizarTags() para a tabela pivot

#### Bug Corrigido

| Bug | Causa | Correção |
|---|---|---|
| Propriedade dinâmica deprecated | PHP 8.2 deprecated propriedades dinâmicas em Tag | Usar método `setArtesCount()` no model |

#### Campos do Formulário

| Campo | Tipo HTML | Validação | Obrigatório |
|---|---|---|---|
| nome | text | unique, max:50 | ✅ |
| cor | color picker | hex format (#RRGGBB) | ❌ (default #6c757d) |
| icone | text | Bootstrap Icons class | ❌ |

#### Status de Teste: ❌ CRUD NÃO TESTADO no navegador

---

## 7. FUNCIONALIDADES TRANSVERSAIS

### 7.1 Helpers Globais (`src/Helpers/functions.php`)

```php
// URL
url('/artes')           → "http://localhost/artflow2/artes"
asset('css/app.css')    → "http://localhost/artflow2/public/assets/css/app.css"
redirect('/clientes')   → header Location + exit

// Formatação
money(1500.50)          → "R$ 1.500,50"
date_br('2026-01-30')   → "30/01/2026"
datetime_br('...')       → "30/01/2026 14:35"
e($string)              → htmlspecialchars (XSS protection)

// Formulário
csrf_token()            → gera input hidden com token CSRF
old('nome')             → valor anterior do campo (após validação falhar)
has_error('campo')      → bool se campo tem erro
errors('campo')         → mensagem de erro do campo

// Flash Messages
flash('success')        → recupera mensagem flash da sessão
flash_success('msg')    → define mensagem de sucesso
flash_error('msg')      → define mensagem de erro
```

### 7.2 Segurança

| Mecanismo | Implementação |
|---|---|
| **CSRF** | Token gerado por request, validado no BaseController. Campo `_csrf` nos forms. |
| **XSS** | Helper `e()` escapa output. Usado em todas as views. |
| **SQL Injection** | PDO prepared statements em todos os Repositories. |
| **Sessão** | HttpOnly cookies. Session regenerate em operações sensíveis. |
| **Diretórios** | `.htaccess` protege config/, src/, database/, storage/ |

**Bug corrigido:** BaseController validava campo `_token` mas views usavam `_csrf` → padronizado para `_csrf`.

### 7.3 Dark Mode

Implementado no layout principal via toggle no sidebar. Estado persistido em `localStorage`. Classes CSS condicionais no `<body>`.

### 7.4 Flash Messages

Uso via helpers `flash_success()` / `flash_error()`. Renderizadas no componente `views/components/alerts.php` incluído no layout principal.

---

## 8. HISTÓRICO DE CORREÇÕES

### Sessão 26/01/2026 — Instalação Inicial

| # | Problema | Arquivo | Correção |
|---|---|---|---|
| 1 | `flash()` sem argumentos | functions.php | Parâmetro opcional `?string $key = null` |
| 2 | `$content` não passado ao layout | View.php | Adicionado `$data['content'] = $content` |
| 3 | Caminho de assets incorreto | View.php | Corrigido path `public/assets/` |
| 4 | Métodos faltando em Repositories | VendaRepo, ClienteRepo | Métodos adicionados |
| 5 | Métodos faltando em Services | ArteService, TagService | Métodos adicionados |
| 6 | Rota `/vendas/relatorio` como `{id}` | routes.php | Rota específica ANTES do resource |
| 7 | Rotas PUT/DELETE inoperantes | Router.php | `resource()` agora registra PUT/DELETE |
| 8 | Tag como array em views | views/artes, views/tags | Usar `->getId()`, `->getNome()` |
| 9 | Propriedade dinâmica deprecated | TagRepository | Usar `setArtesCount()` |
| 10 | View `vendas/relatorio.php` faltando | — | Arquivo criado |
| 11 | Autoloader ausente | vendor/ | `autoload.php` criado manualmente |

### Sessão 30/01/2026 — CSRF e Vendas

| # | Problema | Arquivo | Correção |
|---|---|---|---|
| 12 | CSRF: `_token` vs `_csrf` | BaseController | Padronizado para `_csrf` em tudo |
| 13 | VendaController variáveis incompatíveis | VendaController | `'artes'` → `'artesDisponiveis'`, `'clientes'` → `'clientesSelect'` |

### Sessão 31/01/2026 — Type Errors

| # | Problema | Arquivo | Correção |
|---|---|---|---|
| 14 | TypeError: atualizarProgresso retorna Meta | MetaRepository | Return type `bool` |
| 15 | TypeError: getValor() em array | VendaController | Verificação defensiva is_object/is_array |
| 16 | Cliente como array no dashboard | DashboardController | Verificação defensiva implementada |
| 17 | View metas/show.php não existia | — | View criada com progresso e projeções |

### Sessão 31/01/2026 — Dashboard e Seeds

| # | Problema | Arquivo | Correção |
|---|---|---|---|
| 18 | Top Clientes zerado | ClienteRepository | Retorna arrays brutos, não objetos hydrated |
| 19 | Gráficos infinitos | dashboard/index.php | Containers com altura fixa para canvas |
| 20 | Seeds: duplicate entry | seeds.php | DELETE + ALTER TABLE ao invés de TRUNCATE |

---

## 9. STATUS DE TESTES POR MÓDULO

### 9.1 tests.php — Testes Automatizados (100% ✅)

Verifica estrutura do sistema em 7 categorias:

| Categoria | Testes | Status |
|---|---|---|
| Ambiente | PHP version, extensões, diretórios | ✅ 100% |
| Banco de Dados | Conexão, tabelas, estrutura, FKs | ✅ 100% |
| Rotas | Todas URLs GET retornam 200 | ✅ 100% |
| Segurança | CSRF, sessão, proteção de arquivos | ✅ 100% |
| Módulos | Classes existem e instanciam | ✅ 100% |
| Views | Todos os arquivos de view existem | ✅ 100% |
| Helpers | Funções retornam valores corretos | ✅ 100% |

### 9.2 Teste Real no Navegador (CRUD) — PENDENTE

| Módulo | Listar | Criar | Visualizar | Editar | Excluir | Outros |
|---|---|---|---|---|---|---|
| Dashboard | ✅ | — | — | — | — | Gráficos ✅, Dark Mode ✅ |
| Artes | ❌ | ❌ | ❌ | ❌ | ❌ | Status ❌, Tags ❌ |
| Clientes | ✅ | ✅ | ❌ | ⚠️ | ⚠️ | Busca ❌ |
| Vendas | ❌ | ❌ | ❌ | ❌ | ❌ | Relatório ❌ |
| Metas | ❌ | ❌ | ❌ | ❌ | ❌ | Recalcular ❌ |
| Tags | ❌ | ❌ | ❌ | ❌ | ❌ | Criar Rápida ❌ |

**Legenda:** ✅ Testado OK | ⚠️ Parcial | ❌ Não testado

---

## 10. FEATURES PENDENTES

### 10.1 Prioridade Alta — Testes dos Módulos

Cada módulo precisa de teste individual cobrindo:
1. **Listagem** — carrega sem erros, exibe dados dos seeds
2. **Criar** — formulário abre, validação funciona, salva no banco
3. **Visualizar** — show exibe dados corretos, relacionamentos carregam
4. **Editar** — formulário preenche valores existentes, atualiza no banco
5. **Excluir** — confirmação funciona, registro some, FKs respeitadas
6. **Funcionalidades extras** — filtros, relatórios, ações especiais

**Ordem sugerida de teste (menor → maior acoplamento):**
1. Tags (independente)
2. Clientes (independente)
3. Artes (depende de Tags para seletor)
4. Metas (independente, mas atualizado por Vendas)
5. Vendas (depende de Artes + Clientes + Metas)

### 10.2 Prioridade Alta — Sistema de Estilos Padronizados

**Status:** Projetado e codificado, mas NÃO integrado ao sistema.

Arquivos criados na sessão 31/01 (precisam ser copiados para o projeto):
- `database/migrations/009_create_estilos_arte_table.php` — tabela + 10 estilos padrão
- `database/migrations/010_add_estilo_to_artes.php` — coluna estilo_id em artes + FK + mapeamento automático
- `src/Models/EstiloArte.php` — model completo
- `src/Repositories/EstiloArteRepository.php` — repository com getRentabilidadePorEstilo()
- `src/Services/EstiloArteService.php` — service com análise de rentabilidade
- Trecho para DashboardController — busca dados de rentabilidade
- Trecho para dashboard view — gráfico Chart.js horizontal bar (R$/hora por estilo)
- Atualização de seeds.php — vincular artes existentes a estilos

### 10.3 Prioridade Média

| Feature | Descrição | Esforço |
|---|---|---|
| Upload de Imagens | Campo `imagem` existe na tabela, upload não implementado | Médio |
| Timer de Horas | Tabela `timer_sessoes` existe, UI não implementada | Alto |
| Busca Global | Rota existe como TODO, SearchController não criado | Médio |
| Validações nos forms | Testar e corrigir todas validações client-side e server-side | Médio |
| Flash Messages | Verificar se aparecem corretamente após redirect | Baixo |

### 10.4 Prioridade Baixa (Futuro)

| Feature | Descrição |
|---|---|
| Autenticação | Login/logout, proteção de rotas |
| Exportação | Relatórios em PDF/Excel |
| API REST | Endpoints JSON para integração externa |
| PHPUnit | Testes automatizados unitários + integração |
| Cache | Cache de consultas frequentes (dashboard) |
| Logs | Logs estruturados para debug em produção |

---

## 11. DADOS DE TESTE (seeds.php)

O arquivo `seeds.php` popula o banco com dados realistas:

| Entidade | Quantidade | Detalhes |
|---|---|---|
| Tags | 8 | Chibi, Sketch, Full Body, YCH, PWYW, Bust, Icon, Emote |
| Clientes | 10 | Nomes brasileiros com email, telefone, cidade, estado |
| Artes | 20 | 4 por categoria: Chibi(4), Sketch(4), Full Body(4), YCH(4), PWYW(4) |
| Vendas | 15 | Distribuídas em 3 meses (dez/2025, jan/2026, fev/2026) |
| Metas | 3 | R$ 2.000, R$ 3.000, R$ 3.500 para os 3 meses |
| Arte-Tags | Múltiplas | Associações automáticas por categoria |

**Como executar:** `http://localhost/artflow2/seeds.php` → botão "Executar Seeds"

**Mecanismo de limpeza:** Usa DELETE (não TRUNCATE) + ALTER TABLE AUTO_INCREMENT para evitar constraint violations em FK.

---

## 12. GUIA RÁPIDO PARA CONVERSAS FUTURAS

### Como trabalhar um módulo individual

Ao iniciar conversa sobre um módulo específico (ex: "vamos testar o módulo Artes"):

1. **Referência:** Consultar esta documentação (seção 6.x do módulo)
2. **Dependências:** Verificar o que o módulo precisa que já funcione
3. **Teste:** Testar CRUD completo no navegador, cada operação
4. **Bugs:** Reportar com:
   - URL exata
   - Mensagem de erro completa
   - Screenshot se possível
5. **Correção:** Receber arquivo corrigido individual, testar, confirmar

### Arquivos-chave por módulo

| Para mexer em... | Editar estes arquivos |
|---|---|
| Rotas | `config/routes.php` |
| Lógica de negócio | `src/Services/{Modulo}Service.php` |
| SQL/Consultas | `src/Repositories/{Modulo}Repository.php` |
| Formulários/HTML | `views/{modulo}/create.php`, `edit.php` |
| Listagem/Tabela | `views/{modulo}/index.php` |
| Detalhes | `views/{modulo}/show.php` |
| Validação | `src/Validators/{Modulo}Validator.php` |
| Entidade | `src/Models/{Modulo}.php` |
| Coordenação | `src/Controllers/{Modulo}Controller.php` |

### Comandos úteis

```bash
# Reinstalar tudo do zero
http://localhost/artflow2/install.php

# Popular dados de teste
http://localhost/artflow2/seeds.php

# Rodar testes automatizados
http://localhost/artflow2/tests.php

# Ver logs de erro
C:\xampp\htdocs\artflow2\storage\logs\error.log
```

---

## 13. CHANGELOG

| Data | Sessão | O que foi feito |
|---|---|---|
| 26/01/2026 | Instalação | Arquitetura completa, 5 módulos, 14 bugs corrigidos na instalação |
| 30/01/2026 | CSRF + Vendas | Bug CSRF (_token→_csrf), variáveis incompatíveis no VendaController |
| 31/01/2026 | Type Errors | 3 TypeErrors (MetaRepo, VendaController, Dashboard) |
| 31/01/2026 | Testes | Sistema completo de testes (tests.php) com 7 categorias |
| 31/01/2026 | Seeds | Populador de dados (seeds.php) com dados realistas |
| 31/01/2026 | Dashboard | 4 gráficos Chart.js + Top Clientes + fix infinito |
| 31/01/2026 | Estilos | Feature de estilos padronizados projetada (não integrada) |
| 01/02/2026 | Documentação | Este documento — referência completa e atualizada |

---

*Documento gerado em 01/02/2026 — ArtFlow 2.0 v2.0.0-beta*
