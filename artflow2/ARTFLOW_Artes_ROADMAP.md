# ArtFlow 2.0 — Módulo Artes: Documentação Completa

**Data:** 21/02/2026  
**Status Geral:** ✅ MÓDULO 100% COMPLETO — Fase 1 + 6/6 Melhorias implementadas  
**Versão Base:** CRUD estabilizado + Paginação + Filtros combinados + Ordenação dinâmica + Upload de Imagem + Estatísticas + Gráficos  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Artes do ArtFlow 2.0 é o módulo central do sistema — gerencia o portfólio de obras artísticas, incluindo dados de produção (tempo, complexidade, custo), status de disponibilidade, imagens das obras e categorização via Tags (relacionamento N:N). O módulo depende de Tags (seletor no formulário) e é pré-requisito para o módulo de Vendas (select de arte_id no formulário de venda) e para o Dashboard (estatísticas e gráficos).

O módulo passou por uma fase de estabilização com **11 bugs corrigidos** em 4 sessões de trabalho (15/02/2026), cobrindo backend (Controller, Service, Validator) e frontend (4 views). Todos os 12 testes CRUD passaram com sucesso. A **Melhoria 1 (Paginação)** foi implementada em 16/02/2026 com 12/12 testes OK, incluindo filtros combinados (status + tag + busca simultâneos) que antecipam a Melhoria 3. A **Melhoria 2 (Ordenação Dinâmica)** foi implementada em 16/02/2026 com 10/10 testes OK, adicionando 6 colunas ordenáveis com headers clicáveis e botões de ordenação. A **Melhoria 4 (Upload de Imagem)** foi implementada em 20/02/2026 com 12/12 testes OK, adicionando upload seguro de imagens JPG/PNG/WEBP com validação por MIME type real, preview JavaScript, thumbnails na listagem e imagem ampliada no show. As **Melhorias 5 e 6** foram implementadas simultaneamente em 21/02/2026, adicionando cards de métricas (Custo/Hora, Preço Sugerido, Progresso) no show.php e cards financeiros + gráficos Chart.js (Doughnut status + Barras complexidade) no index.php.

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

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | — | ✅ COMPLETA |
| 2 | Ordenação dinâmica (6 colunas) | Baixa | Melhoria 1 ✅ | ✅ COMPLETA |
| 3 | Filtros combinados (status + tag + busca) | Média | Melhoria 1 ✅ | ✅ COMPLETA (via M1) |
| 4 | Upload de imagem (JPG/PNG/WEBP, 2MB) | Média | — | ✅ COMPLETA |
| 5 | Estatísticas por arte (cards no show.php) | Média | — | ✅ COMPLETA |
| 6 | Gráfico de distribuição (Doughnut + Barras) | Baixa | — | ✅ COMPLETA |

### ⚠️ PENDÊNCIA CROSS-MODULE (Artes ↔ Vendas)

| Pendência | Depende de | Onde implementar | Status |
|-----------|------------|------------------|--------|
| Card **Lucro** no show.php | Tabela `vendas` (preço de venda) | ArteService + show.php | ⏳ Após Vendas estável |
| Card **Rentabilidade** no show.php | Tabela `vendas` + horas_trabalhadas | ArteService + show.php | ⏳ Após Vendas estável |

**Detalhes:** Os cards de Lucro (`preço_venda - preço_custo`) e Rentabilidade (`lucro / horas_trabalhadas`) só fazem sentido para artes vendidas e dependem de uma query na tabela `vendas`. Como o módulo Vendas ainda não foi testado/estabilizado, esses 2 cards foram postergados. TODOs estão marcados no código (`ArteService::getMetricasArte()` e `views/artes/show.php`).

**Implementação futura (pós-Vendas):**
1. Adicionar `calcularLucro(Arte $arte)` no ArteService — query `SELECT valor FROM vendas WHERE arte_id = ?`
2. Adicionar `calcularRentabilidade(Arte $arte)` no ArteService — `lucro / horas_trabalhadas`
3. Adicionar 2 cards extras no show.php (reorganizar de 3 para 5 cards)
4. Condição: só exibir quando `$arte->getStatus() === 'vendida'`

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
│   └── ArteService.php                🔧 M4 + M5 + M6 (+ upload, calcularProgresso, getMetricasArte, getDistribuicaoComplexidade, getResumoCards)
├── Controllers/
│   └── ArteController.php             🔧 M4 + M5 + M6 (store/update + $metricas em show + gráficos em index)
└── Validators/
    └── ArteValidator.php              🔧 Melhoria 4 (+ validateImagem com 4 camadas de segurança)

views/
└── artes/
    ├── index.php                      🔧 M6 (cards financeiros + gráficos Chart.js — substitui cards status antigos)
    ├── create.php                     🔧 Melhoria 4 (+ enctype multipart, input file, preview JS)
    ├── show.php                       🔧 M5 (3 cards métricas — substitui cards financeiros antigos + barra progresso)
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
ArteService    → ArteRepository + TagRepository + ArteValidator
(Depende de Tags para seletor no formulário)

ArteController::index()     usa ArteService::listarPaginado() + getDistribuicaoComplexidade() + getResumoCards() [M6]
ArteController::create()    usa TagService::listar() para checkboxes de tags
ArteController::store()     usa ArteService::criar($dados, $arquivo) [M4: + $arquivo]
ArteController::show()      usa ArteService::getTags() + getMetricasArte() [M5: métricas unificadas]
ArteController::edit()      usa TagService::listar() + TagService::getTagIdsArte()
ArteController::update()    usa ArteService::atualizar($id, $dados, $arquivo, $removerImagem) [M4]
ArteController::destroy()   usa ArteService::remover() [M4: remove imagem física antes de deletar]
ArteController::alterarStatus()  usa ArteService::alterarStatus()
ArteController::adicionarHoras() usa ArteService::adicionarHoras()
```

**Nota sobre acoplamento:** O módulo Artes depende de Tags (✅ COMPLETO) para o seletor de categorias. NÃO depende de Vendas ou Metas.

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
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,

    INDEX idx_arte_tags_arte (arte_id),
    INDEX idx_arte_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Campos do Formulário (create.php / edit.php)

| Campo | Tipo HTML | Validação | Obrigatório | Notas |
|-------|-----------|-----------|-------------|-------|
| nome | text | max:150 | ✅ | — |
| descricao | textarea | — | ❌ | Texto livre |
| tempo_medio_horas | number (step 0.5) | min:0 | ❌ | Estimativa de produção |
| complexidade | select | in:baixa,media,alta | ✅ | Default: media |
| preco_custo | number (step 0.01) | min:0 | ✅ | Em R$ |
| horas_trabalhadas | number (step 0.5) | min:0 | ❌ | Acumulativo |
| status | select | in:disponivel,em_producao,vendida,reservada | ✅ | Default: disponivel |
| tags[] | checkbox multiple | IDs existentes | ❌ | Relacionamento N:N |
| imagem | file (accept .jpg,.png,.webp) | MIME + extensão + tamanho ≤2MB | ❌ | [M4] Preview JS antes de enviar |
| remover_imagem | checkbox | — | ❌ | [M4] Só no edit.php — remove imagem sem substituir |

### Rotas (9 total)

```
ARTES (7 RESTful + 2 extras)
  GET    /artes              → ArteController@index         (listar com filtros + ordenação + gráficos M6)
  GET    /artes/criar        → ArteController@create        (formulário criação)
  POST   /artes              → ArteController@store         (salvar nova + upload imagem)
  GET    /artes/{id}         → ArteController@show          (detalhes + tags + métricas M5 + imagem)
  GET    /artes/{id}/editar  → ArteController@edit          (formulário edição + imagem atual)
  PUT    /artes/{id}         → ArteController@update        (atualizar + sync tags + upload/remover imagem)
  DELETE /artes/{id}         → ArteController@destroy       (excluir — CASCADE remove arte_tags + remove imagem física)
  POST   /artes/{id}/status  → ArteController@alterarStatus (muda status sem editar tudo)
  POST   /artes/{id}/horas   → ArteController@adicionarHoras(incrementa horas_trabalhadas)
```

---

## ✅ FASE 1 — ESTABILIZAÇÃO CRUD (COMPLETA)

**Status:** ✅ 12/12 testes passando  
**Data de conclusão:** 15/02/2026  
**Sessões de trabalho:** 4 sessões no mesmo dia  
**Total de bugs corrigidos:** 11

### Checklist de Testes

| # | Operação | Rota | O que verificar | Status |
|---|----------|------|-----------------|--------|
| T1 | Listar | `GET /artes` | Carrega sem erros, exibe dados dos seeds, tags no filtro | ✅ |
| T2 | Criar (form) | `GET /artes/criar` | Formulário abre, checkboxes de tags aparecem | ✅ |
| T3 | Criar (salvar) | `POST /artes` | Validação funciona, salva no banco com tags associadas | ✅ |
| T4 | Visualizar | `GET /artes/{id}` | Exibe dados + tags + cálculos (custo/hora, preço sugerido) | ✅ |
| T5 | Editar (form) | `GET /artes/{id}/editar` | Preenche valores existentes, tags marcadas corretamente | ✅ |
| T6 | Editar (salvar) | `PUT /artes/{id}` | Atualiza dados + sync de tags funciona | ✅ |
| T7 | Excluir | `DELETE /artes/{id}` | Confirmação funciona, arte some, arte_tags CASCADE | ✅ |
| T8 | Filtro status | `GET /artes?status=disponivel` | Filtra corretamente | ✅ |
| T9 | Filtro tag | `GET /artes?tag_id=X` | Retorna artes da tag selecionada | ✅ |
| T10 | Busca | `GET /artes?termo=X` | Busca por nome e descrição | ✅ |
| T11 | Alterar status | `POST /artes/{id}/status` | Muda status sem editar toda a arte | ✅ |
| T12 | Adicionar horas | `POST /artes/{id}/horas` | Incrementa horas_trabalhadas | ✅ |

### Bugs Corrigidos — Resumo

| Bug | Arquivo | Problema | Correção |
|-----|---------|----------|----------|
| A1 | ArteValidator | Status 'reservada' ausente | Adicionado ao array $statusValidos |
| B8 | ArteController | Erros de validação invisíveis | Grava direto em $_SESSION['_errors'] |
| B9 | ArteController | Dados residuais no edit | limparDadosFormulario() em index/edit/show |
| — | ArteController | IDs string do Router | $id = (int) $id em todos os métodos |
| — | ArteController | Falta $statusList | Passa array para create/edit |
| T1 | ArteService | Busca retorna 0 | Normalização filtros com ?? ?: |
| T11 | ArteService | Transição 'reservada' bloqueada | Array de transições expandido |
| — | 4 views | URLs hardcoded + reservada | url() helper + match() corrigidos |

---

## ✅ MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (COMPLETA)

**Implementada em:** 16/02/2026 | **Testes:** 12/12 OK  
**Arquivos:** ArteRepository, ArteService, ArteController, views/artes/index.php

- 12 artes por página com controles Bootstrap 5
- Filtros combinados (status + tag + busca simultâneos) via WHERE dinâmico
- Preservação de estado via URL params (helper `arteUrl()`)
- Indicador "Mostrando X–Y de Z artes"
- Whitelist de 6 colunas para ORDER BY (preparação para M2)

---

## ✅ MELHORIA 2 — ORDENAÇÃO DINÂMICA (COMPLETA)

**Implementada em:** 16/02/2026 | **Testes:** 10/10 OK  
**Arquivos:** views/artes/index.php (backend já pronto via M1)

- 6 botões de ordenação: Nome, Complexidade, Custo, Horas, Status, Data
- Headers da tabela clicáveis com setas contextuais (▲/▼)
- Toggle automático ASC↔DESC + direções padrão por tipo (texto ASC, numérico DESC)
- Helpers `arteSortUrl()` e `arteSortIcon()` na view

---

## ✅ MELHORIA 3 — FILTROS COMBINADOS (BACKEND PRONTO VIA M1)

**Status:** ✅ BACKEND + UI JÁ FUNCIONAIS — Implementados junto com Melhoria 1

- `allPaginated()` constrói WHERE dinâmico com AND (não if/elseif exclusivo)
- Barra de filtros com 3 campos simultâneos + botão "Limpar Filtros"

---

## ✅ MELHORIA 4 — UPLOAD DE IMAGEM (COMPLETA)

**Implementada em:** 20/02/2026 | **Testes:** 12/12 OK | **1 bug corrigido (M4-BUG1)**  
**Arquivos:** ArteService, ArteController, ArteValidator, Arte (Model), 4 views, 2 .htaccess

- Upload seguro JPG/PNG/WEBP até 2MB com validação por MIME type real (finfo_file)
- Nomenclatura `arte_{id}_{timestamp}.{ext}` — evita colisões e cache stale
- Preview JavaScript antes de enviar + substituição/remoção no edit
- Thumbnail 45x45 na listagem + imagem ampliada 400px no show
- Segurança: `.htaccess` bloqueia PHP no diretório de uploads
- Bug M4-BUG1: `getPublicDir()` corrigido de `SCRIPT_FILENAME` para `dirname(__DIR__, 2)`

---

## ✅ MELHORIA 5 — ESTATÍSTICAS POR ARTE (COMPLETA)

**Implementada em:** 21/02/2026  
**Complexidade:** Média  
**Testes:** 5/5 OK (T7-T11 do guia M5+M6)  
**Arquivos alterados:** ArteService (+2 métodos), ArteController (show enriquecido), views/artes/show.php (SUBSTITUÍDO)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **Card Custo/Hora** | R$/hora investida — exibe "N/A" se horas = 0 |
| **Card Preço Sugerido** | Multiplicador 2.5× sobre custo + margem calculada |
| **Card Progresso** | Barra visual 0-100% + % real + horas faltantes |
| **Barra vermelha** | Se horas ultrapassaram tempo estimado (>100%) |
| **Substituição de duplicidade** | Cards financeiros antigos e barra de progresso antiga removidos |

### Métodos Adicionados

**ArteService:**
```php
// [M5] Calcula progresso baseado em horas_trabalhadas vs tempo_medio_horas
public calcularProgresso(Arte $arte): ?array
// Retorna: ['percentual' => 0-100, 'valor_real' => float, 'horas_faltam' => float] | null

// [M5] Centraliza TODAS as métricas da arte para o show.php
public getMetricasArte(Arte $arte): array
// Retorna: ['custo_por_hora' => float|null, 'preco_sugerido' => float, 'progresso' => array|null]
// TODO: adicionar 'lucro' e 'rentabilidade' após módulo Vendas estável
```

### Mudanças na View (show.php — arquivo SUBSTITUÍDO)

| Antes | Depois |
|-------|--------|
| 3 cards bg-light (Custo, Custo/Hora, Preço Sugerido) dentro de col-lg-8 | 3 cards M5 com border-start colorida, ícones grandes, entre header e row |
| Barra de progresso dentro de "Informações Técnicas" | Card de Progresso M5 com barra + % + horas faltantes + cor vermelha se >100% |
| Variáveis locais $custoHora, $precoSugerido, $progresso | $metricas via ArteService::getMetricasArte() |
| Card "Info Técnica" com 3 colunas (col-md-4) | 4 colunas (col-md-3): Complexidade, Custo Material, Tempo Estimado, Horas |

### Decisões Técnicas

| Decisão | Justificativa |
|---------|---------------|
| **Substituir cards antigos** | Evita duplicidade de informação na mesma página |
| **$metricas centralizado** | Service como fonte única — view não calcula |
| **Progresso: percentual limitado a 100%** | Barra visual não ultrapassa container, mas valor_real preserva >100% |
| **Lucro/Rentabilidade postergados** | Dependem de query na tabela vendas — módulo não testado |

---

## ✅ MELHORIA 6 — GRÁFICOS DE DISTRIBUIÇÃO (COMPLETA)

**Implementada em:** 21/02/2026  
**Complexidade:** Baixa  
**Testes:** 6/6 OK (T1-T6 do guia M5+M6)  
**Arquivos alterados:** ArteRepository (+2), ArteService (+2), ArteController (index enriquecido), views/artes/index.php (SUBSTITUÍDO)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **4 Cards de Resumo** | Total de Artes, Valor em Estoque, Horas Investidas, Disponíveis |
| **Gráfico Doughnut** | Distribuição por Status (4 fatias: Disponível, Em Produção, Vendida, Reservada) |
| **Gráfico Barras Horizontais** | Distribuição por Complexidade (3 barras: Baixa, Média, Alta) |
| **Collapse expansível** | Botão chevron para expandir/recolher gráficos |
| **Legenda manual HTML** | Bolinhas coloridas com valores numéricos por categoria |
| **Substituição de duplicidade** | Cards de contagem por status antigos substituídos (info agora no Doughnut) |
| **Fallback banco vazio** | Se $temDadosGrafico = false, exibe cards simples com zeros |
| **Bug corrigido** | `class="width: 60px;"` → `style="width: 60px;"` na coluna Imagem |

### Métodos Adicionados

**ArteRepository:**
```php
// [M6] GROUP BY complexidade — retorna ['baixa' => N, 'media' => N, 'alta' => N]
public countByComplexidade(): array

// [M6] Query única com SUM/COUNT — retorna total, valor_estoque, horas_totais, disponiveis
public getResumoFinanceiro(): array
```

**ArteService:**
```php
// [M6] Wrapper para ArteRepository::countByComplexidade()
public getDistribuicaoComplexidade(): array

// [M6] Wrapper para ArteRepository::getResumoFinanceiro()
public getResumoCards(): array
```

### Mudanças na View (index.php — arquivo SUBSTITUÍDO)

| Antes | Depois |
|-------|--------|
| 4 cards simples (Disponíveis, Em Produção, Vendidas, Reservadas) | 4 cards financeiros M6 (Total, Estoque, Horas, Disponíveis) com border-start + ícones |
| — | Card Gráficos com Doughnut (status) + Barras (complexidade) + collapse |
| — | CDN Chart.js 4.4.7 + script condicional |
| — | Fallback quando banco vazio (cards com zeros) |

### Fluxo Arquitetural M6

```
ArteController::index()
  ├─► ArteService::getDistribuicaoComplexidade()
  │     └─► ArteRepository::countByComplexidade()
  │           └─► SELECT complexidade, COUNT(*) GROUP BY complexidade
  │
  ├─► ArteService::getResumoCards()
  │     └─► ArteRepository::getResumoFinanceiro()
  │           └─► SELECT COUNT(*), SUM(CASE...), SUM(horas), SUM(CASE...)
  │
  └─► View: 4 cards + 2 gráficos Chart.js (condicional)
```

### Decisões Técnicas

| Decisão | Justificativa |
|---------|---------------|
| **Cards status → Doughnut** | Gráfico mostra mesma informação + proporção visual |
| **Chart.js 4.4.7** | Mesmo padrão de Tags M6 e Metas M3 |
| **maintainAspectRatio: false** | Container altura fixa 280px — evita loop de resize (lição Dashboard) |
| **Collapse com chart.resize()** | Chart.js precisa recalcular após display:none → block |
| **$temDadosGrafico** | Proteção contra Canvas vazio quando banco sem artes |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível (Afeta TODOS os módulos)

**Problema:** A classe `Response` armazena erros de validação em `$_SESSION['_flash']`, mas as funções helper `has_error()` e `errors()` leem de `$_SESSION['_errors']`. Resultado: validação falha silenciosamente.

**Status no módulo Artes:** ✅ Workaround aplicado no ArteController (grava direto em `$_SESSION['_errors']`).

### Bug B9: Dados Residuais no Edit

**Problema:** Após validação falhar no create, dados ficam em `$_SESSION['_old_input']` e contaminam o edit de outra arte.

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
| `ArteService::getMetricasArte($arte)` | ✅ Sim | ✅ Adicionado Melhoria 5 |
| `ArteService::calcularProgresso($arte)` | ✅ Sim | ✅ Adicionado Melhoria 5 |
| `ArteService::getDistribuicaoComplexidade()` | ✅ Sim | ✅ Adicionado Melhoria 6 |
| `ArteService::getResumoCards()` | ✅ Sim | ✅ Adicionado Melhoria 6 |
| `TagService::listar()` | ✅ Sim (módulo Tags completo) | ✅ Verificado |
| `TagService::getTagIdsArte($id)` | ✅ Sim | ✅ Verificado |

### Métodos privados do ArteService (uso interno)

| Método | Adicionado em | Descrição |
|--------|---------------|-----------|
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

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                         → COMPLETO (6/6)
2. ✅ Clientes     — independente                         → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)  → COMPLETO (6/6)
4. ✅ ARTES        — depende de Tags (✅ pronto)            → COMPLETO (6/6) ★
5. ⏳ Vendas       — depende de Artes + Clientes + Metas  → NÃO TESTADO (próximo)
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

---

**Última atualização:** 21/02/2026  
**Status:** ✅ MÓDULO 100% COMPLETO (Fase 1 + 6/6 Melhorias)  
**Pendência cross-module:** Cards Lucro + Rentabilidade → implementar após módulo Vendas estável  
**Próximo módulo:** 🎯 Vendas (Fase 1 — estabilização CRUD)
