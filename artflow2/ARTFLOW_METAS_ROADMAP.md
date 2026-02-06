# ArtFlow 2.0 — Módulo Metas: Documentação Completa

**Data:** 06/02/2026  
**Status Geral:** 5 de 6 melhorias implementadas  
**Versão Base:** Sistema funcional com melhorias 1-5 completas  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)

---

## 📋 RESUMO EXECUTIVO

O módulo de Metas do ArtFlow 2.0 gerencia metas mensais de faturamento para negócios de arte, permitindo acompanhar progresso, projeções e histórico. O módulo passou por 6 melhorias planejadas, das quais 5 já foram implementadas e testadas com sucesso.

### Status das Melhorias

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Status "Superado" (≥120%) | Baixa | ✅ IMPLEMENTADA |
| 2 | Resumo Estatístico por Ano | Baixa | ✅ IMPLEMENTADA |
| 3 | Gráfico Evolução Anual (Chart.js) | Baixa-Média | ✅ IMPLEMENTADA |
| 4 | Notificação de Metas em Risco | Baixa | ✅ IMPLEMENTADA |
| 5 | Criação de Metas Recorrentes | Média | ✅ IMPLEMENTADA |
| 6 | Histórico de Transições de Status | Média-Alta | ⏳ PENDENTE |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Meta.php                      ✅ Atualizado (Melhoria 1)
├── Repositories/
│   └── MetaRepository.php            ✅ Atualizado (Melhorias 1,2,3)
├── Services/
│   └── MetaService.php               ✅ Atualizado (Melhorias 2,3,4,5)
├── Controllers/
│   ├── MetaController.php            ✅ Atualizado (Melhorias 2,3,5)
│   └── DashboardController.php       ✅ Atualizado (Melhoria 4)
└── Validators/
    └── MetaValidator.php             ✅ Original

views/
├── metas/
│   ├── index.php                     ✅ Atualizado (Melhorias 1,2,3)
│   ├── create.php                    ✅ Atualizado (Melhoria 5)
│   ├── show.php                      ✅ Original
│   └── edit.php                      ✅ Original
└── dashboard/
    └── index.php                     ✅ Atualizado (Melhoria 4)

database/migrations/
└── 012_add_status_superado.php       ✅ Executada (Melhoria 1)

public/assets/js/
└── app.js                            ✅ Atualizado (timeout alertas: 10s)

src/Core/
└── View.php                          ✅ Corrigido (bug flash messages)
```

### Dependências entre Classes

```
MetaController → MetaService
MetaService    → MetaRepository + VendaRepository + MetaValidator
DashboardController → MetaService (Melhoria 4: alerta de risco)
VendaService → MetaRepository::atualizarProgresso() (ao registrar/excluir venda)
```

### Tabela `metas` (Banco de Dados)

```sql
CREATE TABLE metas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mes_ano DATE NOT NULL UNIQUE,          -- Primeiro dia do mês (ex: 2026-02-01)
    valor_meta DECIMAL(10,2) NOT NULL,     -- Valor alvo em R$
    valor_realizado DECIMAL(10,2) DEFAULT 0, -- Soma das vendas do mês
    porcentagem_atingida DECIMAL(5,2) DEFAULT 0, -- (realizado/meta)*100
    dias_trabalho_semana INT DEFAULT 5,    -- Dias úteis por semana
    status ENUM('iniciado','em_progresso','finalizado','superado') DEFAULT 'iniciado',
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ✅ MELHORIA 1: STATUS "SUPERADO" — IMPLEMENTADA

### Descrição
Adiciona 4º status para metas que ultrapassam 120% de realização, oferecendo reconhecimento visual para super-performance.

### Regras de Negócio
1. **Threshold:** 120% de `porcentagem_atingida` ativa status "superado"
2. **Transição automática:** Ao registrar venda que ultrapassa 120%, status muda automaticamente
3. **Permanência:** Uma vez "superado", permanece superado mesmo se cair abaixo de 120%
4. **Visual:** Troféu dourado (bi-trophy-fill) com badge bg-warning

### Implementação

**Migration:** `database/migrations/012_add_status_superado.php`
```sql
ALTER TABLE metas MODIFY COLUMN status ENUM('iniciado', 'em_progresso', 'finalizado', 'superado');
UPDATE metas SET status = 'superado' WHERE porcentagem_atingida >= 120 AND status = 'finalizado';
```

**Model:** `src/Models/Meta.php` — Constantes e métodos adicionados:
```php
public const STATUS_SUPERADO = 'superado';
public const STATUS_VALIDOS = ['iniciado', 'em_progresso', 'finalizado', 'superado'];

public function isSuperado(): bool;        // Verifica status
public function getStatusLabel(): string;   // 'Superado'
public function getStatusIcon(): string;    // 'bi-trophy-fill'
public function getStatusBadgeClass(): string; // 'bg-warning text-dark'
```

**Repository:** `MetaRepository::atualizarProgresso()` — Lógica de transição:
```
iniciado → em_progresso (primeira venda)
em_progresso → superado (≥120%)
superado permanece superado
Ao virar o mês: em_progresso → finalizado (se <120%)
```

**View:** `views/metas/index.php` — Badge especial com ícone de troféu dourado

### Testes
✅ Criação de meta com status inicial "iniciado"  
✅ Transição automática para "em_progresso" ao registrar venda  
✅ Transição automática para "superado" ao ultrapassar 120%  
✅ Status "superado" persistente mesmo com ajustes  

---

## ✅ MELHORIA 2: RESUMO ESTATÍSTICO POR ANO — IMPLEMENTADA

### Descrição
4 cards informativos acima da listagem de metas mostrando totais e médias do ano selecionado.

### Cards Exibidos
1. **Total de Metas** — Quantidade de metas no ano
2. **Atingidas** — Quantidade com ≥100% + taxa de sucesso em %
3. **Média Realização** — Média de `porcentagem_atingida` do ano
4. **Faturamento** — Soma de `valor_realizado` vs soma de `valor_meta`

### Implementação

**Repository:** `MetaRepository::getEstatisticasAno(int $ano): array`
```php
// Retorna array com:
// total_metas, metas_atingidas, metas_superadas, metas_nao_atingidas,
// media_porcentagem, soma_metas, soma_realizado, taxa_sucesso
```

**Service:** `MetaService::getEstatisticasAno(int $ano): array`
```php
// Wrapper que delega ao repository
return $this->metaRepository->getEstatisticasAno($ano);
```

**Controller:** `MetaController::index()` — Passa `estatisticasAno` para a view

**View:** `views/metas/index.php` — 4 cards Bootstrap em `row > col-md-3`

### Testes
✅ Cards exibidos corretamente com dados reais  
✅ Valores atualizados ao trocar filtro de ano  
✅ Cards com zero quando ano sem metas  

---

## ✅ MELHORIA 3: GRÁFICO DE EVOLUÇÃO ANUAL — IMPLEMENTADA

### Descrição
Gráfico de barras comparando Meta vs Realizado mês a mês usando Chart.js, exibido abaixo dos cards estatísticos.

### Implementação

**Repository:** `MetaRepository::getDesempenhoAnual(int $ano): array`
```php
// Retorna array de 12 posições (jan-dez), cada uma com:
// mes, nome_mes, valor_meta, valor_realizado, porcentagem, status
// Meses sem meta preenchidos com null
```

**Service:** `MetaService::getDesempenhoAnual(int $ano): array`
```php
// Wrapper que delega ao repository
return $this->metaRepository->getDesempenhoAnual($ano);
```

**Controller:** `MetaController::index()` — Passa `desempenhoAnual` para a view

**View:** `views/metas/index.php` — Gráfico Chart.js tipo 'bar' com:
- Dataset azul: Meta (R$)
- Dataset verde: Realizado (R$)
- Tooltip com formatação pt-BR (R$ X.XXX,XX)
- Eixo Y com formato monetário
- Container com altura fixa para evitar overflow

### Dependências
- Chart.js via CDN: `https://cdn.jsdelivr.net/npm/chart.js`

### Nota sobre variável
- Controller passa `desempenhoAnual` (não `desempenho_anual`)
- View lê via `$desempenhoAnual` (extraída pelo `extract()` da View.php)

### Testes
✅ Gráfico renderiza com dados reais  
✅ Meses sem meta aparecem vazios (null)  
✅ Tooltip com valores formatados em R$  
✅ Gráfico atualiza ao trocar filtro de ano  

---

## ✅ MELHORIA 4: NOTIFICAÇÃO DE METAS EM RISCO — IMPLEMENTADA

### Descrição
Alerta persistente no Dashboard quando a projeção indica que a meta do mês atual não será batida.

### Regras de Negócio
1. Busca meta do mês atual via `buscarMesAtual()`
2. Calcula projeção linear via `calcularProjecao()` existente
3. Se `vai_bater_meta === false` → exibe alerta
4. Alerta inclui: projeção total, porcentagem projetada, valor faltante, média diária necessária
5. Alerta usa `data-persist="true"` para NÃO ser fechado pelo auto-dismiss do app.js

### Implementação

**Service:** `MetaService::getMetasEmRisco(): array`
```php
// Retorna:
// ['alerta' => true/false, 'meta' => [...], 'projecao' => [...], 'mensagem' => '...']
// ou ['alerta' => false, 'motivo' => 'sem_meta' | 'meta_ok']
```

**Controller:** `DashboardController::index()` — Passa `metaEmRisco` para a view

**View:** `views/dashboard/index.php` — Alerta Bootstrap `alert-danger` com:
- Ícone `bi-exclamation-triangle-fill`
- Mensagem formatada com valores monetários
- Botão "Ver Meta" com link direto para `/metas/{id}`
- Atributo `data-persist="true"` (não fecha automaticamente)

### Testes
✅ Alerta aparece quando projeção indica risco  
✅ Alerta não aparece quando meta está em dia  
✅ Alerta não aparece quando não há meta para o mês atual  
✅ Alerta persistente (não fecha após 10 segundos)  
✅ Botão "Ver Meta" navega corretamente  

---

## ✅ MELHORIA 5: METAS RECORRENTES — IMPLEMENTADA

### Descrição
Permite criar múltiplas metas de uma vez para meses consecutivos a partir do formulário de criação. Meses que já possuem meta são automaticamente ignorados (sem erro).

### Regras de Negócio
1. Checkbox "Repetir meta para os próximos meses" ativa o modo recorrente
2. Seletor de quantidade: 2 a 12 meses
3. Meses com meta existente → ignorados (sem erro, com aviso)
4. Preview visual com badges mostrando os meses que serão criados
5. Texto do botão muda dinamicamente: "Criar Meta" → "Criar N Metas"

### Implementação

**Service:** `MetaService::criarRecorrente(array $dados, int $quantidadeMeses): array`
```php
// Parâmetros:
//   $dados — dados base da meta (valor_meta, dias_trabalho_semana, etc.)
//   $quantidadeMeses — quantidade de meses (1-12, validado com min/max)
//
// Lógica:
//   1. Parseia mes_ano do input (formato "YYYY-MM" do HTML month picker)
//   2. Appende "-01" para criar DateTime
//   3. Loop de N meses:
//      - existsMesAno() → true: adiciona a ignoradas[]
//      - existsMesAno() → false: criar() → adiciona a criadas[]
//      - Exception → adiciona a erros[]
//   4. Avança 1 mês com modify('+1 month')
//
// Retorna:
//   ['criadas' => Meta[], 'ignoradas' => [...], 'erros' => [...]]
```

**Controller:** `MetaController::store()` — Branching logic:
```php
// Lê do POST:
$recorrente = isset($_POST['recorrente']) && $_POST['recorrente'] === '1';
$quantidadeMeses = isset($_POST['quantidade_meses']) ? (int)$_POST['quantidade_meses'] : 1;

// Se recorrente && quantidade > 1:
//   → criarRecorrente() + flash com contadores
// Senão:
//   → criar() simples + flash padrão
//
// Flash messages com emojis:
//   ✅ "N meta(s) criada(s)..." (sucesso)
//   ⚠️ "Nenhuma meta criada. Todos os N meses já possuem meta." (warning)
//   ❌ Erro genérico (exception)
```

**View:** `views/metas/create.php` — Elementos adicionados:
```
Checkbox: <input type="checkbox" name="recorrente" value="1">
Quantidade: <input type="number" name="quantidade_meses" min="2" max="12" value="3">
Preview: div#preview-meses com badges dinâmicas (Jan/2026, Fev/2026, etc.)
Botão: texto muda dinamicamente conforme quantidade
```

**JavaScript da view:**
- Toggle visibilidade do seletor de quantidade
- Geração dinâmica de badges de preview (nomes de meses em PT-BR)
- Atualização do texto do botão submit
- Preserva estado com `old()` após erros de validação

### Testes
✅ Teste 1: Criação simples (sem checkbox) — funciona normalmente  
✅ Teste 2: Criação recorrente de meses novos — cria todas as metas  
✅ Teste 3: Criação recorrente com alguns meses existentes — cria os novos, ignora existentes  
✅ Teste 4: Criação recorrente com todos os meses existentes — aviso "nenhuma criada"  
✅ Flash messages exibidas corretamente após redirect  

---

## ⏳ MELHORIA 6: HISTÓRICO DE TRANSIÇÕES DE STATUS — PENDENTE

### Descrição
Registra todas as mudanças de status em tabela de log para auditoria. Exibe timeline na página de detalhes da meta.

### Especificação Técnica

**Migration:** `database/migrations/013_create_meta_status_log.php`
```sql
CREATE TABLE IF NOT EXISTS meta_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meta_id INT NOT NULL,
    status_anterior VARCHAR(20) NULL COMMENT 'NULL para criação inicial',
    status_novo VARCHAR(20) NOT NULL,
    porcentagem_momento DECIMAL(10,2) NULL,
    valor_realizado_momento DECIMAL(10,2) NULL,
    observacao TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (meta_id) REFERENCES metas(id) ON DELETE CASCADE,
    INDEX idx_meta_status_log_meta_id (meta_id),
    INDEX idx_meta_status_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Repository:** `MetaRepository` — Novos métodos:
```php
private function registrarTransicao(int $metaId, ?string $statusAnterior, string $statusNovo, 
    ?float $porcentagem, ?float $valorRealizado, ?string $observacao): void;

// atualizarStatus() modificado para registrar transição ANTES de atualizar

public function getHistoricoTransicoes(int $metaId): array;
```

**Service:** `MetaService::getHistoricoTransicoes(int $metaId): array`
```php
// Retorna array formatado com labels e datas em PT-BR
```

**Controller:** `MetaController::show()` — Passa `historicoTransicoes` para a view

**View:** `views/metas/show.php` — Timeline com:
- Badge status anterior → seta → badge status novo
- Porcentagem e valor no momento da transição
- Data/hora formatada
- Observação opcional

### Arquivos a Criar/Modificar
- ✅ `database/migrations/013_create_meta_status_log.php` — Criar
- ✅ `src/Repositories/MetaRepository.php` — Adicionar 2 métodos
- ✅ `src/Services/MetaService.php` — Adicionar 1 método
- ✅ `src/Controllers/MetaController.php` — Modificar show()
- ✅ `views/metas/show.php` — Adicionar seção timeline

### Dependências
- Migration 012 (status superado) deve estar executada
- Método `atualizarStatus()` existente será modificado

---

## 🐛 BUGS CORRIGIDOS DURANTE IMPLEMENTAÇÃO

### Bug 1: Flash Messages Não Exibidas (View.php)

**Problema:** Flash messages eram definidas na sessão pelo controller, persistiam no redirect, mas nunca apareciam na view.

**Causa raiz:** Conflito de limpeza dupla no ciclo de renderização:
1. `View::render()` lia `$_SESSION['_flash']` → salvava em `$data['success']` e `$data['error']` → **apagava `$_SESSION['_flash']` com `unset()`**
2. Layout `main.php` chamava `$flash = flash()` → lia `$_SESSION['_flash']` → **já estava vazio!**

**Correção:** Removido o `unset($_SESSION['_flash'])` do `View::render()` em `src/Core/View.php`. A limpeza agora é feita exclusivamente pelo helper `flash()` chamado no layout `main.php`.

```php
// ANTES (src/Core/View.php):
$data['success'] = $_SESSION['_flash']['success'] ?? null;
$data['error'] = $_SESSION['_flash']['error'] ?? null;
unset($_SESSION['_flash']); // ← BUG: apagava antes do layout ler

// DEPOIS:
$data['success'] = $_SESSION['_flash']['success'] ?? null;
$data['error'] = $_SESSION['_flash']['error'] ?? null;
// NÃO limpar flash aqui — o layout main.php chama flash()
// que já faz a leitura E limpeza ao consumir as mensagens.
```

**Impacto:** Este bug afetava TODOS os módulos, não apenas Metas. Com a correção, flash messages funcionam corretamente em todo o sistema.

### Bug 2: Alertas Desaparecem Muito Rápido (app.js)

**Problema:** Flash messages complexas (com contadores de metas criadas/ignoradas) desapareciam em 5 segundos, tempo insuficiente para leitura.

**Correção:** Aumentado timeout de auto-dismiss de 5000ms para 10000ms em `public/assets/js/app.js`:

```javascript
// ANTES:
setTimeout(function() { bsAlert.close(); }, 5000);

// DEPOIS:
setTimeout(function() { bsAlert.close(); }, 10000);
```

**Nota:** Alertas com `data-persist="true"` (Melhoria 4: alerta de risco) continuam sem auto-dismiss.

### Bug 3: Checkbox POST não Detectada (MetaController)

**Problema:** `$request->get('recorrente')` retornava null mesmo com checkbox marcado.

**Correção:** Substituído por leitura direta do `$_POST`:
```php
// ANTES:
$recorrente = $request->get('recorrente') === '1';

// DEPOIS:
$recorrente = isset($_POST['recorrente']) && $_POST['recorrente'] === '1';
```

### Bug 4: Variável Renomeada no Controller (Melhorias 2-3)

**Problema:** Controller passava `anosDisponiveis` para a view, mas o filtro de anos usava variável com nome diferente.

**Nota importante:** Se o filtro de anos quebrar em algum momento, verificar se a variável no controller bate com o nome esperado na view. O controller renomeou de `'anos'` para `'anosDisponiveis'`.

---

## 📊 REFERÊNCIA RÁPIDA DE MÉTODOS

### Meta Model (`src/Models/Meta.php`)

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `getStatus()` | string | Status atual |
| `setStatus(string)` | void | Define status |
| `isIniciado()` | bool | Status = 'iniciado' |
| `isEmProgresso()` | bool | Status = 'em_progresso' |
| `isFinalizado()` | bool | Status = 'finalizado' |
| `isSuperado()` | bool | Status = 'superado' (Melhoria 1) |
| `getStatusLabel()` | string | Label legível ('Superado') |
| `getStatusIcon()` | string | Classe ícone Bootstrap |
| `getStatusBadgeClass()` | string | Classe CSS do badge |
| `foiAtingida()` | bool | porcentagem ≥ 100% |
| `isMesAtual()` | bool | Meta é do mês corrente |
| `isMesPassado()` | bool | Meta é de mês anterior |
| `isMesFuturo()` | bool | Meta é de mês futuro |
| `getValorFaltante()` | float | valor_meta - valor_realizado |
| `getProgressoClass()` | string | Classe CSS da barra de progresso |

### MetaRepository (`src/Repositories/MetaRepository.php`)

| Método | Retorno | Melhoria | Descrição |
|--------|---------|----------|-----------|
| `findByAno(int)` | array | Base | Lista metas de um ano |
| `findMesAtual()` | Meta/null | Base | Meta do mês corrente |
| `findByMesAno(string)` | Meta/null | Base | Meta por mês/ano específico |
| `existsMesAno(string)` | bool | Base | Verifica se já existe meta |
| `getAnosComMetas()` | array | Base | Anos com metas cadastradas |
| `getRecentes(int)` | array | Base | Últimas metas |
| `atualizarProgresso(int, float)` | bool | M1 | Atualiza valor + status automático |
| `atualizarStatus(int, string)` | bool | Base | Atualiza status |
| `finalizarMetasPassadas()` | void | Base | Finaliza metas de meses anteriores |
| `getDesempenhoMensal(int)` | array | Base | Desempenho últimos N meses |
| `getEstatisticas()` | array | Base | Estatísticas gerais |
| `getEstatisticasAno(int)` | array | **M2** | Estatísticas agregadas por ano |
| `getDesempenhoAnual(int)` | array | **M3** | 12 posições (jan-dez) para gráfico |

### MetaService (`src/Services/MetaService.php`)

| Método | Retorno | Melhoria | Descrição |
|--------|---------|----------|-----------|
| `listar(array)` | array | Base | Lista com filtros |
| `buscar(int)` | Meta | Base | Busca por ID |
| `buscarMesAtual()` | Meta/null | Base | Meta do mês corrente |
| `buscarPorAno(int)` | array | Base | Lista metas de um ano |
| `criar(array)` | Meta | Base | Cria meta (valida unicidade) |
| `atualizar(int, array)` | Meta | Base | Atualiza meta |
| `excluir(int)` | void | Base | Exclui meta |
| `getResumoDashboard()` | array | Base | Resumo para dashboard |
| `calcularProjecao(Meta)` | array | Base | Projeção linear |
| `recalcularProgresso(int)` | void | Base | Recalcula via vendas |
| `getAnosDisponiveis()` | array | Base | Anos para filtro |
| `finalizarMetasPassadas()` | void | Base | Wrapper do repository |
| `getEstatisticasAno(int)` | array | **M2** | Estatísticas do ano |
| `getDesempenhoAnual(int)` | array | **M3** | Dados para gráfico |
| `getMetasEmRisco()` | array | **M4** | Alerta de projeção |
| `criarRecorrente(array, int)` | array | **M5** | Criação em lote |

### MetaController (`src/Controllers/MetaController.php`)

| Método | Rota | Melhorias | Descrição |
|--------|------|----------|-----------|
| `index()` | GET /metas | M2,M3 | Lista + cards + gráfico |
| `create()` | GET /metas/criar | — | Formulário criação |
| `store()` | POST /metas | **M5** | Cria simples ou recorrente |
| `show($id)` | GET /metas/{id} | — | Detalhes + progresso |
| `edit($id)` | GET /metas/{id}/editar | — | Formulário edição |
| `update($id)` | PUT /metas/{id} | — | Atualiza |
| `destroy($id)` | DELETE /metas/{id} | — | Exclui |

---

## 🔧 INSTRUÇÕES PARA CONTINUAÇÃO

### Para implementar Melhoria 6 (Histórico de Transições):

1. **Criar migration** `013_create_meta_status_log.php` com SQL descrito na seção da Melhoria 6
2. **Executar migration** via phpMyAdmin ou CLI
3. **Modificar** `MetaRepository.php`:
   - Adicionar método privado `registrarTransicao()`
   - Modificar `atualizarStatus()` para registrar antes de atualizar
   - Adicionar método público `getHistoricoTransicoes()`
4. **Adicionar** `MetaService::getHistoricoTransicoes()`
5. **Modificar** `MetaController::show()` para passar `historicoTransicoes`
6. **Atualizar** `views/metas/show.php` com seção de timeline

### Verificação do Estado Atual

```bash
# Verificar tabela metas
DESCRIBE metas;
# Deve mostrar status ENUM com 'superado'

# Verificar se migration 012 foi executada
SELECT * FROM metas WHERE status = 'superado';

# Verificar se tabela de log existe (Melhoria 6)
SHOW TABLES LIKE 'meta_status_log';
```

### Referências
- **Documentação geral:** `ARTFLOW_2_0_DOCUMENTACAO_COMPLETA.md`
- **Arquitetura:** `ARTFLOW_2_0_ARQUITETURA_PROFISSIONAL.md`
- **Este documento:** `ARTFLOW_METAS_ROADMAP.md`

---

## 📝 NOTAS TÉCNICAS IMPORTANTES

### Flash Messages — Como Funcionam no ArtFlow

O sistema possui dois mecanismos de flash que convivem:

1. **BaseController:** `flashSuccess()`, `flashError()`, `flashWarning()` → escrevem em `$_SESSION['_flash']`
2. **Layout main.php:** `$flash = flash()` → lê e limpa `$_SESSION['_flash']`
3. **View.php:** Extrai `$success`, `$error`, `$errors` da sessão (mas NÃO limpa mais)

**Regra:** O `flash()` no layout é o único responsável por limpar a sessão. View.php apenas lê sem limpar.

### Variável 'anosDisponiveis'

O controller passa `'anosDisponiveis'` (renomeado de `'anos'`). Se o filtro de anos parar de funcionar, verificar se o nome da variável no controller bate com o esperado na view `metas/index.php`.

### Auto-dismiss de Alertas

- Alertas normais: auto-fecham em **10 segundos** (app.js)
- Alertas com `data-persist="true"`: **nunca** fecham automaticamente (usados na Melhoria 4)
- Usuário sempre pode fechar manualmente via botão X (btn-close)

### Formato de `mes_ano`

- **Banco:** `DATE` no formato `YYYY-MM-DD` (sempre dia 01, ex: `2026-02-01`)
- **HTML input:** `type="month"` envia `YYYY-MM` (ex: `2026-02`)
- **Service:** Appenda `-01` ao input antes de salvar
- **Display:** Formatado como `Fev/2026` nas views

---

**Última atualização:** 06/02/2026  
**Próxima ação:** Implementar Melhoria 6 (Histórico de Transições de Status)
