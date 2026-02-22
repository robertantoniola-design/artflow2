# ArtFlow 2.0 — Módulo Vendas: Documentação Completa

**Data:** 21/02/2026  
**Status Geral:** ⏳ NÃO TESTADO NO NAVEGADOR — Próximo módulo a estabilizar  
**Versão Base:** Código existente com correções parciais (01-05/02/2026), CRUD não validado  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Vendas do ArtFlow 2.0 é o **módulo mais acoplado** do sistema — registra transações de venda de artes, calcula lucro e rentabilidade automaticamente, atualiza o status da arte para "vendida" e incrementa o progresso das metas mensais. É o único módulo com 3 dependências no Controller e 4 no Service, orquestrando operações que afetam 3 tabelas simultaneamente (`vendas`, `artes`, `metas`).

O módulo é **pré-requisito** para:
1. **Dashboard completo** — 5 dos 8 dados do Dashboard vêm de Vendas
2. **Cards Lucro + Rentabilidade** do módulo Artes (M5 pendência cross-module)
3. **Metas funcionais** — o `valor_realizado` e `porcentagem_atingida` dependem de vendas registradas

O código já passou por correções parciais em 01-05/02/2026 (extração de campos no store, sanitização, nomes de métodos no Repository), mas **nenhuma operação CRUD foi testada no navegador**. Dado o alto acoplamento, é o módulo com maior probabilidade de bugs sistêmicos.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — testar no navegador e corrigir bugs | 📋 PENDENTE |
| Melhoria 1 | Paginação na listagem (12/página) | 📋 PLANEJADA |
| Melhoria 2 | Ordenação dinâmica (data, valor, cliente, forma pgto) | 📋 PLANEJADA |
| Melhoria 3 | Filtros combinados (período + cliente + forma pgto) | 📋 PLANEJADA |
| Melhoria 4 | Relatório aprimorado (resumo financeiro + exportação) | 📋 PLANEJADA |
| Melhoria 5 | Estatísticas por venda (cards métricas no show.php) | 📋 PLANEJADA |
| Melhoria 6 | Gráficos de vendas (Chart.js — faturamento + ranking) | 📋 PLANEJADA |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | Fase 1 ✅ | 📋 PLANEJADA |
| 2 | Ordenação dinâmica (5+ colunas) | Baixa | Melhoria 1 | 📋 PLANEJADA |
| 3 | Filtros combinados (período + cliente + pgto) | Média | Melhoria 1 | 📋 PLANEJADA |
| 4 | Relatório aprimorado + exportação | Média | Fase 1 ✅ | 📋 PLANEJADA |
| 5 | Estatísticas por venda (cards no show.php) | Média | Fase 1 ✅ | 📋 PLANEJADA |
| 6 | Gráficos de vendas (Chart.js) | Baixa | Fase 1 ✅ | 📋 PLANEJADA |

### ⚠️ PENDÊNCIA CROSS-MODULE (Artes ↔ Vendas)

| Pendência | Origem | Onde implementar | Condição |
|-----------|--------|------------------|----------|
| Card **Lucro** no Artes show.php | Artes M5 | ArteService + views/artes/show.php | Após Vendas Fase 1 OK |
| Card **Rentabilidade** no Artes show.php | Artes M5 | ArteService + views/artes/show.php | Após Vendas Fase 1 OK |

**Detalhes:** Os cards de Lucro (`preço_venda - preço_custo`) e Rentabilidade (`lucro / horas_trabalhadas`) na página de detalhes de uma arte dependem de uma query na tabela `vendas`. TODOs estão marcados no código do módulo Artes (`ArteService::getMetricasArte()` e `views/artes/show.php`).

**Implementação (após Vendas Fase 1 OK):**
1. `ArteService::calcularLucro(Arte $arte)` — query `SELECT valor FROM vendas WHERE arte_id = ?`
2. `ArteService::calcularRentabilidade(Arte $arte)` — `lucro / horas_trabalhadas`
3. +2 cards extras no `views/artes/show.php` (reorganizar de 3 para 5 cards)
4. Condição: só exibir quando `$arte->getStatus() === 'vendida'`

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Venda.php                      ✅ Implementado (getters/setters + Arte/Cliente relacionados)
├── Repositories/
│   └── VendaRepository.php            ✅ Implementado (CRUD + filtros + estatísticas + relatórios)
├── Services/
│   └── VendaService.php               ✅ Implementado (orquestra 3 Repos + cálculos + metas)
├── Controllers/
│   └── VendaController.php            ✅ Implementado (CRUD + relatório + correções 01/02)
└── Validators/
    └── VendaValidator.php             ✅ Implementado (arte_id, valor, data, forma_pgto)

views/
└── vendas/
    ├── index.php                      ✅ Existe (lista com filtros e resumo)
    ├── create.php                     ✅ Existe (selects arte + cliente + campos)
    ├── show.php                       ✅ Existe (detalhes da venda)
    ├── edit.php                       ✅ Existe (edição com restrições)
    └── relatorio.php                  ✅ Existe (relatório com filtros de período)
```

### Dependências entre Classes (MÓDULO MAIS ACOPLADO)

```
VendaController
├── __construct(VendaService, ArteService, ClienteService)  ← 3 dependências!
│
├── index()     usa VendaService::listar() + getEstatisticas() + ClienteService::getParaSelect()
├── create()    usa ArteService::getDisponiveisParaVenda() + ClienteService::getParaSelect()
├── store()     usa VendaService::registrar() [orquestra 3 tabelas]
├── show()      usa VendaService::buscar()
├── edit()      usa VendaService::buscar() + ClienteService::getParaSelect()
├── update()    usa VendaService::atualizar()
├── destroy()   usa VendaService::excluir() [recalcula meta]
└── relatorio() usa VendaService::getVendasMensais() + getEstatisticas() + getRankingRentabilidade()

VendaService ← ORQUESTRA 3 REPOSITORIES
├── VendaRepository   — CRUD vendas
├── ArteRepository    — buscar arte + atualizar status → 'vendida'
├── MetaRepository    — incrementar/recalcular meta do mês
└── VendaValidator    — validação de dados
```

### Fluxo Principal: Registrar Venda

```
POST /vendas → VendaController::store()
  │
  ├─► 1. Sanitiza dados (cliente_id vazio → null)
  ├─► 2. VendaValidator::validate() — campos obrigatórios + tipos
  ├─► 3. ArteRepository::findOrFail() — busca arte
  ├─► 4. VendaValidator::validateArteDisponivel() — status != 'vendida'
  ├─► 5. Calcula: lucro = valor - arte.preco_custo
  ├─► 6. Calcula: rentabilidade = lucro / arte.horas_trabalhadas
  ├─► 7. VendaRepository::create() — INSERT na tabela vendas
  ├─► 8. ArteRepository::update(arte_id, ['status' => 'vendida'])
  └─► 9. MetaRepository::incrementarRealizado(mes_ano, valor)
```

### Fluxo: Excluir Venda

```
DELETE /vendas/{id} → VendaController::destroy()
  │
  ├─► 1. VendaService::buscar($id) — busca venda
  ├─► 2. VendaService::excluir($id) — remove registro
  └─► 3. VendaService::recalcularMetaMes() — re-soma vendas do mês
         ├─► VendaRepository::getTotalVendasMes()
         └─► MetaRepository::atualizarProgresso()
```

**Nota:** A exclusão NÃO reverte o status da arte automaticamente. Se a venda for excluída, a arte permanece com status 'vendida'. Isso pode ser um bug ou decisão de design — verificar na Fase 1.

### Tabela `vendas` (Banco de Dados)

```sql
CREATE TABLE vendas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arte_id INT UNSIGNED NULL,                          -- FK → artes(id) SET NULL
    cliente_id INT UNSIGNED NULL,                       -- FK → clientes(id) SET NULL
    valor DECIMAL(10,2) NOT NULL,                       -- Valor da venda em R$
    data_venda DATE NOT NULL,                           -- Data da transação
    lucro_calculado DECIMAL(10,2) NULL,                 -- valor - preco_custo (calculado no Service)
    rentabilidade_hora DECIMAL(10,2) NULL,              -- lucro / horas_trabalhadas (calculado)
    forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito','transferencia','outro')
                    DEFAULT 'pix',                      -- Método de pagamento
    observacoes TEXT NULL,                               -- Notas da venda
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (arte_id) REFERENCES artes(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,

    INDEX idx_vendas_data (data_venda),
    INDEX idx_vendas_arte (arte_id),
    INDEX idx_vendas_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Comportamento das FKs (SET NULL):** Se a arte ou cliente for excluído, a venda permanece no histórico. Os campos `arte_id` e `cliente_id` ficam NULL mas os dados financeiros (`valor`, `lucro_calculado`, `rentabilidade_hora`) são preservados.

### Campos do Formulário (create.php / edit.php)

| Campo | Tipo HTML | Validação | Obrigatório | Notas |
|-------|-----------|-----------|-------------|-------|
| arte_id | select | exists:artes, status != vendida | ✅ | Lista artes disponíveis (não vendidas) |
| cliente_id | select | exists:clientes (se fornecido) | ❌ | Venda pode ser sem cliente identificado |
| valor | number (step 0.01) | min:0.01, max:9999999.99 | ✅ | Em R$ |
| data_venda | date | date_format Y-m-d | ✅ | Default: hoje |
| forma_pagamento | select | in:dinheiro,pix,cartao_credito,cartao_debito,transferencia,outro | ✅ | Default: pix |
| observacoes | textarea | — | ❌ | Notas livres |

**Nota sobre edit:** Na edição, `arte_id` NÃO pode ser alterado (arte já marcada como vendida). Apenas `cliente_id`, `valor`, `data_venda`, `forma_pagamento` e `observacoes` são editáveis.

### Rotas (8 total)

```
VENDAS (7 RESTful + 1 extra)
  GET    /vendas/relatorio   → VendaController@relatorio   (ANTES do resource! Senão "relatorio" = {id})
  GET    /vendas             → VendaController@index        (lista com filtros + resumo)
  GET    /vendas/criar       → VendaController@create       (formulário com selects arte/cliente)
  POST   /vendas             → VendaController@store        (registra + calcula lucro + atualiza meta)
  GET    /vendas/{id}        → VendaController@show         (detalhes com arte + cliente)
  GET    /vendas/{id}/editar → VendaController@edit         (edição — arte_id fixo)
  PUT    /vendas/{id}        → VendaController@update       (atualiza dados editáveis)
  DELETE /vendas/{id}        → VendaController@destroy      (exclui + recalcula meta)
```

**REGRA CRÍTICA:** A rota `/vendas/relatorio` DEVE ser declarada ANTES de `$router->resource('/vendas')`, senão o Router interpreta "relatorio" como `{id}` numérico e chama `show()`.

### Variáveis do Controller para Views

```php
// index.php espera:
'vendas'          => array de Venda/array (listagem)
'estatisticas'    => array (totais gerais)
'clientesSelect'  => array de Cliente (para filtro por cliente)
'resumo'          => ['total_vendas', 'valor_total', 'lucro_total']
'filtros'         => array (filtros ativos)

// create.php espera:
'artesDisponiveis'    => array de Arte (status != 'vendida')
'clientesSelect'      => array de Cliente (todos)
'arteSelecionada'     => int|null (pré-seleção via URL ?arte_id=X)
'clienteSelecionado'  => int|null (pré-seleção via URL ?cliente_id=X)

// show.php espera:
'venda' => Venda (com Arte e Cliente hydrated via findWithRelations)

// edit.php espera:
'venda'          => Venda
'clientesSelect' => array de Cliente

// relatorio.php espera:
'relatorio'              => array composto
'vendasMensais'          => array (dados para gráfico)
'estatisticas'           => array (totais)
'rankingRentabilidade'   => array (top 10 mais rentáveis)
'filtros'                => ['mes', 'ano']
```

---

## 📋 FASE 1 — ESTABILIZAÇÃO CRUD (PENDENTE)

**Status:** 📋 PENDENTE — Nenhum teste no navegador realizado  
**Metodologia:** Mesmo padrão de Artes/Clientes/Tags — 12 testes no navegador + correções

### Checklist de Testes

| # | Operação | Rota | O que verificar | Status |
|---|----------|------|-----------------|--------|
| T1 | Listar | `GET /vendas` | Carrega sem erros, exibe vendas dos seeds com arte/cliente | ⬜ |
| T2 | Criar (form) | `GET /vendas/criar` | Formulário abre, selects de artes e clientes populados | ⬜ |
| T3 | Criar (salvar) | `POST /vendas` | Validação funciona, salva com lucro calculado | ⬜ |
| T4 | Verificar arte | — | Após T3: arte muda para status 'vendida' | ⬜ |
| T5 | Verificar meta | — | Após T3: meta do mês incrementa valor_realizado | ⬜ |
| T6 | Visualizar | `GET /vendas/{id}` | Exibe dados + arte + cliente + lucro + rentabilidade | ⬜ |
| T7 | Editar (form) | `GET /vendas/{id}/editar` | Campos preenchidos, arte_id fixo (não editável) | ⬜ |
| T8 | Editar (salvar) | `PUT /vendas/{id}` | Atualiza valor/data/pgto, recalcula lucro se valor mudou | ⬜ |
| T9 | Excluir | `DELETE /vendas/{id}` | Confirmação funciona, venda removida | ⬜ |
| T10 | Verificar meta excl | — | Após T9: meta do mês recalculada (decrementada) | ⬜ |
| T11 | Relatório | `GET /vendas/relatorio` | Página carrega sem erros, dados corretos | ⬜ |
| T12 | Validação | `POST /vendas` com dados inválidos | Erros exibidos corretamente | ⬜ |

### Bugs Potenciais a Investigar (baseados em padrões dos outros módulos)

| # | Bug Potencial | Onde verificar | Padrão de referência |
|---|--------------|----------------|----------------------|
| V1 | **B8 — Validação Invisível** | VendaController store/update | Mesmo de Artes/Clientes — `$_SESSION['_errors']` vs `$_SESSION['_flash']` |
| V2 | **B9 — Dados Residuais** | VendaController edit/index | Falta `limparDadosFormulario()` em métodos de leitura |
| V3 | **Conversão string→int** | VendaController todos os métodos com $id | Router passa string, Service espera int |
| V4 | **Filtros mutuamente exclusivos** | VendaService::listar() | Usa if/elseif em vez de AND combinado |
| V5 | **Arte não-disponível no select** | views/vendas/create.php | Se todas as artes são 'vendida', select fica vazio sem mensagem |
| V6 | **update() não recalcula meta** | VendaService::atualizar() | Se valor muda, meta do mês não é atualizada |
| V7 | **destroy() não reverte arte** | VendaService::excluir() | Arte permanece 'vendida' após excluir venda |
| V8 | **Relatório como {id}** | config/routes.php | Se rota relatorio está DEPOIS do resource, "relatorio" vira $id |
| V9 | **show() sem relacionamentos** | VendaController::show() | Se usa findOrFail() em vez de findWithRelations(), arte_nome/cliente_nome ficam null |
| V10 | **Nome da variável na view** | create.php/edit.php | Bug já corrigido na doc: 'artes'/'clientes' vs 'artesDisponiveis'/'clientesSelect' |

### Análise de Risco por Camada

```
ALTO RISCO (mais provável de ter bugs):
├── VendaController — B8/B9 workarounds provavelmente faltam
├── VendaService::listar() — filtros mutuamente exclusivos (if/elseif)
├── VendaService::excluir() — não reverte status da arte
└── Views — nomenclatura de variáveis pode não bater

MÉDIO RISCO:
├── VendaService::registrar() — fluxo complexo (7 passos), já corrigido parcialmente
├── VendaRepository — queries parecem corretas mas não testadas
└── VendaValidator — validação básica, pode faltar validateArteDisponivel no update

BAIXO RISCO:
├── Venda Model — getters/setters simples + fromArray/toArray
├── Migration — tabela já existe e é usada por seeds
└── Rotas — /vendas/relatorio já documentada como "antes do resource"
```

---

## 📌 CÓDIGO EXISTENTE — ANÁLISE DETALHADA

### VendaController (já implementado)

**Correções já aplicadas (01/02/2026):**
- `store()` extrai forma_pagamento e observacoes do form
- `store()` sanitiza cliente_id vazio → null
- `store()` catch para DatabaseException com mensagem útil
- `update()` mesma sanitização do store()

**Potenciais problemas (a verificar na Fase 1):**
- **Falta B8 workaround:** Não grava `$_SESSION['_errors']` direto — provavelmente usa `Response::withErrors()` que grava em `$_SESSION['_flash']` (validação invisível)
- **Falta B9:** Não tem `limparDadosFormulario()` em `index()`, `show()`, `edit()`
- **Falta conversão int:** `$id` do Router chega como string — precisa `(int) $id`
- **show()** pode estar usando `findOrFail()` em vez de `findWithRelations()` — perdendo arte_nome e cliente_nome

### VendaService (já implementado)

**Correções já aplicadas (05/02/2026):**
- `getVendasMensais()` agora chama `getVendasPorMes()` (nome correto)
- `getRankingRentabilidade()` agora chama `getMaisRentaveis()` (nome correto)
- `registrar()` inclui forma_pagamento e observacoes no INSERT

**Potenciais problemas:**
- **listar()** usa if/elseif mutuamente exclusivo — não combina filtros
- **excluir()** recalcula meta MAS não reverte status da arte
- **atualizar()** não recalcula meta se valor mudou

### VendaRepository (já implementado)

**Métodos existentes:**
```
allWithRelations()          — lista com JOIN arte + cliente (retorna objetos)
findWithRelations(id)       — busca com JOIN (hydrata Arte/Cliente no Venda)
findByPeriodo(inicio, fim)  — WHERE BETWEEN
findByMesAno(ano, mes)      — WHERE YEAR/MONTH
findByMes(mesAno)           — alias para findByMesAno com parse
findByCliente(clienteId)    — WHERE cliente_id
getRecentes(limit)          — ORDER BY data_venda DESC (retorna arrays)
getVendasPorMes(meses)      — GROUP BY mês com SUM (para gráficos)
getMaisRentaveis(limit)     — ORDER BY rentabilidade_hora DESC
getTotalVendasMes(mesAno)   — SUM(valor) do mês
getEstatisticas()           — totais gerais
findPaginated(filtros)      — paginação básica com filtros (já existe!)
```

**Nota importante:** O `getRecentes()` retorna **arrays brutos** (não objetos Venda), enquanto `findByPeriodo()` e `findByCliente()` retornam **objetos hydrated**. Isso pode causar inconsistência na view se o tipo muda conforme o filtro.

### VendaValidator (já implementado)

**Validações existentes:**
- `arte_id`: required, integer, > 0
- `cliente_id`: optional, integer, > 0
- `valor`: required, numeric, > 0, < 9999999.99
- `data_venda`: required, date format
- `forma_pagamento`: in:dinheiro,pix,cartao_credito,cartao_debito,transferencia,outro
- `validateArteDisponivel($status)`: verifica status != 'vendida'

### Venda Model (já implementado)

**Propriedades:** id, arte_id, cliente_id, valor, data_venda, lucro_calculado, rentabilidade_hora, forma_pagamento, observacoes, created_at, updated_at

**Relacionamentos carregados:** `$arte` (Arte|null), `$cliente` (Cliente|null) — populados por `findWithRelations()`

**Constantes:** PAGAMENTO_DINHEIRO, PAGAMENTO_PIX, PAGAMENTO_CREDITO, PAGAMENTO_DEBITO, PAGAMENTO_TRANSFERENCIA, PAGAMENTO_OUTRO

**Métodos úteis:** `getFormaPagamentoLabel()` retorna label legível ("Cartão de Crédito", etc.)

---

## 📋 MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (PLANEJADA)

**Complexidade:** Baixa  
**Padrão:** Idêntico a Tags, Clientes e Artes (12 itens por página)  
**Pré-requisito:** Fase 1 ✅

### Especificação

| Recurso | Descrição |
|---------|-----------|
| **12 vendas por página** | Controles Bootstrap 5 com janela de 5 páginas |
| **Preservação de filtros** | Período + cliente + forma pgto mantidos ao paginar |
| **Indicador** | "Mostrando X–Y de Z vendas" |
| **Helper URL** | `vendaUrl()` para montar URLs preservando parâmetros |

### Nota

O `VendaRepository::findPaginated()` já existe com paginação básica! Pode ser aproveitado/adaptado para o padrão `allPaginated()` + `countAll()` dos outros módulos.

### Arquivos a Alterar

| Arquivo | Ação |
|---------|------|
| VendaRepository | Adaptar `findPaginated()` ou criar `allPaginated()` + `countAll()` |
| VendaService | Criar `listarPaginado($filtros)` retornando `['vendas' => [...], 'paginacao' => [...]]` |
| VendaController | index() usa `listarPaginado()` |
| views/vendas/index.php | + controles de paginação + helper vendaUrl() |

---

## 📋 MELHORIA 2 — ORDENAÇÃO DINÂMICA (PLANEJADA)

**Complexidade:** Baixa  
**Padrão:** Idêntico a Tags, Clientes e Artes (headers clicáveis)  
**Pré-requisito:** Melhoria 1 ✅

### Colunas Ordenáveis

| Coluna | Campo BD | Direção padrão | Tipo ícone |
|--------|----------|----------------|------------|
| Data | data_venda | DESC (recentes) — **PADRÃO** | bi-sort-down/up |
| Arte | arte_nome (via JOIN) | ASC (A→Z) | bi-sort-alpha-down/up |
| Cliente | cliente_nome (via JOIN) | ASC (A→Z) | bi-sort-alpha-down/up |
| Valor | valor | DESC (maior primeiro) | bi-sort-numeric-down/up |
| Lucro | lucro_calculado | DESC (maior primeiro) | bi-sort-numeric-down/up |
| Forma Pgto | forma_pagamento | ASC (ordem ENUM) | bi-sort-alpha-down/up |

### Arquivos a Alterar

| Arquivo | Ação |
|---------|------|
| VendaRepository | Whitelist de colunas + ORDER BY dinâmico no `allPaginated()` |
| views/vendas/index.php | + headers clicáveis + helpers `vendaSortUrl()` e `vendaSortIcon()` |

---

## 📋 MELHORIA 3 — FILTROS COMBINADOS (PLANEJADA)

**Complexidade:** Média  
**Padrão:** WHERE dinâmico com AND (mesmo de Artes M1/M3)  
**Pré-requisito:** Melhoria 1 ✅

### Filtros Combinados

| Filtro | Tipo | Campo BD | UI |
|--------|------|----------|-----|
| Período | date range | data_venda BETWEEN | 2 inputs date |
| Cliente | select | cliente_id = ? | Dropdown com clientes |
| Forma pagamento | select | forma_pagamento = ? | Dropdown com 6 opções |
| Busca (termo) | text | arte_nome LIKE ou observacoes LIKE | Input text |

**Problema atual:** `VendaService::listar()` usa if/elseif mutuamente exclusivo. A M3 deve converter para WHERE dinâmico com AND, idêntico ao padrão de Artes.

### Arquivos a Alterar

| Arquivo | Ação |
|---------|------|
| VendaRepository | `allPaginated()` com WHERE dinâmico (AND) + JOINs para filtro por nome |
| VendaService | `listarPaginado()` normaliza filtros com `?? null ?: null` |
| VendaController | index() extrai todos os filtros |
| views/vendas/index.php | Barra de filtros com 4 campos + botão "Limpar" |

---

## 📋 MELHORIA 4 — RELATÓRIO APRIMORADO (PLANEJADA)

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅  
**Rota:** `GET /vendas/relatorio` (já existe)

### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Filtro por período** | Data início + data fim |
| **Filtro por ano** | Dropdown com anos disponíveis |
| **Cards de resumo** | Total vendas, Faturamento, Lucro total, Ticket médio, Margem média |
| **Tabela detalhada** | Lista de vendas do período com arte, cliente, valor, lucro |
| **Comparativo mensal** | Tabela mês a mês com evolução |

### Nota

O `relatorio()` já existe no Controller e chama 3 métodos do Service. A melhoria focaria em enriquecer a view e adicionar filtros mais robustos.

---

## 📋 MELHORIA 5 — ESTATÍSTICAS POR VENDA (PLANEJADA)

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅

### Cards de Métricas no show.php

| Card | Dado | Cálculo | Condição |
|------|------|---------|----------|
| **Margem de Lucro** | % de lucro sobre valor | `(lucro / valor) × 100` | Sempre |
| **Rentabilidade/Hora** | R$/hora de lucro | `lucro / horas_trabalhadas` (da arte) | Se arte com horas > 0 |
| **Comparativo** | vs média de vendas | valor da venda vs ticket médio geral | Sempre |
| **Posição no Ranking** | X° mais rentável | Ranking entre todas as vendas | Se rentabilidade > 0 |

### Dados Adicionais

- **Arte vendida:** Card com miniatura, nome, custo original, complexidade
- **Cliente:** Card com nome, email, total de compras do cliente
- **Timeline:** Datas relevantes (criação da arte, venda, tempo de estoque)

---

## 📋 MELHORIA 6 — GRÁFICOS DE VENDAS (PLANEJADA)

**Complexidade:** Baixa  
**Pré-requisito:** Fase 1 ✅  
**Biblioteca:** Chart.js 4.4.7 via CDN (mesmo padrão Tags/Metas/Artes)

### Gráficos no index.php

| Gráfico | Tipo Chart.js | Dados | Localização |
|---------|--------------|-------|-------------|
| **Faturamento Mensal** | Barras verticais | SUM(valor) por mês (últimos 6-12 meses) | index.php (topo) |
| **Forma de Pagamento** | Doughnut | COUNT por forma_pagamento | index.php (topo) |

### Cards de Resumo no index.php

| Indicador | Cálculo |
|-----------|---------|
| **Total de Vendas** | COUNT(*) |
| **Faturamento Total** | SUM(valor) |
| **Lucro Total** | SUM(lucro_calculado) |
| **Ticket Médio** | AVG(valor) ou SUM/COUNT |

### Gráficos no relatorio.php (Melhoria 4+6 combinadas)

| Gráfico | Tipo | Dados |
|---------|------|-------|
| **Evolução Mensal** | Barras + Linha | Faturamento (barras) + Quantidade (linha) |
| **Top 5 Rentáveis** | Barras horizontais | Ordenado por rentabilidade_hora |
| **Meta vs Realizado** | Barras duplas | Valor meta vs realizado mês a mês |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS (Aplicar na Fase 1)

### Bug B8: Validação Invisível

**Status no módulo Vendas:** ⚠️ PROVAVELMENTE NÃO CORRIGIDO  
**Ação:** Aplicar mesmo workaround dos outros módulos — gravar direto em `$_SESSION['_errors']` no Controller.

### Bug B9: Dados Residuais no Edit

**Status no módulo Vendas:** ⚠️ PROVAVELMENTE NÃO CORRIGIDO  
**Ação:** Adicionar `limparDadosFormulario()` em `index()`, `show()`, `edit()`. NUNCA em `create()`.

### Conversão string→int do Router

**Status no módulo Vendas:** ⚠️ PROVAVELMENTE NÃO CORRIGIDO  
**Ação:** Adicionar `$id = (int) $id` em `show()`, `edit()`, `update()`, `destroy()`.

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos do VendaService chamados no Controller

| Método no Controller | Existe no Service? | Observação |
|---------------------|--------------------|------------|
| `listar($filtros)` | ✅ | Filtros mutuamente exclusivos — corrigir M3 |
| `buscar($id)` | ✅ | Usa `findOrFail()` — verificar se retorna com relacionamentos |
| `registrar($dados)` | ✅ | Fluxo de 7 passos — já corrigido parcialmente |
| `atualizar($id, $dados)` | ✅ | Verificar se recalcula meta/lucro |
| `excluir($id)` | ✅ | Recalcula meta — verificar se reverte status da arte |
| `getEstatisticas()` | ✅ | Delega ao Repository |
| `getVendasMensais($meses)` | ✅ | Corrigido: chama `getVendasPorMes()` |
| `getRankingRentabilidade($limite)` | ✅ | Corrigido: chama `getMaisRentaveis()` |
| `getTotalMes($mesAno)` | ✅ | Chamado pelo Dashboard |

### Métodos do Service usados pelo Dashboard

| Método | Chamado por | Retorno |
|--------|------------|---------|
| `getVendasMesAtual()` | DashboardController | array de Venda |
| `getTotalMes()` | DashboardController | float |
| `getVendasMensais(6)` | DashboardController | array para gráfico |
| `getRankingRentabilidade(5)` | DashboardController | array top 5 |

---

## 📌 PADRÕES A APLICAR (Lições dos Módulos Anteriores)

| Padrão | Origem | Aplicação em Vendas |
|--------|--------|---------------------|
| B8 workaround (`$_SESSION['_errors']` direto) | Clientes/Artes | Fase 1 — Controller |
| B9 workaround (`limparDadosFormulario()`) | Clientes/Artes | Fase 1 — Controller |
| Conversão `(int) $id` | Artes (Router bug) | Fase 1 — Controller |
| Normalização filtros `?? null ?: null` | Artes (T1) | Melhoria 3 — Service |
| Paginação 12/página + helper URL | Tags/Clientes/Artes | Melhoria 1 |
| Headers clicáveis + whitelist ORDER BY | Tags/Clientes/Artes | Melhoria 2 |
| WHERE dinâmico com AND | Artes M1/M3 | Melhoria 3 |
| Chart.js 4.4.7 + container 280px fixo | Tags/Metas/Artes | Melhoria 6 |
| Collapse com chart.resize() | Artes M6 | Melhoria 6 |
| Fallback banco vazio ($temDadosGrafico) | Artes M6 | Melhoria 6 |

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                         → COMPLETO (6/6)
2. ✅ Clientes     — independente                         → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)  → COMPLETO (6/6)
4. ✅ Artes        — depende de Tags (✅)                   → COMPLETO (6/6)
5. 🎯 VENDAS      — depende de Artes + Clientes + Metas  → FASE 1 PENDENTE ★
6. 🔄 Dashboard   — depende de TODOS                     → Funcional, revisitar após Vendas
```

### Impacto de Vendas em Outros Módulos

```
Vendas → Artes:
  ├── store() → ArteRepository::update(status='vendida')
  ├── Artes M5 pendência → cards Lucro/Rentabilidade dependem de query em vendas
  └── destroy() → ⚠️ NÃO reverte status da arte (verificar)

Vendas → Metas:
  ├── store() → MetaRepository::incrementarRealizado()
  ├── destroy() → recalcularMetaMes() (re-soma vendas)
  └── Metas M1 (superado) → transição automática depende do valor acumulado

Vendas → Dashboard:
  ├── Faturamento Mensal (gráfico barras)
  ├── Vendas do Mês (card)
  ├── Evolução de Vendas (gráfico linha+barras)
  ├── Ranking Rentabilidade (top 5)
  └── Top Clientes (via JOIN com clientes)

Vendas → Clientes:
  └── Histórico de compras no ClienteController::show()
```

---

## 📌 SEQUÊNCIA RECOMENDADA

```
FASE 1 — Estabilização CRUD
├── Passo 1: Testar T1-T12 no navegador
├── Passo 2: Corrigir bugs encontrados (B8, B9, conversão int, etc.)
├── Passo 3: Validar fluxo completo (venda → arte vendida → meta atualizada)
└── Passo 4: Regressão — dashboard ainda funciona?

PÓS-FASE 1 — Implementar pendência Artes
└── Cards Lucro + Rentabilidade no Artes show.php

MELHORIAS (sequência recomendada)
├── M1: Paginação (base para M2 e M3)
├── M2: Ordenação (depende de M1)
├── M3: Filtros combinados (depende de M1)
├── M5: Estatísticas show.php (independente)
├── M6: Gráficos index.php (independente)
└── M4: Relatório aprimorado (pode incorporar gráficos de M6)
```

---

**Última atualização:** 21/02/2026  
**Status:** ⏳ NÃO TESTADO — CRUD nunca validado no navegador  
**Próxima ação:** Fase 1 — Teste T1-T12 no navegador + correção de bugs  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅
