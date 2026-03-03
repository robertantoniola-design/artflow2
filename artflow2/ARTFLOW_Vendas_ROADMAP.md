# ArtFlow 2.0 — Módulo Vendas: Documentação Completa

**Data:** 03/03/2026  
**Status Geral:** ✅ COMPLETO — 6/6 MELHORIAS IMPLEMENTADAS  
**Versão Base:** Código corrigido na Fase 1 (22/02/2026) + Todas as melhorias (23/02 – 03/03/2026)  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Vendas do ArtFlow 2.0 é o **módulo mais acoplado** do sistema — registra transações de venda de artes, calcula lucro e rentabilidade automaticamente, atualiza o status da arte para "vendida" e incrementa o progresso das metas mensais. É o único módulo com 3 dependências no Controller e 4 no Service, orquestrando operações que afetam 3 tabelas simultaneamente (`vendas`, `artes`, `metas`).

O módulo é **pré-requisito** para:
1. **Dashboard completo** — 5 dos 8 dados do Dashboard vêm de Vendas
2. ~~**Cards Lucro + Rentabilidade** do módulo Artes (M5 pendência cross-module)~~ ✅ IMPLEMENTADO (22/02/2026)
3. **Metas funcionais** — o `valor_realizado` e `porcentagem_atingida` dependem de vendas registradas

A Fase 1 foi concluída em 22/02/2026 com **6 bugs corrigidos** e **12/12 testes manuais OK**. As melhorias M1+M2+M3 foram implementadas em 23/02/2026 com **14/14 testes OK**. As melhorias M5+M6 foram implementadas em 28/02/2026 com **12/12 testes OK** (incluindo bug fix de cliente telefone no findWithRelations). A melhoria M4 (Relatório Aprimorado) foi implementada em 03/03/2026 com **10/10 testes OK**.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 6 bugs corrigidos, 12/12 testes | ✅ COMPLETA (22/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | ✅ COMPLETA (23/02/2026) |
| Melhoria 2 | Ordenação dinâmica (7 colunas clicáveis) | ✅ COMPLETA (23/02/2026) |
| Melhoria 3 | Filtros combinados (termo + cliente + pgto + período) | ✅ COMPLETA (23/02/2026) |
| Melhoria 4 | Relatório aprimorado (filtros + gráficos + tabelas) | ✅ COMPLETA (03/03/2026) |
| Melhoria 5 | Estatísticas por venda (4 mini-cards no show.php) | ✅ COMPLETA (28/02/2026) |
| Melhoria 6 | Gráficos de vendas (Chart.js — barras + doughnut) | ✅ COMPLETA (28/02/2026) |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | Fase 1 ✅ | ✅ COMPLETA (23/02) |
| 2 | Ordenação dinâmica (7 colunas) | Baixa | Melhoria 1 ✅ | ✅ COMPLETA (23/02) |
| 3 | Filtros combinados (termo + cliente + pgto + período) | Média | Melhoria 1 ✅ | ✅ COMPLETA (23/02) |
| 4 | Relatório aprimorado (filtros + gráficos + tabelas) | Média | M5+M6 ✅ | ✅ COMPLETA (03/03) |
| 5 | Estatísticas por venda (cards no show.php) | Média | Fase 1 ✅ | ✅ COMPLETA (28/02) |
| 6 | Gráficos de vendas (Chart.js no index.php) | Baixa | Fase 1 ✅ | ✅ COMPLETA (28/02) |

### ✅ PENDÊNCIA CROSS-MODULE RESOLVIDA (Artes ↔ Vendas)

| Pendência | Origem | Onde implementado | Status |
|-----------|--------|-------------------|--------|
| Card **Lucro** no Artes show.php | Artes M5 | ArteService + views/artes/show.php | ✅ COMPLETO (22/02/2026) |
| Card **Rentabilidade** no Artes show.php | Artes M5 | ArteService + views/artes/show.php | ✅ COMPLETO (22/02/2026) |

**Implementação realizada:**
1. `ArteService::getDadosVenda(Arte)` — método privado, busca venda via `findFirstBy('arte_id', $id)`
2. `ArteService::calcularLucro(Arte)` — retorna `valor_venda`, `lucro`, `margem_percentual`
3. `ArteService::calcularRentabilidade(Arte)` — retorna R$/hora (recalcula com horas atuais)
4. `ArteService::getMetricasArte()` agora retorna 5 métricas (antes 3)
5. `VendaRepository` adicionado como dependência do ArteService (auto-wiring resolve)
6. Cards condicionais na view: só aparecem quando `$arte->getStatus() === 'vendida'`

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Venda.php                      ✅ Implementado (getters/setters + Arte/Cliente relacionados)
├── Repositories/
│   └── VendaRepository.php            ✅ Fase 1 + M1+M2+M3 + M5 + M6 + M4
├── Services/
│   └── VendaService.php               ✅ Fase 1 + M1 + M5 + M6 + M4
├── Controllers/
│   └── VendaController.php            ✅ Fase 1 + M1+M2+M3 + M5 + M6 + M4
└── Validators/
    └── VendaValidator.php             ✅ Implementado (arte_id, valor, data, forma_pgto)

views/
└── vendas/
    ├── index.php                      ✅ M1+M2+M3+M6 (paginação + filtros + ordenação + gráficos)
    ├── create.php                     ✅ Funcional (selects arte + cliente + campos)
    ├── show.php                       ✅ M5 (detalhes + 4 mini-cards estatísticas)
    ├── edit.php                       ✅ Funcional (edição com arte_id fixo)
    └── relatorio.php                  ✅ M4 (reescrito: filtros + 5 cards + gráficos + tabelas)
```

### Dependências entre Classes (MÓDULO MAIS ACOPLADO)

```
VendaController
├── __construct(VendaService, ArteService, ClienteService)  ← 3 dependências!
│
├── index()     usa VendaService::listarPaginado() + getEstatisticas() + getVendasMensais()
│               + getDistribuicaoFormaPagamento() + ClienteService::getParaSelect()  [M1+M2+M3+M6]
├── create()    usa ArteService::getDisponiveisParaVenda() + ClienteService::getParaSelect()
├── store()     usa VendaService::registrar() [orquestra 3 tabelas]
├── show()      usa VendaService::buscarComRelacionamentos() + getEstatisticasVenda()  [V9+M5]
├── edit()      usa VendaService::buscarComRelacionamentos() + ClienteService::getParaSelect()
├── update()    usa VendaService::atualizar()
├── destroy()   usa VendaService::excluir() [V7 fix: reverte arte + recalcula meta]
└── relatorio() usa VendaService::getDadosRelatorio()  [M4: orquestra 5 queries]

VendaService ← ORQUESTRA 3 REPOSITORIES
├── VendaRepository   — CRUD vendas + allPaginated/countAll + stats/gráficos + relatório
├── ArteRepository    — buscar arte + atualizar status → 'vendida' / 'disponivel'
├── MetaRepository    — incrementar/recalcular meta do mês
└── VendaValidator    — validação de dados
```

### Fluxo Principal: Registrar Venda

```
POST /vendas → VendaController::store()
  │
  ├─► 1. Sanitiza dados (cliente_id vazio → null, observacoes vazia → null)
  ├─► 2. VendaValidator::validate() — campos obrigatórios + tipos
  ├─► 3. ArteRepository::findOrFail() — busca arte
  ├─► 4. VendaValidator::validateArteDisponivel() — status != 'vendida'
  ├─► 5. Calcula: lucro = valor - arte.preco_custo
  ├─► 6. Calcula: rentabilidade = lucro / arte.horas_trabalhadas
  ├─► 7. VendaRepository::create() — INSERT na tabela vendas
  ├─► 8. ArteRepository::update(arte_id, ['status' => 'vendida'])
  └─► 9. MetaRepository: recalcularMetaMes() via findByMesAno()
```

### Fluxo: Excluir Venda (CORRIGIDO V7)

```
DELETE /vendas/{id} → VendaController::destroy()
  │
  ├─► 1. VendaService::buscar($id) — busca venda
  ├─► 2. ArteRepository::update(arte_id, ['status' => 'disponivel'])  ← V7 FIX
  ├─► 3. VendaRepository::delete($id) — remove registro
  └─► 4. VendaService::recalcularMetaMes() — re-soma vendas do mês
         ├─► VendaRepository::getTotalVendasMes()
         └─► MetaRepository::atualizarProgresso()
```

### Tabela `vendas` (Banco de Dados)

```sql
CREATE TABLE vendas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arte_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_venda DATE NOT NULL,
    lucro_calculado DECIMAL(10,2) NULL,
    rentabilidade_hora DECIMAL(10,2) NULL,
    forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito','transferencia','outro')
                    DEFAULT 'pix',
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (arte_id) REFERENCES artes(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,

    INDEX idx_vendas_data (data_venda),
    INDEX idx_vendas_arte (arte_id),
    INDEX idx_vendas_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

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
  GET    /vendas/relatorio   → VendaController@relatorio   (ANTES do resource!)
  GET    /vendas             → VendaController@index
  GET    /vendas/criar       → VendaController@create
  POST   /vendas             → VendaController@store
  GET    /vendas/{id}        → VendaController@show
  GET    /vendas/{id}/editar → VendaController@edit
  PUT    /vendas/{id}        → VendaController@update
  DELETE /vendas/{id}        → VendaController@destroy
```

**REGRA CRÍTICA:** A rota `/vendas/relatorio` DEVE ser declarada ANTES de `$router->resource('/vendas')`.

---

## ✅ FASE 1 — ESTABILIZAÇÃO CRUD (COMPLETA)

**Status:** ✅ COMPLETA — 22/02/2026  
**Bugs corrigidos:** 6 (V1, V2, V3, V7, V9, findByMesAno)  
**Testes:** 12/12 OK  
**Arquivos alterados:** VendaController.php, VendaService.php, views/vendas/relatorio.php

### Bugs Corrigidos

| # | Bug | Correção | Arquivo |
|---|-----|----------|---------|
| V1 | **B8 — Validação Invisível** — `store()`/`update()` usavam `Response::withErrors()` que grava em `$_SESSION['_flash']`, mas views leem `$_SESSION['_errors']` | Gravação direta em `$_SESSION['_errors']` | VendaController |
| V2 | **B9 — Dados Residuais** — Faltava `limparDadosFormulario()` em métodos de leitura | Método privado + chamadas em `index()`, `show()`, `edit()`, `relatorio()`. NUNCA em `create()` | VendaController |
| V3 | **Conversão string→int** — Router passa `$id` como string | `$id = (int) $id` em `show()`, `edit()`, `update()`, `destroy()` | VendaController |
| V7 | **destroy() não revertia arte** — Arte permanecia 'vendida' após excluir venda | `excluir()` reverte arte para 'disponivel' ANTES de recalcular meta | VendaService |
| V9 | **show() sem relacionamentos** — Usava `findOrFail()` perdendo arte_nome/cliente_nome | Novo método `buscarComRelacionamentos()` usando `findWithRelations()` | VendaService |
| — | **findMesAno() inexistente** — `recalcularMetaMes()` chamava método que não existe | Corrigido para `findByMesAno()` (nome real no MetaRepository) | VendaService |
| — | **Chave view relatório** — Card "Total Vendas" mostrava 0 | `$estatisticas['total_vendas'] ?? $estatisticas['total']` | relatorio.php |

### Checklist de Testes Fase 1

| # | Operação | Rota | O que verificar | Status |
|---|----------|------|-----------------|--------|
| T1 | Listar | `GET /vendas` | Carrega, exibe vendas com arte/cliente | ✅ OK |
| T2 | Criar (form) | `GET /vendas/criar` | Selects de artes e clientes populados | ✅ OK |
| T3 | Criar (salvar) | `POST /vendas` | Validação funciona, lucro calculado | ✅ OK |
| T4 | Verificar arte | — | Arte muda para status 'vendida' | ✅ OK |
| T5 | Verificar meta | — | Meta do mês incrementa valor_realizado | ✅ OK |
| T6 | Visualizar | `GET /vendas/{id}` | Dados + arte_nome + cliente_nome + lucro | ✅ OK |
| T7 | Editar (form) | `GET /vendas/{id}/editar` | Campos preenchidos, arte_id fixo | ✅ OK |
| T8 | Editar (salvar) | `PUT /vendas/{id}` | Valor alterado, lucro recalculado | ✅ OK |
| T9 | Excluir | `DELETE /vendas/{id}` | Confirmação funciona, venda removida | ✅ OK |
| T10 | Meta + Arte pós-excluir | — | Meta decrementada, arte volta 'disponivel' | ✅ OK |
| T11 | Relatório | `GET /vendas/relatorio` | Página carrega, cards e tabela corretos | ✅ OK |
| T12 | Validação | `POST /vendas` vazio | Validação HTML5 nativa bloqueia envio | ✅ OK |

---

## ✅ MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (COMPLETA)

**Status:** ✅ COMPLETA — 23/02/2026  
**Padrão:** Idêntico a Tags, Clientes e Artes (12 itens por página)

### Implementação

| Recurso | Descrição |
|---------|-----------|
| **12 vendas por página** | Controles Bootstrap 5 com janela de 5 páginas centrada |
| **Preservação de filtros** | Helper `vendaUrl()` mantém todos os parâmetros ao paginar |
| **Indicador** | "Mostrando X–Y de Z vendas" + "Página N de M" |

### Arquivos Alterados

| Arquivo | Ação | Detalhes |
|---------|------|---------|
| VendaRepository | **+2 métodos** | `allPaginated()` com JOINs + hydrating, `countAll()` com mesmos filtros |
| VendaService | **+1 método + 1 constante** | `listarPaginado($filtros)`, `const POR_PAGINA = 12` |
| VendaController | **index() reescrito** | Usa `listarPaginado()`, passa `$paginacao` para a view |
| views/vendas/index.php | **Reescrito** | Helper `vendaUrl()`, controles Bootstrap 5 |

---

## ✅ MELHORIA 2 — ORDENAÇÃO DINÂMICA (COMPLETA)

**Status:** ✅ COMPLETA — 23/02/2026  
**Padrão:** Headers clicáveis com toggle ASC/DESC

### Colunas Ordenáveis (7 colunas)

| Coluna | Campo BD | Direção padrão |
|--------|----------|----------------|
| Data | `v.data_venda` | DESC — **PADRÃO** |
| Arte | `a.nome` (JOIN) | ASC |
| Cliente | `c.nome` (JOIN) | ASC |
| Valor | `v.valor` | DESC |
| Lucro | `v.lucro_calculado` | DESC |
| R$/h | — (não ordenável) | — |
| Forma Pgto | `v.forma_pagamento` | ASC |

### Bug Corrigido: Global Scope em Helpers (23/02/2026)

**Fix:** `$GLOBALS['_vendaFiltros']` em vez de `global $filtros` nos helpers da view.

---

## ✅ MELHORIA 3 — FILTROS COMBINADOS (COMPLETA)

**Status:** ✅ COMPLETA — 23/02/2026  
**Padrão:** WHERE dinâmico com AND

### Filtros (5 campos combinados)

| Filtro | Tipo | Campo BD |
|--------|------|----------|
| Busca (termo) | text | `a.nome LIKE` OR `v.observacoes LIKE` |
| Cliente | select | `v.cliente_id = ?` |
| Forma pagamento | select | `v.forma_pagamento = ?` |
| Data início | date | `v.data_venda >= ?` |
| Data fim | date | `v.data_venda <= ?` |

---

## ✅ MELHORIA 5 — ESTATÍSTICAS POR VENDA (COMPLETA)

**Status:** ✅ COMPLETA — 28/02/2026  
**Localização:** views/vendas/show.php

### Cards Implementados

| Card | Dado | Cálculo | Cor dinâmica |
|------|------|---------|--------------|
| **Margem de Lucro** | % lucro/valor | `(lucro / valor) × 100` | Verde ≥30%, Amarelo ≥15%, Vermelho <15% |
| **vs Ticket Médio** | Comparativo | `((valor - ticketMedio) / ticketMedio) × 100` | Verde acima, Vermelho abaixo |
| **Posição Ranking** | X° de Y | COUNT com rentabilidade > atual | Primário |
| **R$/h vs Média** | Comparativo | `((rent - média) / média) × 100` | Verde acima, Vermelho abaixo |

### Arquivos Alterados

| Arquivo | Ação |
|---------|------|
| VendaRepository | +2 métodos: `getPosicaoRanking()`, `countComRentabilidade()` |
| VendaService | +1 método: `getEstatisticasVenda()` |
| VendaController | show() modificado: passa `$estatisticasVenda` |
| views/vendas/show.php | +Seção 4 mini-cards |

### Bug Corrigido

| Bug | Correção |
|-----|----------|
| Cliente telefone ausente no show.php | `findWithRelations()`: +`c.telefone as cliente_telefone` no SELECT + hydratação |

---

## ✅ MELHORIA 6 — GRÁFICOS DE VENDAS (COMPLETA)

**Status:** ✅ COMPLETA — 28/02/2026  
**Biblioteca:** Chart.js 4.4.7 via CDN  
**Localização:** views/vendas/index.php (card colapsável)

### Gráficos Implementados

| Gráfico | Tipo | Dados |
|---------|------|-------|
| **Faturamento + Lucro Mensal** | Barras verticais (dual) | SUM(valor) + SUM(lucro) últimos 6 meses |
| **Formas de Pagamento** | Doughnut | COUNT por forma_pagamento |

### Arquivos Alterados

| Arquivo | Ação |
|---------|------|
| VendaRepository | +1 método: `countByFormaPagamento()` |
| VendaService | +1 método: `getDistribuicaoFormaPagamento()` |
| VendaController | index(): +2 chamadas (vendas mensais + distribuição pgto) |
| views/vendas/index.php | +Card gráficos com collapse + Chart.js |

### Checklist de Testes M5+M6 (12 cenários)

| # | Teste | Status |
|---|-------|--------|
| T1 | M5: Margem de lucro — card com % e cor dinâmica | ✅ OK |
| T2 | M5: vs Ticket Médio — seta ↑ ou ↓ | ✅ OK |
| T3 | M5: Posição ranking — "X° de Y" | ✅ OK |
| T4 | M5: R$/h vs média — diferença % com cor | ✅ OK |
| T5 | M5: Ranking sem rentabilidade — card oculto | ✅ OK |
| T6 | Arte/Cliente cards — nome, email, telefone | ✅ OK (bug telefone corrigido) |
| T7 | M6: Card gráficos visível com collapse | ✅ OK |
| T8 | M6: Collapse funciona sem squashing | ✅ OK |
| T9 | M6: Barras faturamento + lucro | ✅ OK |
| T10 | M6: Doughnut formas pagamento | ✅ OK |
| T11 | CRUD preservado | ✅ OK |
| T12 | Paginação/filtros/ordenação intactos | ✅ OK |

---

## ✅ MELHORIA 4 — RELATÓRIO APRIMORADO (COMPLETA)

**Status:** ✅ COMPLETA — 03/03/2026  
**Rota:** `GET /vendas/relatorio`

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **Filtro por ano** | Dropdown com anos disponíveis (DISTINCT YEAR) |
| **Filtro por período** | Data início + data fim (mutuamente exclusivo com ano via JS) |
| **5 Cards de resumo** | Total Vendas, Faturamento, Lucro, Ticket Médio, Margem Média |
| **Gráfico barras** | Faturamento + Lucro mensal (Chart.js em collapse) |
| **Gráfico doughnut** | Distribuição por forma de pagamento |
| **Tabela comparativo mensal** | Mês, Qtd, Faturamento, Lucro, Ticket, Margem, Evolução % |
| **Tabela vendas detalhadas** | Data, Arte, Cliente, Valor, Lucro, R$/h, Pgto, Ações (ver) |
| **Ranking Top 10** | Vendas mais rentáveis com medalhas 🥇🥈🥉 |
| **Dicas de análise** | 3 colunas sobre margem, evolução e R$/h |

### Arquivos Alterados

| Arquivo | Ação |
|---------|------|
| VendaRepository | +5 métodos: `getAnosDisponiveis()`, `getEstatisticasFiltradas()`, `getVendasDetalhadas()`, `vendasPorMesFiltradas()`, `countByFormaPagamentoFiltrada()` |
| VendaService | +1 método: `getDadosRelatorio($filtros)` — orquestra 5 queries |
| VendaController | relatorio() substituído: usa `getDadosRelatorio()` |
| views/vendas/relatorio.php | Reescrito completo (707 linhas) |

### Checklist de Testes M4 (10 cenários)

| # | Teste | Status |
|---|-------|--------|
| T1 | Página carrega sem filtros — stats globais | ✅ OK |
| T2 | Filtro por ano funciona | ✅ OK |
| T3 | Filtro por período funciona | ✅ OK |
| T4 | 5 cards visíveis e corretos | ✅ OK |
| T5 | Gráfico barras (faturamento + lucro) | ✅ OK |
| T6 | Gráfico doughnut (formas pagamento) | ✅ OK |
| T7 | Comparativo mensal com evolução % | ✅ OK |
| T8 | Tabela detalhada com ações (ver) | ✅ OK |
| T9 | Link sidebar "Relatórios" navega | ✅ OK |
| T10 | CRUD preservado (criar/editar/excluir) | ✅ OK |

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos do VendaRepository (25 total)

| Método | Melhoria | Descrição |
|--------|----------|-----------|
| `allPaginated(...)` | **M1+M2+M3** | Query paginada com JOINs + WHERE dinâmico + whitelist ORDER BY |
| `countAll(...)` | **M1+M3** | COUNT com mesmos filtros |
| `allWithRelations()` | Base | Lista com JOINs (LEGADO) |
| `findWithRelations($id)` | Base+Fix | Busca por ID com JOINs (+telefone) |
| `findByPeriodo($inicio, $fim)` | Base | Filtro por período |
| `findByMesAno($ano, $mes)` | Base | Filtro por mês/ano |
| `findByMes($mesAno)` | Base | Alias (formato YYYY-MM) |
| `getTotalVendasMes($mesAno)` | Base | SUM(valor) do mês |
| `somaVendasMes($ano, $mes)` | Base | SUM(valor) por ano+mês |
| `getEstatisticas()` | Base | COUNT, SUM, AVG globais |
| `vendasPorMes($meses)` | Base | GROUP BY mês |
| `vendasPorCliente()` | Base | GROUP BY cliente |
| `paginate(...)` | Base | Paginação básica (LEGADO) |
| `findByCliente($clienteId)` | Base | Filtro por cliente |
| `getRecentes($limit)` | Base | Últimas vendas |
| `getVendasPorMes($meses)` | Base | Alias para vendasPorMes |
| `getMaisRentaveis($limit)` | Base | TOP N por R$/h |
| `getPosicaoRanking($vendaId)` | **M5** | Posição no ranking R$/h |
| `countComRentabilidade()` | **M5** | Total com rentabilidade > 0 |
| `countByFormaPagamento()` | **M6** | GROUP BY forma_pagamento |
| `getAnosDisponiveis()` | **M4** | DISTINCT YEAR(data_venda) |
| `getEstatisticasFiltradas(?inicio, ?fim)` | **M4** | Stats com filtro período |
| `getVendasDetalhadas(?inicio, ?fim)` | **M4** | Vendas com JOINs filtradas |
| `vendasPorMesFiltradas(?inicio, ?fim)` | **M4** | GROUP BY mês filtrado |
| `countByFormaPagamentoFiltrada(?inicio, ?fim)` | **M4** | Doughnut filtrado |

### Métodos do VendaService chamados no Controller (14 total)

| Método | Status |
|--------|--------|
| `listarPaginado($filtros)` | ✅ **M1** |
| `listar($filtros)` | ✅ LEGADO |
| `buscar($id)` | ✅ Base |
| `buscarComRelacionamentos($id)` | ✅ **Fase 1** |
| `registrar($dados)` | ✅ Base (8 passos) |
| `atualizar($id, $dados)` | ✅ Base (V6 fix) |
| `excluir($id)` | ✅ Base (V7 fix) |
| `getEstatisticas()` | ✅ Base |
| `getVendasMensais($meses)` | ✅ Base |
| `getRankingRentabilidade($limite)` | ✅ Base |
| `getTotalMes($mesAno)` | ✅ Base (Dashboard) |
| `getEstatisticasVenda(...)` | ✅ **M5** |
| `getDistribuicaoFormaPagamento()` | ✅ **M6** |
| `getDadosRelatorio($filtros)` | ✅ **M4** |

---

## 📌 PADRÕES APLICADOS

| Padrão | Origem | Aplicação |
|--------|--------|-----------|
| B8 workaround (`$_SESSION['_errors']` direto) | Clientes/Artes | ✅ Fase 1 |
| B9 workaround (`limparDadosFormulario()`) | Clientes/Artes | ✅ Fase 1 |
| Conversão `(int) $id` | Artes (Router bug) | ✅ Fase 1 |
| Normalização filtros `?? null ?: null` | Artes (T1) | ✅ M3 |
| Paginação 12/página + helper URL | Tags/Clientes/Artes | ✅ M1 |
| Headers clicáveis + whitelist ORDER BY | Tags/Clientes/Artes | ✅ M2 |
| WHERE dinâmico com AND | Artes M1/M3 | ✅ M3 |
| `$GLOBALS['_key']` vs `global` em helpers | **NOVO** Vendas M2 | ✅ M2 |
| Chart.js 4.4.7 + container 280px fixo | Tags/Metas/Artes | ✅ M6 |
| Collapse com chart.resize() | Artes M6 | ✅ M6 |
| Legenda manual HTML + labels PT-BR | Tags M6 | ✅ M6 |
| Cards border-start-4 com cores dinâmicas | Tags M5 / Artes M5 | ✅ M4+M5 |
| Try/catch por seção com fallback vazio | Todos os módulos | ✅ M4+M5+M6 |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível
**Status Vendas:** ✅ Workaround aplicado no VendaController.

### Bug B9: Dados Residuais
**Status Vendas:** ✅ Workaround aplicado — `limparDadosFormulario()` em index(), show(), edit(), relatorio().

### Conversão string→int do Router
**Status Vendas:** ✅ Corrigido — `$id = (int) $id` em show(), edit(), update(), destroy().

### ⚠️ Bug Global Scope em Helpers de Views

**Fix:** `$GLOBALS['_chave']` em vez de `global $variavel`.

| Módulo | Fix aplicado? |
|--------|---------------|
| Tags index.php | ❓ VERIFICAR |
| Clientes index.php | ❓ VERIFICAR |
| Artes index.php | ❓ VERIFICAR |
| Vendas index.php | ✅ Corrigido (23/02) |

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                             → COMPLETO (6/6)
2. ✅ Clientes     — independente                             → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)      → COMPLETO (6/6)
4. ✅ Artes        — depende de Tags (✅)                       → COMPLETO (6/6 + cross-module OK)
5. ✅ VENDAS       — depende de Artes + Clientes + Metas       → COMPLETO (6/6) ★
6. 🎯 Dashboard   — depende de TODOS                          → FASE 1 PENDENTE
```

### Impacto de Vendas em Outros Módulos

```
Vendas → Artes:
  ├── store() → ArteRepository::update(status='vendida')
  ├── destroy() → ArteRepository::update(status='disponivel') ✅ V7 FIX
  └── Artes M5 cross-module → cards Lucro/Rentabilidade ✅ IMPLEMENTADO

Vendas → Metas:
  ├── store() → MetaRepository::atualizarProgresso() via recalcularMetaMes()
  ├── update() → recalcula meta se valor mudou ✅ V6 FIX
  └── destroy() → recalcularMetaMes() (re-soma vendas)

Vendas → Dashboard:
  ├── Faturamento Mensal (gráfico barras)
  ├── Vendas do Mês (card)
  ├── Evolução de Vendas (gráfico linha+barras)
  ├── Ranking Rentabilidade (top 5)
  └── Top Clientes (via JOIN com clientes)

Vendas → Clientes:
  └── Histórico de compras no ClienteController::show()
```

### Histórico das Sessões

| # | Data | Foco | Entregas |
|---|------|------|----------|
| 1 | 22/02 | Fase 1 — Análise + Correções + Testes T1-T12 | VendaController + VendaService (6 bugs) + diagnostico + relatorio fix |
| 2 | 22/02 | Cross-module Artes M5 | ArteService (+VendaRepository +3 métodos) + show.php (2 cards novos) |
| 3 | 23/02 | M1+M2+M3 — Paginação + Ordenação + Filtros | VendaRepository (+2), VendaService (+1+const), VendaController (index reescrito), index.php (reescrito) |
| 4 | 23/02 | Revisão anti-regressão + Bug fix global scope | 18 regressões corrigidas nos 4 arquivos + fix `$GLOBALS` nos helpers da view |
| 5 | 28/02 | M5+M6 — Estatísticas + Gráficos (6 arquivos) | VendaRepository (+3), VendaService (+2), VendaController (show+index mod), show.php (+cards), index.php (+gráficos) |
| 6 | 28/02 | Testes M5+M6 T1-T12 + Bug fix telefone | findWithRelations() corrigido (SELECT + hydratação telefone). 12/12 OK |
| 7 | 03/03 | M4 — Relatório Aprimorado (4 arquivos) | VendaRepository (+5), VendaService (+1), VendaController (relatorio reescrito), relatorio.php (reescrito 707 linhas) |
| 8 | 03/03 | Testes M4 T1-T10 | Todos os testes aprovados. Módulo Vendas 6/6 COMPLETO |

---

**Última atualização:** 03/03/2026  
**Status:** ✅ MÓDULO COMPLETO — 6/6 melhorias implementadas  
**Cross-module:** ✅ Cards Lucro + Rentabilidade implementados no módulo Artes  
**Próxima ação:** Dashboard — Fase 1 (testes browser)  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅
