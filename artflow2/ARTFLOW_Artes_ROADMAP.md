# ArtFlow 2.0 — Módulo Artes: Documentação Completa

**Data:** 22/02/2026  
**Status Geral:** ✅ MÓDULO 100% COMPLETO — Fase 1 + 6/6 Melhorias + Cross-Module OK  
**Versão Base:** CRUD estabilizado + Paginação + Filtros combinados + Ordenação dinâmica + Upload de Imagem + Estatísticas + Gráficos + Cards Lucro/Rentabilidade  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Artes do ArtFlow 2.0 é o módulo central do sistema — gerencia o portfólio de obras artísticas, incluindo dados de produção (tempo, complexidade, custo), status de disponibilidade, imagens das obras e categorização via Tags (relacionamento N:N). O módulo depende de Tags (seletor no formulário) e é pré-requisito para o módulo de Vendas (select de arte_id no formulário de venda) e para o Dashboard (estatísticas e gráficos).

O módulo passou por uma fase de estabilização com **11 bugs corrigidos** em 4 sessões de trabalho (15/02/2026), cobrindo backend (Controller, Service, Validator) e frontend (4 views). Todos os 12 testes CRUD passaram com sucesso. A **Melhoria 1 (Paginação)** foi implementada em 16/02/2026 com 12/12 testes OK, incluindo filtros combinados (status + tag + busca simultâneos) que antecipam a Melhoria 3. A **Melhoria 2 (Ordenação Dinâmica)** foi implementada em 16/02/2026 com 10/10 testes OK, adicionando 6 colunas ordenáveis com headers clicáveis e botões de ordenação. A **Melhoria 4 (Upload de Imagem)** foi implementada em 20/02/2026 com 12/12 testes OK, adicionando upload seguro de imagens JPG/PNG/WEBP com validação por MIME type real, preview JavaScript, thumbnails na listagem e imagem ampliada no show. As **Melhorias 5 e 6** foram implementadas simultaneamente em 21/02/2026, adicionando cards de métricas (Custo/Hora, Preço Sugerido, Progresso) no show.php e cards financeiros + gráficos Chart.js (Doughnut status + Barras complexidade) no index.php. A **pendência cross-module** (Cards Lucro + Rentabilidade) foi implementada em 22/02/2026 após a estabilização do módulo Vendas, completando o show.php com 5 cards de métricas.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 11 bugs corrigidos, 12/12 testes | ✅ COMPLETA (15/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | ✅ COMPLETA (16/02/2026) |
| Melhoria 2 | Ordenação dinâmica (6 colunas clicáveis) | ✅ COMPLETA (16/02/2026) |
| Melhoria 3 | Filtros combinados (status + tag + busca simultâneos) | ✅ COMPLETA (via M1) — UI já funcional |
| Melhoria 4 | Upload de imagem (JPG/PNG/WEBP, 2MB, segurança) | ✅ COMPLETA (20/02/2026) |
| Melhoria 5 | Estatísticas por arte (cards métricas no show.php) | ✅ COMPLETA (21/02/2026) |
| Melhoria 6 | Gráficos de distribuição (Chart.js — status + complexidade) | ✅ COMPLETA (21/02/2026) |
| Cross-Module | Cards Lucro + Rentabilidade (Artes ↔ Vendas) | ✅ COMPLETA (22/02/2026) |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | — | ✅ COMPLETA |
| 2 | Ordenação dinâmica (6 colunas) | Baixa | Melhoria 1 ✅ | ✅ COMPLETA |
| 3 | Filtros combinados (status + tag + busca) | Média | Melhoria 1 ✅ | ✅ COMPLETA (via M1) |
| 4 | Upload de imagem (JPG/PNG/WEBP, 2MB) | Média | — | ✅ COMPLETA |
| 5 | Estatísticas por arte (cards no show.php) | Média | — | ✅ COMPLETA |
| 6 | Gráfico de distribuição (Doughnut + Barras) | Baixa | — | ✅ COMPLETA |

### ✅ PENDÊNCIA CROSS-MODULE RESOLVIDA (Artes ↔ Vendas)

| Pendência | Depende de | Onde implementado | Status |
|-----------|------------|-------------------|--------|
| Card **Lucro** no show.php | Tabela `vendas` (preço de venda) | ArteService + show.php | ✅ COMPLETO (22/02/2026) |
| Card **Rentabilidade** no show.php | Tabela `vendas` + horas_trabalhadas | ArteService + show.php | ✅ COMPLETO (22/02/2026) |

**Implementação (22/02/2026):**
1. `ArteService` recebeu `VendaRepository` como 3ª dependência no construtor (auto-wiring)
2. `getDadosVenda(Arte)` — método privado, busca venda via `findFirstBy('arte_id', $id)`
3. `calcularLucro(Arte)` — retorna `['valor_venda', 'lucro', 'margem_percentual']` ou null
4. `calcularRentabilidade(Arte)` — retorna R$/hora ou null
5. `getMetricasArte()` agora retorna **5 métricas** (antes 3): + `lucro` + `rentabilidade`
6. `show.php` — Row 2 condicional com 2 cards (só aparece se `status === 'vendida'`)
7. Card Lucro: valor de venda, lucro em R$, margem % com barra visual (verde/vermelho)
8. Card Rentabilidade: R$/hora + comparação multiplicadora com custo/hora

**Layout final do show.php:**
```
ARTE DISPONÍVEL (3 cards):
┌──────────┐ ┌──────────────┐ ┌───────────┐
│ Custo/h  │ │ Preço Suger. │ │ Progresso │
└──────────┘ └──────────────┘ └───────────┘

ARTE VENDIDA (5 cards):
┌──────────┐ ┌──────────────┐ ┌───────────┐
│ Custo/h  │ │ Preço Suger. │ │ Progresso │
└──────────┘ └──────────────┘ └───────────┘
┌────────────────┐ ┌──────────────────────┐
│ Lucro da Venda │ │ Rentabilidade/Hora   │
└────────────────┘ └──────────────────────┘
```

**Segurança:** getDadosVenda() tem try/catch — erro na consulta NÃO quebra a página. Cards condicionais: sem risco de undefined. Log de inconsistências para artes vendidas sem registro.

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Arte.php                       🔧 Melhoria 4 (+ getImagem, setImagem)
├── Repositories/
│   └── ArteRepository.php             🔧 M1 + M6 (+ allPaginated, countAll, countByComplexidade, getResumoFinanceiro)
├── Services/
│   └── ArteService.php                🔧 M4 + M5 + M6 + Cross-Module (+ VendaRepository, getDadosVenda, calcularLucro, calcularRentabilidade)
├── Controllers/
│   └── ArteController.php             🔧 M4 + M5 + M6 (store/update + $metricas em show + gráficos em index)
└── Validators/
    └── ArteValidator.php              🔧 Melhoria 4 (+ validateImagem com 4 camadas de segurança)

views/
└── artes/
    ├── index.php                      🔧 M6 (cards financeiros + gráficos Chart.js — substitui cards status antigos)
    ├── create.php                     🔧 Melhoria 4 (+ enctype multipart, input file, preview JS)
    ├── show.php                       🔧 M5 + Cross-Module (5 cards métricas: 3 base + 2 condicionais vendida)
    └── edit.php                       🔧 Melhoria 4 (+ imagem atual, checkbox remover, preview nova)

public/
└── uploads/
    └── artes/
        └── .htaccess                  🆕 Melhoria 4 (bloqueia execução PHP, permite apenas imagens)

artflow2/
└── .htaccess                          🔧 Melhoria 4 (+ RewriteRule ^uploads/ → public/uploads/)

database/
├── migrations/
│   ├── 001_create_artes_table.php     ✅ Executada (coluna imagem VARCHAR(255) já existe)
│   └── 006_create_arte_tags_table.php ✅ Executada (pivot N:N)
└── seeds/
    └── ArteSeeder.php                 ✅ Executado
```

### Dependências entre Classes

```
ArteController → ArteService + TagService
ArteService    → ArteRepository + TagRepository + VendaRepository + ArteValidator
(Depende de Tags para seletor no formulário)
(Depende de VendaRepository para cards Lucro/Rentabilidade — Cross-Module 22/02/2026)

ArteController::index()     usa ArteService::listarPaginado() + getDistribuicaoComplexidade() + getResumoCards() [M6]
ArteController::create()    usa TagService::listar() para checkboxes de tags
ArteController::store()     usa ArteService::criar($dados, $arquivo) [M4: + $arquivo]
ArteController::show()      usa ArteService::getTags() + getMetricasArte() [M5: 5 métricas unificadas]
ArteController::edit()      usa TagService::listar() + TagService::getTagIdsArte()
ArteController::update()    usa ArteService::atualizar($id, $dados, $arquivo, $removerImagem) [M4]
ArteController::destroy()   usa ArteService::remover() [M4: remove imagem física antes de deletar]
ArteController::alterarStatus()  usa ArteService::alterarStatus()
ArteController::adicionarHoras() usa ArteService::adicionarHoras()
```

**Nota sobre acoplamento:** O módulo Artes depende de Tags (✅ COMPLETO) para o seletor de categorias e de VendaRepository (✅ leitura apenas) para cards de métricas de artes vendidas.

**Quem depende de Artes:**
- VendaService usa ArteRepository para buscar arte e atualizar status para 'vendida'
- VendaController precisa de ArteService para listar artes disponíveis no formulário de venda
- DashboardController usa ArteService.getEstatisticas() e ArteService.getDisponiveisParaVenda()

### Tabela `artes` (Banco de Dados)

```sql
CREATE TABLE artes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,                                 -- Nome da arte
    descricao TEXT NULL,                                         -- Descrição detalhada
    tempo_medio_horas DECIMAL(6,2) NULL,                        -- Tempo estimado em horas
    complexidade ENUM('baixa','media','alta') DEFAULT 'media',  -- Nível de dificuldade
    preco_custo DECIMAL(10,2) DEFAULT 0,                        -- Custo de produção em R$
    horas_trabalhadas DECIMAL(8,2) DEFAULT 0,                   -- Horas já investidas
    status ENUM('disponivel','em_producao','vendida','reservada') DEFAULT 'disponivel',
    imagem VARCHAR(255) NULL,                                   -- [M4] Caminho relativo (ex: uploads/artes/arte_1_1708123456.jpg)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_artes_nome (nome),
    INDEX idx_artes_status (status),
    INDEX idx_artes_complexidade (complexidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabela `arte_tags` (Pivot N:N)

```sql
CREATE TABLE arte_tags (
    arte_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (arte_id, tag_id),
    FOREIGN KEY (arte_id) REFERENCES artes(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ✅ MELHORIA 5 — ESTATÍSTICAS POR ARTE (COMPLETA)

**Implementada em:** 21/02/2026 (3 cards base) + 22/02/2026 (2 cards cross-module)  
**Complexidade:** Média  
**Testes:** 5/5 OK (T7-T11 do guia M5+M6) + testes cross-module OK  
**Arquivos alterados:** ArteService (+VendaRepository +5 métodos), ArteController (show enriquecido), views/artes/show.php (SUBSTITUÍDO 2×)

### O Que Foi Implementado

| Recurso | Descrição | Data |
|---------|-----------|------|
| **Card Custo/Hora** | R$/hora investida — exibe "N/A" se horas = 0 | 21/02/2026 |
| **Card Preço Sugerido** | Multiplicador 2.5× sobre custo + margem calculada | 21/02/2026 |
| **Card Progresso** | Barra visual 0-100% + % real + horas faltantes | 21/02/2026 |
| **Barra vermelha** | Se horas ultrapassaram tempo estimado (>100%) | 21/02/2026 |
| **Card Lucro da Venda** | Valor venda, lucro R$, margem % com barra (verde/vermelho) | 22/02/2026 |
| **Card Rentabilidade/Hora** | R$/hora de lucro + comparação multiplicadora vs custo/hora | 22/02/2026 |
| **Substituição de duplicidade** | Cards financeiros antigos e barra de progresso antiga removidos | 21/02/2026 |

### Métodos Adicionados/Alterados

**ArteService:**
```php
// [M5] Calcula progresso baseado em horas_trabalhadas vs tempo_medio_horas
public calcularProgresso(Arte $arte): ?array
// Retorna: ['percentual' => 0-100, 'valor_real' => float, 'horas_faltam' => float] | null

// [M5 Cross-Module] Busca dados da venda associada (só para status='vendida')
private getDadosVenda(Arte $arte): ?array
// Retorna: ['valor_venda', 'lucro', 'rentabilidade_hora', 'data_venda', 'forma_pagamento'] | null

// [M5 Cross-Module] Calcula lucro da venda + margem percentual
public calcularLucro(Arte $arte): ?array
// Retorna: ['valor_venda', 'lucro', 'margem_percentual'] | null

// [M5 Cross-Module] Calcula rentabilidade por hora baseada no lucro
public calcularRentabilidade(Arte $arte): ?float
// Retorna: R$/hora | null

// [M5] Centraliza TODAS as métricas da arte para o show.php
public getMetricasArte(Arte $arte): array
// Retorna: [
//   'custo_por_hora'   => float|null,       (sempre)
//   'preco_sugerido'   => float,            (sempre)
//   'progresso'        => array|null,       (se tem tempo estimado)
//   'lucro'            => array|null,       (SÓ se status='vendida')
//   'rentabilidade'    => float|null,       (SÓ se status='vendida' + horas>0)
// ]
```

### Mudanças na View (show.php)

| Antes (21/02) | Depois (22/02) |
|----------------|----------------|
| 3 cards M5 (Custo/Hora, Preço Sugerido, Progresso) | 3 cards base + Row 2 condicional |
| TODO comment para Lucro/Rentabilidade | 2 cards implementados (col-md-6 cada) |
| — | Card Lucro: barra margem %, cores condicionais |
| — | Card Rentabilidade: multiplicador vs custo/hora |

---

## ✅ MELHORIA 6 — GRÁFICOS DE DISTRIBUIÇÃO (COMPLETA)

**Implementada em:** 21/02/2026  
**Complexidade:** Baixa  
**Testes:** 5/5 OK (T12-T16 do guia M5+M6)

### O Que Foi Implementado

| Recurso | Tipo Chart.js | Dados |
|---------|--------------|-------|
| **Distribuição por Status** | Doughnut | COUNT(*) GROUP BY status |
| **Distribuição por Complexidade** | Barras horizontais | COUNT(*) GROUP BY complexidade |

### Métodos Adicionados

**ArteRepository:**
```php
// [M6] COUNT(*) GROUP BY complexidade
public countByComplexidade(): array

// [M6] Query única com SUM/COUNT — retorna total, valor_estoque, horas_totais, disponiveis
public getResumoFinanceiro(): array
```

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível (Afeta TODOS os módulos)

**Status no módulo Artes:** ✅ Workaround aplicado no ArteController (grava direto em `$_SESSION['_errors']`).

### Bug B9: Dados Residuais no Edit

**Status no módulo Artes:** ✅ Workaround aplicado — `limparDadosFormulario()` chamado em index(), edit() e show().

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos chamados no Controller vs existência no Service

| Método chamado no Controller | Existe no Service? | Status |
|------------------------------|--------------------|--------|
| `ArteService::listarPaginado($filtros)` | ✅ Sim | ✅ Adicionado Melhoria 1 |
| `ArteService::listar($filtros)` | ✅ Sim | ✅ Mantido para compatibilidade |
| `ArteService::buscar($id)` | ✅ Sim | ✅ Verificado |
| `ArteService::criar($dados, $arquivo)` | ✅ Sim | ✅ Atualizado Melhoria 4 (+$arquivo) |
| `ArteService::atualizar($id, $dados, $arquivo, $removerImagem)` | ✅ Sim | ✅ Atualizado Melhoria 4 |
| `ArteService::remover($id)` | ✅ Sim | ✅ Atualizado Melhoria 4 (remove imagem física) |
| `ArteService::alterarStatus($id, $status)` | ✅ Sim | ✅ Verificado + Corrigido (T11) |
| `ArteService::adicionarHoras($id, $horas)` | ✅ Sim | ✅ Verificado |
| `ArteService::getEstatisticas()` | ✅ Sim | ✅ Verificado |
| `ArteService::getTags($id)` | ✅ Sim | ✅ Verificado |
| `ArteService::calcularCustoPorHora($arte)` | ✅ Sim | ✅ Verificado |
| `ArteService::calcularPrecoSugerido($arte)` | ✅ Sim | ✅ Verificado |
| `ArteService::getMetricasArte($arte)` | ✅ Sim | ✅ Adicionado M5, expandido Cross-Module (5 métricas) |
| `ArteService::calcularProgresso($arte)` | ✅ Sim | ✅ Adicionado Melhoria 5 |
| `ArteService::calcularLucro($arte)` | ✅ Sim | ✅ Adicionado Cross-Module (22/02/2026) |
| `ArteService::calcularRentabilidade($arte)` | ✅ Sim | ✅ Adicionado Cross-Module (22/02/2026) |
| `ArteService::getDistribuicaoComplexidade()` | ✅ Sim | ✅ Adicionado Melhoria 6 |
| `ArteService::getResumoCards()` | ✅ Sim | ✅ Adicionado Melhoria 6 |
| `TagService::listar()` | ✅ Sim (módulo Tags completo) | ✅ Verificado |
| `TagService::getTagIdsArte($id)` | ✅ Sim | ✅ Verificado |

### Métodos privados do ArteService (uso interno)

| Método | Adicionado em | Descrição |
|--------|---------------|-----------|
| `getDadosVenda($arte)` | Cross-Module (22/02) | Busca venda via findFirstBy('arte_id') — try/catch silencioso |
| `processarUploadImagem($arquivo, $arteId)` | Melhoria 4 | Move arquivo para public/uploads/artes/ |
| `removerImagemFisica($arte)` | Melhoria 4 | Remove arquivo de imagem do disco |
| `getUploadDirAbsoluto()` | Melhoria 4 | Caminho absoluto do diretório de uploads |
| `getPublicDir()` | Melhoria 4 | Caminho absoluto da pasta public/ (via dirname) |
| `validarTransicaoStatus($atual, $novo)` | Fase 1 | Valida máquina de estados de status |

---

## 📌 LIÇÕES APRENDIDAS

| Lição | Módulo/Fase | Contexto |
|-------|-------------|----------|
| `??` só testa null, `?:` testa falsy | Fase 1 (T1) | Filtros com string vazia precisam de `?? null ?: null` |
| Máquina de estados em 3 lugares | Fase 1 (T11) | Validator + Service + Views devem estar sincronizados |
| Nunca usar SCRIPT_FILENAME | M4-BUG1 | Entry point varia — usar `__DIR__` é determinístico |
| `.htaccess` duplo para uploads | M4 | Um bloqueia PHP, outro redireciona URLs |
| MIME via finfo_file | M4 | `$_FILES['type']` pode ser falsificado |
| Container altura fixa para Chart.js | M6 | Evita loop de redimensionamento (lição do Dashboard) |
| chart.resize() após collapse | M6 | Chart.js precisa recalcular após display:none → block |
| Substituir em vez de duplicar | M5+M6 | Views novas substituem cards/barras antigos por versões ricas |
| `findFirstBy()` do BaseRepository | Cross-Module | Usar métodos herdados em vez de criar métodos inexistentes |
| try/catch em consultas cross-module | Cross-Module | Falha em tabela externa NÃO deve quebrar o módulo principal |

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                         → COMPLETO (6/6)
2. ✅ Clientes     — independente                         → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)  → COMPLETO (6/6)
4. ✅ ARTES        — depende de Tags (✅) + VendaRepo (✅)  → COMPLETO (6/6 + Cross-Module) ★
5. ✅ Vendas       — depende de Artes + Clientes + Metas  → FASE 1 COMPLETA (22/02/2026)
```

### Histórico das Sessões

| # | Data | Foco | Entregas |
|---|------|------|----------|
| 1 | 15/02 manhã | Análise de bugs no código-fonte | Relatório com 9 bugs identificados |
| 2 | 15/02 manhã | Correção backend | ArteController.php + ArteValidator.php (7 bugs fixados) |
| 3 | 15/02 tarde | Correção views | 4 views corrigidas (index, show, create, edit) |
| 4 | 15/02 noite | Re-teste + fixes finais | T1 (busca) + T11 (transição status) → 12/12 OK |
| 5 | 16/02 manhã | Melhoria 1 — Paginação | 4 arquivos (Repository, Service, Controller, view) → 12/12 testes OK |
| 6 | 16/02 tarde | Melhoria 2 — Ordenação | 1 arquivo (view index.php) → 10/10 testes OK |
| 7 | 20/02 manhã-tarde | Melhoria 4 — Upload de Imagem | 8 arquivos + 4 diagnósticos + 1 bug corrigido → 12/12 testes OK |
| 8 | 21/02 manhã | Melhorias 5+6 — Estatísticas + Gráficos | 5 arquivos (Repository+2, Service+4, Controller, show, index) → 12/12 testes OK |
| 9 | 22/02 manhã | Cross-Module — Cards Lucro + Rentabilidade | ArteService (+VendaRepository +3 métodos) + show.php (2 cards condicionais) |

---

**Última atualização:** 22/02/2026  
**Status:** ✅ MÓDULO 100% COMPLETO (Fase 1 + 6/6 Melhorias + Cross-Module OK)  
**Pendências cross-module:** ✅ TODAS RESOLVIDAS  
**Próximo módulo:** 🎯 Vendas Melhorias (M1-M6)
