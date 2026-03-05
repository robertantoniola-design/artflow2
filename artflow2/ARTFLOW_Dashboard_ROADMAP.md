# ARTFLOW 2.0 — DASHBOARD ROADMAP

**Módulo:** Dashboard  
**Rota principal:** `GET /` e `GET /dashboard`  
**Controller:** `DashboardController`  
**View:** `views/dashboard/index.php`  
**Componente:** `views/components/alerta-meta-risco.php`  
**Criado:** 03/03/2026  
**Status:** ✅ FASE 1 COMPLETA + M1 ✅ + M2 ✅ — M3-M6 PENDENTES

---

## 🏗️ ARQUITETURA DO MÓDULO

### Natureza Especial

O Dashboard **NÃO é um módulo CRUD** — não tem Model, Repository, Service ou Validator próprios. É um **módulo agregador** que consome dados de TODOS os 5 módulos via seus respectivos Services.

### Estrutura de Arquivos

```
src/
└── Controllers/
    └── DashboardController.php       ✅ Controller com 7 actions (index + 6 AJAX)
                                         + 4 métodos privados auxiliares

views/
└── dashboard/
    ├── index.php                     ✅ View principal (6 cards + 3 seções colapsáveis)
    └── ../components/
        └── alerta-meta-risco.php     ✅ Componente condicional (Metas M4)

views/
└── layouts/
    └── main.php                      ✅ Chart.js 4.4.7 centralizado (FIX D2)

config/
└── routes.php                        ✅ 8 rotas Dashboard registradas
```

### Rotas (8 total)

```
DASHBOARD (1 principal + 7 AJAX)
  GET  /                         → DashboardController@index           (página principal)
  GET  /dashboard                → DashboardController@index           (alias)
  GET  /dashboard/refresh        → DashboardController@refresh         (AJAX — atualiza cards)
  GET  /dashboard/artes          → DashboardController@estatisticasArtes    (AJAX)
  GET  /dashboard/vendas         → DashboardController@estatisticasVendas   (AJAX)
  GET  /dashboard/meta           → DashboardController@progressoMeta       (AJAX)
  GET  /dashboard/atividades     → DashboardController@atividadesRecentes  (AJAX)
  GET  /dashboard/busca          → DashboardController@busca               (AJAX)
```

### Dependências entre Classes

```
DashboardController
├── __construct(ArteService, VendaService, MetaService, ClienteService)  ← 4 dependências
│
├── index()
│   ├── ArteService::getEstatisticas()           → countByStatus (adaptado via adaptarArtesStats)
│   ├── ArteService::getDisponiveisParaVenda()   → artes com status 'disponivel' + 'em_producao'
│   ├── VendaService::getVendasMesAtual()        → array vendas do mês corrente
│   ├── VendaService::getTotalMes()              → float faturamento mensal
│   ├── VendaService::getVendasMensais(6)        → array para gráfico barras + tendências M1
│   ├── VendaService::getRankingRentabilidade(5) → top 5 mais rentáveis
│   ├── MetaService::getResumoDashboard()        → meta atual + % + projeção
│   ├── MetaService::getMetasEmRisco()           → alerta meta em risco
│   ├── ClienteService::getTopClientes(5)        → top 5 compradores
│   ├── [M1] calcularLucroMes($vendasMes)        → soma lucro_calculado dos objetos Venda
│   ├── [M1] calcularTendencias(...)             → variação % vs mês anterior (4 métricas)
│   └── [M1] Cálculos diretos: ticketMedio, margemMes (zero queries extras)
│
├── refresh()               → JSON cards atualizados (AJAX) — inclui novos cards M1
├── estatisticasArtes()     → JSON stats artes por status
├── estatisticasVendas()    → JSON vendas mensais + ranking
├── progressoMeta()         → JSON meta + dias restantes + projeção
├── atividadesRecentes()    → JSON últimas vendas como "atividades"
└── busca()                 → JSON busca global (TODO: parcial)
```

### Métodos Auxiliares do Controller

```
DashboardController (private)
├── limparDadosFormulario()         → FIX D1: limpa $_SESSION['_old_input'] e $_SESSION['_errors']
├── adaptarArtesStats(array)        → FIX CHAVES: converte formato countByStatus → formato Dashboard
├── [M1] calcularLucroMes(array)    → Soma getLucroCalculado() dos objetos Venda do mês
├── [M1] calcularTendencias(...)    → Busca mês anterior em vendasMensais, compara 4 métricas
└── [M1] calcularVariacao(float, float) → Retorna ['percentual', 'anterior'] com proteção divisão/zero
```

### Variáveis Passadas à View (index)

| Variável | Tipo | Origem | Uso na View |
|----------|------|--------|-------------|
| `$artesStats` | array | ArteService (adaptado) | Cards + doughnut status |
| `$vendasMes` | array | VendaService | Card "Vendas no Mês" (count) |
| `$faturamentoMes` | float | VendaService | Card "Faturamento" |
| `$metaAtual` | array | MetaService | Card "Meta do Mês" + semi-doughnut |
| `$metaEmRisco` | array | MetaService | Alerta condicional (componente) |
| `$topClientes` | array | ClienteService | Tabela Top 5 Clientes |
| `$artesDisponiveis` | array | ArteService | Subtexto card Total Artes + lista |
| `$vendasMensais` | array | VendaService | Gráficos barras + evolução |
| `$maisRentaveis` | array | VendaService | Lista ranking rentabilidade |
| `$lucroMes` | float | **M1**: calculado no Controller | Card "Lucro do Mês" |
| `$ticketMedio` | float | **M1**: calculado no Controller | Card "Ticket Médio" |
| `$margemMes` | float | **M1**: calculado no Controller | Badge margem no card Lucro |
| `$tendencias` | array | **M1**: calculado no Controller | Badges ↑↓% em 4 cards |

---

## 📊 ESTADO ATUAL DO DASHBOARD

### O Que Já Existe (Funcional)

| # | Componente | Tipo | Descrição | Status |
|---|-----------|------|-----------|--------|
| 1 | Card Total de Artes | Card | Total + disponíveis + em produção | ✅ M1 aprimorado |
| 2 | Card Vendas no Mês | Card | Quantidade + tendência ↑↓% | ✅ M1 aprimorado |
| 3 | Card Faturamento | Card | Valor total + tendência ↑↓% | ✅ **M1 NOVO** (era subtexto) |
| 4 | Card Lucro do Mês | Card | Lucro + margem % + tendência ↑↓% | ✅ **M1 NOVO** |
| 5 | Card Ticket Médio | Card | Valor médio por venda + tendência ↑↓% | ✅ **M1 NOVO** |
| 6 | Card Meta do Mês | Card | Porcentagem + barra progresso | ✅ M1 reorganizado |
| 7 | Seção Gráficos (colapsável) | Collapse | 4 gráficos Chart.js | ✅ **M2 NOVO** |
| 8 | Gráfico Faturamento Mensal | Chart.js Bar | Últimos 6 meses (valor + lucro) | ✅ Funcional |
| 9 | Gráfico Status Artes | Chart.js Doughnut | Disponível/Produção/Vendida | ✅ Funcional |
| 10 | Gráfico Meta do Mês | Chart.js Semi-doughnut | Realizado vs Falta | ✅ Funcional |
| 11 | Gráfico Evolução Vendas | Chart.js Misto | Faturamento (linha) + Quantidade (barra) | ✅ Funcional |
| 12 | Seção Rankings (colapsável) | Collapse | Top Clientes + Mais Rentáveis | ✅ **M2 NOVO** |
| 13 | Tabela Top Clientes | Tabela | #, Nome, Compras, Total | ✅ Funcional |
| 14 | Lista Mais Rentáveis | Lista | Arte + R$/h | ✅ Funcional |
| 15 | Seção Detalhes (colapsável) | Collapse | Resumo Mensal + Disponíveis | ✅ **M2 NOVO** |
| 16 | Tabela Resumo Mensal | Tabela | Mês, Qtd, Faturamento, Lucro | ✅ Funcional |
| 17 | Lista Artes Disponíveis | Lista | Arte + custo + horas | ✅ Funcional |
| 18 | Alerta Meta em Risco | Componente | Banner condicional (Metas M4) | ✅ Funcional |
| 19 | Botões Nova Arte / Venda | Header | Atalhos rápidos | ✅ Funcional |
| 20 | Tooltips nos Cards | Bootstrap 5 | 6 ícones ℹ️ com explicações | ✅ **M2 NOVO** |
| 21 | 6 Endpoints AJAX | API JSON | Refresh (M1 ampliado), artes, vendas, meta, atividades, busca | ✅ Funcional |

---

## ✅ FASE 1 — ESTABILIZAÇÃO (COMPLETA — 03/03/2026)

### Bugs Investigados

| # | Suspeita | Resultado | Ação |
|---|----------|-----------|------|
| D1 | `limparDadosFormulario()` ausente | 🔴 **CONFIRMADO** | Método privado adicionado ao Controller |
| D2 | Chart.js duplicado em 5 arquivos | 🔴 **CONFIRMADO — CRÍTICO** | Centralizado no layout v4.4.7, removido de 4 views |
| D3 | `$vendasMes` tipo misto | ✅ Descartado | View já tem verificação defensiva `is_array()` |
| D4 | `$metaAtual` pode ser null/vazio | ✅ Descartado | View usa operador `??` em todos os acessos |
| D5 | `$artesStats` chaves inconsistentes | 🔴 **CONFIRMADO — CRÍTICO** | `adaptarArtesStats()` converte formato no Controller |
| D6 | Endpoint `busca()` ausente | ✅ Descartado | Método implementado, retorna `resultados => []` (TODO) |
| D7 | `topClientes` formato array/objeto | ✅ Descartado | View tem dual-path `is_object()`/`is_array()` |
| D8 | Queries duplicadas em `refresh()` | 🔴 **CONFIRMADO** | Variáveis locais eliminam 4 queries por request |

### Correções Aplicadas

#### FIX D1 — `limparDadosFormulario()` (Severidade: Baixa)

**Problema:** DashboardController não limpava `$_SESSION['_old_input']` e `$_SESSION['_errors']`. Se o usuário navegava Criar Venda → Erro validação → Dashboard → outro módulo, dados residuais contaminavam formulários.

**Solução:** Método `private limparDadosFormulario()` adicionado ao DashboardController (padrão dos outros controllers — é privado e copy-pasted, NÃO existe no BaseController). Chamado no início de `index()`.

**Arquivo:** `src/Controllers/DashboardController.php`

#### FIX D2 — Chart.js Centralizado (Severidade: CRÍTICA)

**Problema:** Chart.js carregado em 5 arquivos com versões diferentes:
- `main.php`: `chart.js` (SEM versão = latest)
- `dashboard/index.php`: `chart.js@4.4.0`
- `artes/index.php`: `chart.js@4.4.7` (condicional)
- `metas/index.php`: `chart.js` (SEM versão, condicional)
- `tags/index.php`: `chart.js@4.4.7` (condicional)

Dashboard carregava 2× (latest + 4.4.0). Artes carregava 2× (latest + 4.4.7). Páginas sem gráficos carregavam ~200KB desnecessários.

**Solução (Opção A — Centralizar no layout):**
- `views/layouts/main.php`: fixado em `chart.js@4.4.7/dist/chart.umd.min.js`
- Removido `<script>` de Chart.js de: `dashboard/index.php`, `artes/index.php`, `metas/index.php`, `tags/index.php`

**Arquivos alterados:** 5 (main.php + 4 views)

#### FIX D5/CHAVES — `adaptarArtesStats()` (Severidade: CRÍTICA)

**Problema:** `ArteService::getEstatisticas()` delega para `ArteRepository::countByStatus()` que retorna:
```
['disponivel' => N, 'em_producao' => N, 'vendida' => N, 'reservada' => N]
```

Dashboard esperava formato diferente:
```
['total' => N, 'disponiveis' => N, 'em_producao' => N, 'vendidas' => N, 'reservadas' => N]
```

Diferenças: falta `total` (soma), chaves no singular vs plural (`disponivel`→`disponiveis`, `vendida`→`vendidas`).

Resultado: Card "Total de Artes" = 0, Gráfico doughnut "Nenhuma arte cadastrada".

**Solução:** Método privado `adaptarArtesStats()` no DashboardController converte o formato. NÃO alteramos ArteService/Repository para não afetar módulo Artes que está estável.

**Contexto histórico:** O ArteRepository original tinha `getEstatisticas()` com formato correto, mas foi removido acidentalmente na refatoração M6 do módulo Artes (substituído por `countByStatus()` + `getResumoFinanceiro()`). O método foi restaurado no Repository como backup, mas o Service continua usando `countByStatus()` — por isso a adaptação é feita no Controller consumidor.

**Arquivo:** `src/Controllers/DashboardController.php`

#### FIX D8 — Queries Duplicadas no `refresh()` (Severidade: Média)

**Problema:** `refresh()` chamava `getEstatisticas()` 2× e `getResumoDashboard()` 2× = 4 queries SQL desnecessárias por request AJAX.

**Solução:** Variáveis locais armazenam resultado da primeira chamada e reutilizam.

**Arquivo:** `src/Controllers/DashboardController.php`

### Bugs Anteriores (Já Corrigidos Antes da Fase 1)

| Bug | Causa | Correção | Data |
|-----|-------|----------|------|
| Top Clientes zerado | `topClientes()` retornava objetos que perdiam campos calculados | Retorno array bruto | 31/01/2026 |
| Gráficos infinitos | Canvas `responsive:true` sem altura fixa → loop resize | Container `height:280px` | 31/01/2026 |
| Dashboard quebrado | Objeto Cliente usado como array | Verificação defensiva `is_object()`/`is_array()` | 31/01/2026 |
| TypeError getValor() | Vendas às vezes retornam arrays em vez de objetos | Verificação defensiva no resumo | 31/01/2026 |

### Arquivos Alterados na Fase 1

| Arquivo | Caminho | Fixes |
|---------|---------|-------|
| DashboardController.php | `src/Controllers/` | D1 + D5/CHAVES + D8 |
| main.php | `views/layouts/` | D2 (Chart.js fixado 4.4.7) |
| index.php | `views/dashboard/` | D2 (removido `<script>` Chart.js) |
| index.php | `views/artes/` | D2 (removido `<script>` Chart.js) |
| index.php | `views/metas/` | D2 (removido `<script>` Chart.js) |
| index.php | `views/tags/` | D2 (removido `<script>` Chart.js) |

### Testes T1–T12 — TODOS PASSARAM ✅

| # | Área | Teste | Resultado |
|---|------|-------|-----------|
| **T1** | Cards | Página carrega sem erros | ✅ OK |
| **T2** | Cards — Dados | Valores batem com banco | ✅ OK (após fix D5/CHAVES) |
| **T3** | Gráfico Faturamento | Barras renderizam | ✅ OK |
| **T4** | Gráfico Status Artes | Doughnut renderiza | ✅ OK (após fix D5/CHAVES) |
| **T5** | Gráfico Meta | Semi-doughnut renderiza | ✅ OK |
| **T6** | Gráfico Evolução | Misto renderiza | ✅ OK |
| **T7** | Top Clientes | Tabela correta | ✅ OK |
| **T8** | Artes Disponíveis | Lista correta | ✅ OK |
| **T9** | Ranking Rentáveis | Lista correta | ✅ OK |
| **T10** | Alerta Meta Risco | Condicional funciona | ✅ OK |
| **T11** | Reflexo CRUD | Criar venda reflete | ✅ OK |
| **T12** | Banco vazio | — | ⏭️ Não executado (opcional) |

### Lições Aprendidas na Fase 1

1. **`limparDadosFormulario()` é privado e per-controller:** Não existe no BaseController. Cada controller define o seu. Se esquecer de adicionar, dados residuais de sessão vazam entre módulos.

2. **Chart.js deve ter versão fixa e única:** Carregar via CDN sem versão (`chart.js` sem `@4.4.7`) pega `latest` que pode mudar a qualquer momento. Versão fixada em 4.4.7 no layout, removida de todas as views individuais.

3. **Dashboard é consumidor, não dono dos dados:** Quando o formato dos dados muda no Service/Repository de outro módulo (como aconteceu com `countByStatus()` substituindo `getEstatisticas()`), a adaptação deve ser feita no Dashboard — não no módulo fonte que já está estável.

4. **`array_sum()` é aliado:** Para calcular `total` a partir de `countByStatus()` que retorna contagens por status, `array_sum($raw)` soma todas as contagens sem precisar de query extra.

5. **Erros silenciosos por `?? 0`:** O operador null coalescing mascara bugs — cards mostravam 0 sem nenhum erro no console ou no PHP. Sempre verificar dados reais no phpMyAdmin quando cards mostram zeros.

---

## ✅ MELHORIA 1 — CARDS APRIMORADOS (COMPLETA — 05/03/2026)

**Complexidade:** Baixa  
**Pré-requisito:** Fase 1 ✅  
**Arquivos alterados:** DashboardController.php (editar), views/dashboard/index.php (editar)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **+Card Faturamento** | Card próprio (antes era subtexto de Vendas) com tendência ↑↓% |
| **+Card Lucro do Mês** | SUM(lucro_calculado) + badge margem % (verde ≥40%, amarelo ≥20%, vermelho <20%) |
| **+Card Ticket Médio** | faturamento ÷ qtd vendas + tendência ↑↓% |
| **Indicadores de tendência** | 4 cards com badges comparando mês atual vs anterior: ↑ +15% (verde) ou ↓ -8% (vermelho) |
| **Subtextos informativos** | Card Artes: "X à venda · Y em produção". Card Lucro: margem %. Card Meta: barra progresso |
| **Card "À Venda" absorvido** | Info migrada para subtexto do card Total Artes — ganho de espaço para novos cards |

### Layout: 6 Cards (2 linhas de 3)

```
Linha 1: [Total Artes]  [Vendas no Mês ↑↓%]  [Faturamento ↑↓%]
Linha 2: [Lucro + Margem ↑↓%]  [Ticket Médio ↑↓%]  [Meta do Mês %]
```

### Dados Necessários (nenhuma query extra)

| Dado | Fonte | Cálculo |
|------|-------|---------|
| Lucro do mês | `$vendasMes` (já buscado) | `calcularLucroMes()` soma getLucroCalculado() |
| Ticket médio | `$faturamentoMes` / `count($vendasMes)` | Divisão direta no Controller |
| Margem % | `$lucroMes` / `$faturamentoMes` × 100 | Divisão direta no Controller |
| Tendências | `$vendasMensais` (já buscado com 6 meses) | `calcularTendencias()` compara mês atual vs anterior |

### Métodos Privados Adicionados ao Controller

| Método | Parâmetros | Retorno | Descrição |
|--------|-----------|---------|-----------|
| `calcularLucroMes()` | `array $vendasMes` | `float` | Itera objetos Venda, soma getLucroCalculado(). Defensivo: suporta arrays |
| `calcularTendencias()` | `$vendasMensais, $fatAtual, $lucroAtual, $qtdAtual, $ticketAtual` | `array` | Busca mês anterior (YYYY-MM) no array, compara 4 métricas |
| `calcularVariacao()` | `float $atual, float $anterior` | `array ['percentual', 'anterior']` | Proteção divisão/zero. Se anterior=0 e atual>0 → +100% |

### Helper na View

```php
function renderTendencia(?array $tendencia, string $prefixo, bool $isMoney): string
```
Gera badge HTML com cor semântica (verde/vermelho/cinza) + ícone ↑↓ + percentual + valor anterior.

### Endpoint refresh() Ampliado

| Chave | Antes | Depois (M1) |
|-------|-------|-------------|
| `cards.total_artes` | ✅ | ✅ |
| `cards.artes_disponiveis` | ✅ | ✅ |
| `cards.vendas_mes` | ✅ (era faturamento) | ✅ renomeado |
| `cards.qtd_vendas_mes` | — | ✅ **NOVO** |
| `cards.faturamento_mes` | — | ✅ **NOVO** |
| `cards.lucro_mes` | — | ✅ **NOVO** |
| `cards.ticket_medio` | — | ✅ **NOVO** |
| `cards.margem_mes` | — | ✅ **NOVO** |
| `cards.meta_progresso` | ✅ | ✅ |

### Arquivos Alterados

| Arquivo | Caminho | Alterações |
|---------|---------|-----------|
| DashboardController.php | `src/Controllers/` | +3 métodos privados, index() +5 linhas cálculo, +4 variáveis na view, refresh() ampliado |
| index.php | `views/dashboard/` | Cards reescritos (4→6, 1 linha→2 linhas), helper renderTendencia() |

### Checklist de Testes M1 — TODOS PASSARAM ✅

| # | Teste | O que verificar | Status |
|---|-------|-----------------|--------|
| P1 | Página carrega | 6 cards visíveis, 2 linhas de 3, sem erro PHP | ✅ OK |
| P2 | Cards numéricos | Total Artes, Vendas, Faturamento batem com banco | ✅ OK |
| P3 | Cards novos | Lucro + Ticket Médio corretos | ✅ OK |
| P4 | Tendências | Badges ↑↓% aparecem se houver dados do mês anterior | ✅ OK |
| P5 | Sem mês anterior | Mostra "Sem dados anteriores" | ✅ OK |
| P6 | Gráficos intactos | 4 gráficos renderizam normalmente | ✅ OK |

---

## ✅ MELHORIA 2 — LAYOUT E RESPONSIVIDADE (COMPLETA — 05/03/2026)

**Complexidade:** Média  
**Pré-requisito:** M1 ✅  
**Arquivos alterados:** views/dashboard/index.php (somente view — Controller NÃO muda)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **3 Seções colapsáveis** | Gráficos, Rankings e Detalhes em cards com collapse (padrão Artes M6 / Tags M6 / Vendas M6) |
| **Chart.resize() no collapse** | `Chart.instances.forEach(chart => chart.resize())` no `shown.bs.collapse` — obrigatório para Chart.js recalcular após display:none→block |
| **Tooltips informativos** | 6 ícones ℹ️ nos cards com `data-bs-toggle="tooltip"` + inicialização Bootstrap 5 |
| **Responsividade melhorada** | Cards: `col-6` mobile (2/linha) → `col-lg-4` desktop (3/linha). Gráficos: `col-12` mobile → `col-lg-8`/`col-lg-4` desktop |
| **Ícones responsivos** | Ícones grandes dos cards: `d-none d-sm-block` (ocultos < 576px) |
| **setupCollapse() DRY** | Função JS reutilizável para registrar handlers de collapse (evita repetição) |

### Estrutura Visual M2

```
[CARDS — 6 cards em 2 linhas de 3 (sempre visíveis)]

[ANÁLISE GRÁFICA — Colapsável ▼]
  ├── Faturamento Mensal (col-8) + Status Artes (col-4)
  └── Meta do Mês (col-4) + Evolução Vendas (col-8)

[RANKINGS — Colapsável ▼]
  ├── Top Clientes (col-6)
  └── Artes Mais Rentáveis (col-6)

[DETALHES — Colapsável ▼]
  ├── Resumo Mensal (col-6)
  └── Disponíveis para Venda (col-6)
```

### Seções Colapsáveis

| Seção | ID Collapse | Conteúdo | Chart.resize() | Padrão aberto |
|-------|-------------|----------|----------------|---------------|
| Análise Gráfica | `graficosCollapse` | 4 gráficos Chart.js | ✅ Sim | ✅ Sim |
| Rankings | `rankingsCollapse` | Top Clientes + Rentáveis | Não necessário | ✅ Sim |
| Detalhes | `detalhesCollapse` | Resumo Mensal + Disponíveis | Não necessário | ✅ Sim |

### JavaScript M2 Adicionado

```javascript
// Função DRY para registrar handlers de collapse
function setupCollapse(collapseId, iconId, hasCharts) { ... }

// 3 registros
setupCollapse('graficosCollapse', 'collapseIconGraficos', true);   // COM chart.resize()
setupCollapse('rankingsCollapse', 'collapseIconRankings', false);
setupCollapse('detalhesCollapse', 'collapseIconDetalhes', false);

// Inicializa tooltips Bootstrap 5
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
```

### Arquivos Alterados

| Arquivo | Caminho | Alterações |
|---------|---------|-----------|
| index.php | `views/dashboard/` | Seções reorganizadas em 3 collapses, tooltips nos cards, JS collapse handlers + tooltip init |

### Checklist de Testes M2 — TODOS PASSARAM ✅

| # | Teste | O que verificar | Status |
|---|-------|-----------------|--------|
| P1 | Cards intactos | 6 cards visíveis com tendências (M1 preservado) | ✅ OK |
| P2 | Tooltips | Hover no ℹ️ dos cards mostra explicação | ✅ OK |
| P3 | Collapse gráficos | Clicar chevron recolhe/expande os 4 gráficos | ✅ OK |
| P4 | Chart.resize | Após recolher e expandir, gráficos renderizam corretamente | ✅ OK |
| P5 | Collapse rankings | Top Clientes e Rentáveis recolhem/expandem | ✅ OK |
| P6 | Collapse detalhes | Resumo Mensal e Disponíveis recolhem/expandem | ✅ OK |
| P7 | Mobile | Cards 2/linha, gráficos full-width ao redimensionar | ✅ OK |
| P8 | Chevron anima | Ícone muda de ↑ para ↓ ao recolher cada seção | ✅ OK |

### Lições Aprendidas M1 + M2

6. **Zero queries extras para tendências:** Todos os dados para calcular lucro, ticket médio, margem e tendências já existiam nos resultados das queries da Fase 1. `$vendasMes` (objetos Venda) contém `getLucroCalculado()`. `$vendasMensais` (array dos últimos 6 meses) contém mês anterior para comparação.

7. **Chart.resize() é obrigatório em collapses:** Chart.js renderiza com tamanho 0 dentro de `display:none`. Ao reabrir o collapse, é necessário chamar `Chart.instances.forEach(chart => chart.resize())` no evento `shown.bs.collapse`.

8. **setupCollapse() como padrão DRY:** Ao invés de repetir 3 blocos de addEventListener, uma função genérica `setupCollapse(collapseId, iconId, hasCharts)` cobre todos os cenários com o parâmetro booleano `hasCharts` controlando se faz resize.

9. **Ícones responsivos com d-none d-sm-block:** Os ícones decorativos dos cards (display-6) ocupam espaço valioso em telas pequenas. Ocultá-los abaixo de 576px mantém a informação numérica legível.

---

## 📋 MELHORIAS PLANEJADAS (M3–M6)

### Visão Geral

| # | Melhoria | Complexidade | Descrição | Status |
|---|----------|-------------|-----------|--------|
| M1 | Cards aprimorados | Baixa | +3 cards + indicadores de tendência (↑↓) | ✅ **COMPLETA** (05/03/2026) |
| M2 | Layout e responsividade | Média | 3 seções colapsáveis + tooltips + mobile-first | ✅ **COMPLETA** (05/03/2026) |
| M3 | Seção Atividades Recentes | Média | Timeline visual das últimas ações (vendas, artes, metas, clientes) | 🔲 Pendente |
| M4 | KPIs e métricas avançadas | Média | Insights inteligentes: R$/h médio, projeção, melhor dia, forma pgto | 🔲 Pendente |
| M5 | Período selecionável | Média | Dropdown: Mês Atual / 3 Meses / 6 Meses / Ano / Tudo | 🔲 Pendente |
| M6 | Auto-refresh e polish | Baixa | Polling AJAX opcional, animações, estados vazios, timestamp | 🔲 Pendente |

---

### 📋 MELHORIA 3 — SEÇÃO ATIVIDADES RECENTES

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅  
**Arquivos:** DashboardController.php (editar), views/dashboard/index.php (editar), +1 Repository opcional

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Timeline visual** | Últimas 10 atividades do sistema em formato timeline vertical |
| **Tipos de atividade** | Venda registrada, Arte criada, Meta atingida, Cliente cadastrado |
| **Ícones + cores** | 🛒 verde (venda), 🎨 azul (arte), 🎯 amarelo (meta), 👤 info (cliente) |
| **Timestamps** | "Há 2 horas", "Ontem às 14:30", etc (formato relativo) |

#### Abordagem

Opção A — **Query UNION** combinando últimos registros de cada tabela por `created_at`:
```sql
(SELECT 'venda' as tipo, id, created_at FROM vendas ORDER BY created_at DESC LIMIT 5)
UNION ALL
(SELECT 'arte' as tipo, id, created_at FROM artes ORDER BY created_at DESC LIMIT 5)
UNION ALL ...
ORDER BY created_at DESC LIMIT 10
```

Opção B — **Tabela activity_log** (já existe na migration 008) — registrar ações automaticamente.

---

### 📋 MELHORIA 4 — KPIs E MÉTRICAS AVANÇADAS

**Complexidade:** Média  
**Pré-requisito:** M1 ✅  
**Arquivos:** DashboardController.php (editar), VendaService.php (+1 método), views/dashboard/index.php (editar)

#### Especificação

| Métrica | Cálculo | Card/Seção |
|---------|---------|------------|
| **Margem Média (%)** | AVG(lucro/valor × 100) do mês | Card novo ou dentro de KPIs |
| **R$/Hora Médio** | AVG(rentabilidade_hora) das vendas com horas > 0 | Card novo |
| **Taxa de Conversão** | vendas_mes / artes_disponíveis × 100 | Card novo |
| **Projeção Mensal** | (faturamento_atual / dias_passados) × dias_total | Card ou sub-info |
| **Melhor Dia da Semana** | GROUP BY DAYOFWEEK(data_venda) | Insight textual |
| **Forma Pgto Preferida** | MODE(forma_pagamento) do mês | Insight textual |

#### Seção "Insights Rápidos"

Card com 3-4 insights textuais automáticos:
- "📈 Seu melhor dia de vendas é Sexta-feira"
- "💳 PIX é a forma de pagamento mais usada (68%)"
- "⏱️ Sua rentabilidade média é R$ 45,20/h"
- "🎯 Projeção do mês: R$ 3.200 (85% da meta)"

---

### 📋 MELHORIA 5 — PERÍODO SELECIONÁVEL

**Complexidade:** Média  
**Pré-requisito:** M1 ✅  
**Arquivos:** DashboardController.php (editar), views/dashboard/index.php (editar)

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Dropdown de período** | "Mês Atual", "Últimos 3 Meses", "Últimos 6 Meses", "Este Ano", "Tudo" |
| **Filtro aplicado a** | Cards de valor (faturamento, lucro, ticket) + gráficos |
| **Cards fixos** | Total Artes e Meta Mês NÃO mudam com período (são sempre atuais) |
| **URL parameter** | `?periodo=3m` / `?periodo=6m` / `?periodo=ano` / `?periodo=total` |
| **Gráficos dinâmicos** | Barras/linha ajustam para mostrar o período selecionado |

---

### 📋 MELHORIA 6 — AUTO-REFRESH E POLISH

**Complexidade:** Baixa  
**Pré-requisito:** M2 ✅  
**Arquivos:** views/dashboard/index.php (editar JS)

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Auto-refresh** | Toggle para atualizar cards a cada 60s via `/dashboard/refresh` |
| **Animações** | CountUp nos números dos cards (animação de contagem) |
| **Estados vazios** | Ilustrações SVG amigáveis quando não há dados |
| **Loading skeleton** | Placeholder animado enquanto dados carregam |
| **Timestamp** | "Atualizado há 5 min" no rodapé |

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos dos Services Usados pelo Dashboard

| Método | Service | Retorno Real | Adaptação no Dashboard | Usado em |
|--------|---------|-------------|----------------------|----------|
| `getEstatisticas()` | ArteService | `['disponivel', 'em_producao', 'vendida', 'reservada']` | `adaptarArtesStats()` → `['total', 'disponiveis', 'vendidas', ...]` | Cards + Doughnut |
| `getDisponiveisParaVenda()` | ArteService | Array de Arte objects (disponivel + em_producao) | Nenhuma | Subtexto card + Lista |
| `getVendasMesAtual()` | VendaService | Array de Venda objects | M1: `calcularLucroMes()` itera p/ lucro | Cards (count + lucro) |
| `getTotalMes(?mesAno)` | VendaService | float | Nenhuma | Card faturamento |
| `getVendasMensais(6)` | VendaService | `[['mes', 'quantidade', 'total', 'lucro'], ...]` | M1: `calcularTendencias()` usa para comparar meses | Gráficos + Tendências |
| `getRankingRentabilidade(5)` | VendaService | `[['arte_nome', 'valor', 'rentabilidade_hora'], ...]` | Nenhuma | Lista ranking |
| `getResumoDashboard()` | MetaService | `['valor_meta', 'valor_realizado', 'porcentagem', 'status']` | Nenhuma | Card + Semi-doughnut |
| `getMetasEmRisco()` | MetaService | `['alerta' => bool, 'projecao' => float, ...]` | Nenhuma | Alerta condicional |
| `getTopClientes(5)` | ClienteService | Array de arrays com `nome, total_compras, valor_total_compras` | Nenhuma | Tabela |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível
**Relevância Dashboard:** ✅ RESOLVIDO — `limparDadosFormulario()` adicionado no FIX D1.

### Bug B9: Dados Residuais
**Relevância Dashboard:** ✅ RESOLVIDO — Mesmo fix D1 cobre este cenário.

### Bug Global Scope ($GLOBALS)
**Relevância Dashboard:** ⚠️ NÃO AFETA — View do Dashboard não usa `global $variavel` nem helpers com `extract()`.

### Bug D5: Chaves ArteService inconsistentes
**Relevância Dashboard:** ✅ RESOLVIDO — `adaptarArtesStats()` converte formato no Controller sem alterar módulo Artes.

---

## 📌 DIFERENÇAS vs MÓDULOS CRUD

| Aspecto | Módulos CRUD | Dashboard |
|---------|-------------|-----------|
| Model/Repository/Service | Próprios | Nenhum (usa dos outros) |
| Fase 1 | 12 testes CRUD | 12 testes de exibição + integridade |
| Melhorias M1-M6 | Paginação, ordenação, filtros, stats, gráficos | Cards, layout, atividades, KPIs, período, polish |
| Formulários | Create/Edit/Delete | Nenhum (somente leitura) |
| Risco de regressão | Médio (FK, constraints) | Alto (depende de 4 Services — qualquer mudança pode quebrar) |
| Testes | phpMyAdmin + browser | Browser + console JS + verificação cruzada com banco |

---

## 📌 SEQUÊNCIA RECOMENDADA

```
ESTABILIZAÇÃO
├── ✅ Fase 1: Testes browser T1-T12 + correção de bugs (03/03/2026)

MELHORIAS (sequência recomendada)
├── ✅ M1: Cards aprimorados (+3 cards + tendências ↑↓%) — 05/03/2026
├── ✅ M2: Layout e responsividade (3 collapses + tooltips + mobile) — 05/03/2026
├── 🎯 M3: Atividades recentes (timeline — independente) ← PRÓXIMA
├── M4: KPIs avançados (insights inteligentes — depende de M1)
├── M5: Período selecionável (filtro global — depende de M1+M2)
└── M6: Auto-refresh e polish (final — depende de M2)
```

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                             → COMPLETO (Fase 1 + 6/6)
2. ✅ Clientes     — independente                             → COMPLETO (Fase 1 + 6/6)
3. ✅ Metas        — independente (atualizado por Vendas)      → COMPLETO (Fase 1 + 6/6)
4. ✅ Artes        — depende de Tags (✅)                       → COMPLETO (Fase 1 + 6/6)
5. ✅ Vendas       — depende de Artes + Clientes + Metas       → COMPLETO (Fase 1 + 6/6)
6. ✅ DASHBOARD   — depende de TODOS (Artes+Vendas+Metas+Clientes) → FASE 1 + M1 + M2 ★
```

### Por que Dashboard foi o último?

1. **Dependência máxima:** Consome dados de todos os 4 Services
2. **Sem CRUD próprio:** Só exibe — bugs nos outros módulos se propagam aqui
3. **Agora é seguro:** Com todos os 5 módulos CRUD estabilizados (6/6 cada), os dados que o Dashboard consome são confiáveis

---

## 🗂️ ARQUIVOS ENTREGUES

### Fase 1

| Arquivo | Caminho | Fixes Aplicados |
|---------|---------|-----------------|
| DashboardController.php | `src/Controllers/` | D1 + D5/CHAVES + D8 |
| main.php | `views/layouts/` | D2 (Chart.js 4.4.7 centralizado) |
| index.php | `views/dashboard/` | D2 (removido `<script>` Chart.js duplicado) |
| index.php | `views/artes/` | D2 (removido `<script>` Chart.js duplicado) |
| index.php | `views/metas/` | D2 (removido `<script>` Chart.js duplicado) |
| index.php | `views/tags/` | D2 (removido `<script>` Chart.js duplicado) |

### Melhoria M1

| Arquivo | Caminho | Alterações |
|---------|---------|-----------|
| DashboardController.php | `src/Controllers/` | +3 métodos privados (calcularLucroMes, calcularTendencias, calcularVariacao), index() ampliado, refresh() ampliado |
| index.php | `views/dashboard/` | Cards 4→6, 1 linha→2 linhas, helper renderTendencia(), badges ↑↓% |

### Melhoria M2

| Arquivo | Caminho | Alterações |
|---------|---------|-----------|
| index.php | `views/dashboard/` | 3 seções colapsáveis (graficosCollapse, rankingsCollapse, detalhesCollapse), tooltips nos 6 cards, setupCollapse() DRY, Chart.resize(), responsividade col-6/col-lg-4 |

---

**Última atualização:** 05/03/2026  
**Status:** ✅ Fase 1 + M1 + M2 completas — M3-M6 pendentes  
**Próxima ação:** Melhoria M3 (Atividades Recentes)  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅, Vendas ✅
