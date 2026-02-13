# ArtFlow 2.0 — Módulo Clientes: Documentação Completa

**Data:** 13/02/2026  
**Status Geral:** ✅ Fase 1 (Estabilização) COMPLETA — 9 bugs corrigidos  
**Versão Base:** CRUD estabilizado com validação funcional  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Clientes do ArtFlow 2.0 gerencia a base de clientes do negócio de arte, incluindo dados de contato, localização e histórico de compras. O módulo opera de forma independente (não depende de outros módulos), mas é consumido pelo módulo de Vendas (select de clientes nos formulários) e pelo Dashboard (Top Clientes por valor de compras).

O módulo passou por uma fase de estabilização com 9 bugs corrigidos, incluindo problemas críticos de busca, captura incompleta de campos, desalinhamento do sistema de sessão do framework, e contaminação cruzada de dados entre formulários.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 9 bugs corrigidos | ✅ COMPLETA (13/02/2026) |
| Melhoria 1 | Paginação na listagem | 📋 PLANEJADA |
| Melhoria 2 | Ordenação dinâmica | 📋 PLANEJADA |
| Melhoria 3 | Campos adicionais no formulário (cidade, estado, endereço, observações) | 📋 PLANEJADA |
| Melhoria 4 | Exibição do histórico de compras na view show.php | 📋 PLANEJADA |
| Melhoria 5 | Estatísticas do cliente (total gasto, ticket médio, frequência) | 📋 PLANEJADA |
| Melhoria 6 | Máscara de telefone + validação client-side | 📋 PLANEJADA |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | 📋 PLANEJADA |
| 2 | Ordenação dinâmica (nome, data, cidade) | Baixa | 📋 PLANEJADA |
| 3 | Campos adicionais no formulário UI (cidade, estado, endereço, obs) | Baixa | 📋 PLANEJADA |
| 4 | Exibição do histórico de compras no show.php | Baixa | 📋 PLANEJADA |
| 5 | Estatísticas do cliente (cards com métricas financeiras) | Média | 📋 PLANEJADA |
| 6 | Máscara de telefone + validação client-side reforçada | Baixa | 📋 PLANEJADA |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Cliente.php                    ✅ Original
├── Repositories/
│   └── ClienteRepository.php          ✅ Fase 1 (+ allOrdered, hasVendas, emailExists,
│                                             getTopCompradores, getHistoricoCompras,
│                                             search expandido com telefone/cidade)
├── Services/
│   └── ClienteService.php             ✅ Fase 1 (+ getHistoricoCompras, normalizarDados expandido)
├── Controllers/
│   └── ClienteController.php          ✅ Fase 1 (B1-B9: todos os bugs corrigidos)
└── Validators/
    └── ClienteValidator.php           ✅ Fase 1 (+ validação cidade/estado/endereco/observacoes,
                                             telefone reforçado 10-11 dígitos, UFs brasileiras)

views/
└── clientes/
    ├── index.php                      ✅ Original (busca com name="termo")
    ├── create.php                     ✅ Original (com máscara JS inline)
    ├── show.php                       ✅ Original (recebe historicoCompras — backend pronto)
    └── edit.php                       ✅ Original (com máscara JS inline)

config/
└── routes.php                         ✅ Original (resource + buscar antes do resource)
```

### Dependências entre Classes

```
ClienteController → ClienteService
ClienteService    → ClienteRepository + ClienteValidator
(Independente: NÃO depende de outros módulos)
```

**Quem depende de Clientes:**
- VendaController precisa listar clientes para select no formulário de venda
- DashboardController usa ClienteService.getTopClientes() para card "Top Clientes"

### Tabela `clientes` (Banco de Dados)

```sql
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(20) NULL,
    empresa VARCHAR(100) NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_clientes_nome (nome),
    INDEX idx_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Nota importante sobre campos:** A migration original (002) define 8 campos editáveis: nome, email, telefone, empresa, endereco, cidade, estado, observacoes. O seeds.php popula todos. Antes da Fase 1, o Controller só capturava 4 (nome, email, telefone, empresa). Agora captura todos os 8.

### Dados Iniciais (Seeds)

| Nome | Email | Telefone | Cidade | Estado |
|------|-------|----------|--------|--------|
| Lucas Mendes | lucas.mendes@email.com | (11) 99876-5432 | São Paulo | SP |
| Amanda Silva | amanda.silva@email.com | 21987654321 | Rio de Janeiro | RJ |
| Rafael Costa | rafa.costa@email.com | (31) 97654-3210 | Belo Horizonte | MG |
| Juliana Oliveira | ju.oliveira@email.com | (41) 96543-2109 | Curitiba | PR |
| Pedro Henrique | pedroh@email.com | (51) 95432-1098 | Porto Alegre | RS |
| Carla Fernandes | carla.f@email.com | (61) 94321-0987 | Brasília | DF |
| Thiago Santos | thiago.s@email.com | (71) 93210-9876 | Salvador | BA |
| Marina Rodrigues | marina.r@email.com | (81) 92109-8765 | Recife | PE |
| Bruno Almeida | bruno.almeida@email.com | (85) 91098-7654 | Fortaleza | CE |
| Fernanda Lima | fe.lima@email.com | (91) 90987-6543 | Belém | PA |

---

## ✅ FASE 1 — ESTABILIZAÇÃO CRUD (COMPLETA)

**Implementada em:** 12-13/02/2026  
**Arquivos alterados:** ClienteController, ClienteRepository, ClienteService, ClienteValidator  
**Total de bugs:** 9 corrigidos

### Resumo dos Bugs

| Bug | Severidade | Descrição | Arquivo |
|-----|-----------|-----------|---------|
| B1 | 🔴 CRÍTICO | Busca quebrada — Controller lia 'q', view envia 'termo' | ClienteController |
| B2 | 🔴 CRÍTICO | Campos cidade/estado/endereco/observacoes nunca salvos | ClienteController |
| B3 | 🟡 MÉDIO | Validação incompleta — sem cidade, estado, endereco, obs | ClienteValidator |
| B4 | 🟡 MÉDIO | Histórico de compras não exibido no show() | ClienteController |
| B5 | 🔴 CRÍTICO | Método getTopCompradores() não existia no Repository | ClienteRepository |
| B6 | 🔴 CRÍTICO | Métodos hasVendas() e emailExists() inexistentes | ClienteRepository |
| B7 | 🟡 MÉDIO | Busca search() não incluía telefone e cidade | ClienteRepository |
| B8 | 🔴 CRÍTICO | Erros de validação invisíveis — desalinhamento de sessão | ClienteController |
| B9 | 🔴 CRÍTICO | Edit carregava dados do último create que falhou | ClienteController |

---

### Bug B1: Parâmetro de Busca Incompatível (CRÍTICO)

**Problema:** `ClienteController::index()` lia `$request->get('q')` mas a view `index.php` envia o campo com `name="termo"`. Busca completamente quebrada — nunca encontrava resultados.

**Causa raiz:** Nomenclatura inconsistente entre Controller e View.

**Correção:**
```php
// ANTES (Controller):
'termo' => $request->get('q')

// DEPOIS:
'termo' => $request->get('termo')
```

**Arquivo:** `src/Controllers/ClienteController.php` — método `index()`

---

### Bug B2: Captura Incompleta de Campos (CRÍTICO)

**Problema:** `store()` e `update()` usavam `$request->only(['nome', 'email', 'telefone', 'empresa'])` — ignorando completamente 4 campos da migration: cidade, estado, endereco, observacoes. Seeds populavam esses campos, mas eles nunca podiam ser editados pelo usuário.

**Causa raiz:** Controller não refletia todos os campos da migration 002.

**Correção:**
```php
// ANTES:
$dados = $request->only(['nome', 'email', 'telefone', 'empresa']);

// DEPOIS:
$dados = $request->only([
    'nome', 'email', 'telefone', 'empresa',
    'endereco', 'cidade', 'estado', 'observacoes'
]);
```

**Arquivo:** `src/Controllers/ClienteController.php` — métodos `store()` e `update()`

**Nota:** O backend agora aceita todos os 8 campos, mas os formulários create.php/edit.php ainda não têm inputs para cidade, estado, endereco e observacoes. Isso será adicionado na **Melhoria 3**.

---

### Bug B3: Validação Incompleta (MÉDIO)

**Problema:** `ClienteValidator` só validava nome, email, telefone e empresa. Campos cidade, estado, endereco e observacoes não tinham nenhuma validação.

**Correção:** Adicionadas validações:
- `cidade`: maxLength 100
- `estado`: valida contra lista de 27 UFs brasileiras (constante `UFS_VALIDAS`)
- `endereco`: maxLength 255
- `observacoes`: maxLength 1000

**Arquivo:** `src/Validators/ClienteValidator.php`

---

### Bug B4: Histórico de Compras Ausente (MÉDIO)

**Problema:** Documentação especifica que `show()` deve exibir o histórico de compras do cliente, mas o Controller só passava `$cliente` para a view. O Repository tinha `getHistoricoCompras()` mas nunca era chamado.

**Correção:**
```php
// show() agora faz:
$historicoCompras = $this->clienteService->getHistoricoCompras($id);
// E passa para a view:
'historicoCompras' => $historicoCompras
```

**Arquivos:** `ClienteController::show()`, `ClienteService::getHistoricoCompras()` (novo)

**Nota:** A view show.php recebe `$historicoCompras` mas a exibição UI será melhorada na **Melhoria 4**.

---

### Bug B5: Método getTopCompradores() Inexistente (CRÍTICO)

**Problema:** `ClienteService` chamava `getTopCompradores()` no Repository, mas o método se chamava `topClientes()`. Causava Fatal Error no DashboardController ao exibir "Top Clientes".

**Correção:** Adicionado `getTopCompradores()` como alias para `topClientes()` no Repository.

**Arquivo:** `src/Repositories/ClienteRepository.php`

---

### Bug B6: Métodos hasVendas() e emailExists() Inexistentes (CRÍTICO)

**Problema:** `ClienteService` chamava dois métodos que não existiam no Repository:
- `hasVendas($id)` — verificar se cliente tem vendas antes de excluir
- `emailExists($email, $exceptId)` — validar unicidade de email

**Correção:** Ambos os métodos implementados:
```php
// hasVendas(): verifica se há registros em vendas para o cliente
public function hasVendas(int $clienteId): bool

// emailExists(): verifica unicidade com exceção para o registro atual (update)
public function emailExists(string $email, ?int $exceptId = null): bool
```

**Arquivo:** `src/Repositories/ClienteRepository.php`

---

### Bug B7: Busca Incompleta (MÉDIO)

**Problema:** `search()` no Repository só filtrava por nome, email e empresa. Não incluía telefone e cidade, campos frequentemente usados para encontrar clientes.

**Correção:** Expandido o LIKE para incluir telefone e cidade:
```sql
WHERE nome LIKE :termo
   OR email LIKE :termo
   OR empresa LIKE :termo
   OR telefone LIKE :termo
   OR cidade LIKE :termo
```

**Arquivo:** `src/Repositories/ClienteRepository.php` — método `search()`

---

### Bug B8: Erros de Validação Invisíveis — Desalinhamento de Sessão (CRÍTICO)

**Problema:** Quando a validação falhava no `store()`, o usuário era redirecionado de volta ao formulário, mas os campos ficavam vazios e nenhum erro era exibido. O formulário parecia aceitar dados inválidos (mas não salvava no banco).

**Causa raiz:** Desalinhamento sistêmico do framework entre Response e Helpers:

| Componente | Onde salva | Onde lê |
|---|---|---|
| `Response::withErrors()` | `$_SESSION['_flash']['errors']` | — |
| `Response::withInput()` | `$_SESSION['_flash']['old']` | — |
| Helper `errors()` | — | `$_SESSION['_errors']` |
| Helper `old()` | — | `$_SESSION['_old_input']` |

Os dados de erro iam para `$_SESSION['_flash']` mas os helpers liam de `$_SESSION['_errors']` e `$_SESSION['_old_input']`. Nunca se encontravam.

**Correção:** Escrever diretamente em `$_SESSION` no catch, seguindo o padrão do VendaController que já contornava esse bug:
```php
catch (ValidationException $e) {
    // Escreve direto onde os helpers leem
    $_SESSION['_errors'] = $e->getErrors();
    $_SESSION['_old_input'] = $request->all();
    return $this->back();
}
```

**Arquivo:** `src/Controllers/ClienteController.php` — métodos `store()` e `update()`

**Impacto sistêmico:** Este bug afeta QUALQUER controller que use `->withErrors()->withInput()`. O VendaController já contornava. Outros módulos podem precisar da mesma correção.

---

### Bug B9: Edit Carregava Dados do Create Anterior (CRÍTICO)

**Problema:** Após o fix B8, quando a validação falhava no create, os dados ficavam em `$_SESSION['_old_input']`. Quando o usuário navegava para editar qualquer cliente, `old('nome', $cliente->getNome())` retornava os dados residuais da sessão ao invés dos dados do cliente.

**Causa raiz:** `$_SESSION['_old_input']` e `$_SESSION['_errors']` não eram limpos após serem consumidos. Persistiam indefinidamente entre requests.

**Correção inicial (incorreta):** `limparDadosFormulario()` em TODOS os métodos GET, incluindo `create()`. Isso **quebrou** a exibição de erros no create (limpa os erros antes do form renderizar).

**Correção final (correta):** `limparDadosFormulario()` chamado em `index()`, `edit()` e `show()` — **NÃO em `create()`**:
```php
private function limparDadosFormulario(): void
{
    unset($_SESSION['_old_input'], $_SESSION['_errors']);
}

// index() → limpa ✅
// create() → NÃO limpa (precisa dos erros do store) ⚠️
// edit() → limpa ✅ (impede contaminação cruzada)
// show() → limpa ✅
```

Para o `update()` (edit que falha validação), como o `edit()` limpa os dados no GET, a solução foi **re-renderizar a view diretamente** ao invés de redirecionar:
```php
catch (ValidationException $e) {
    $_SESSION['_errors'] = $e->getErrors();
    $_SESSION['_old_input'] = $request->all();
    // Re-renderiza diretamente (sem redirect → sem passar pelo edit() que limparia)
    $cliente = $this->clienteService->buscar($id);
    return $this->view('clientes/edit', [...]);
}
```

**Arquivo:** `src/Controllers/ClienteController.php`

---

### Bug Adicional: allOrdered() Inexistente (Fatal Error)

**Problema:** `ClienteService::listar()` chamava `$this->clienteRepository->allOrdered()` mas o método não existia no ClienteRepository. Apenas o TagRepository tinha implementação própria.

**Causa raiz:** `BaseRepository` tem `all($orderBy, $direction)` genérico, mas não `allOrdered()`. Cada Repository deve implementar o seu.

**Correção:**
```php
public function allOrdered(): array
{
    $sql = "SELECT * FROM {$this->table} ORDER BY nome ASC";
    $stmt = $this->db->query($sql);
    return $this->hydrateMany($stmt->fetchAll(PDO::FETCH_ASSOC));
}
```

**Arquivo:** `src/Repositories/ClienteRepository.php`

---

## 🧪 STATUS DOS TESTES (Fase 1)

| # | Operação | Rota | Status | Observação |
|---|----------|------|--------|------------|
| 1 | Busca por termo | `GET /clientes?termo=X` | ✅ OK | B1 corrigido |
| 2 | Criar com dados válidos | `POST /clientes` | ✅ OK | B2 captura todos os campos |
| 3 | Criar com email inválido | `POST /clientes` | ✅ OK | B8 exibe erro + mantém dados |
| 4 | Criar com telefone incompleto | `POST /clientes` | ✅ OK | B10 rejeita < 10 dígitos |
| 5 | Criar com só nome (mínimo) | `POST /clientes` | ✅ OK | Campos opcionais ficam vazios |
| 6 | Visualizar detalhes | `GET /clientes/{id}` | ✅ OK | B4 mostra histórico de compras |
| 7 | Editar após erro no criar | `GET /clientes/{id}/editar` | ✅ OK | B9 limpa dados residuais |
| 8 | Excluir cliente sem vendas | `DELETE /clientes/{id}` | ✅ OK | B6 hasVendas() funcional |
| 9 | Excluir cliente com vendas | `DELETE /clientes/{id}` | ✅ OK | B6 bloqueia com mensagem |
| 10 | Dashboard Top Clientes | `GET /` | ✅ OK | B5 getTopCompradores() funcional |
| 11 | Busca por telefone/cidade | `GET /clientes?termo=X` | ✅ OK | B7 busca expandida |

---

## 📋 MELHORIAS PLANEJADAS

### Melhoria 1 — Paginação na Listagem

**Complexidade:** Baixa  
**Padrão:** Mesmo da Melhoria 1 do módulo Tags

**O que fazer:**
- `ClienteRepository::allPaginated(int $page, int $perPage)` com LIMIT/OFFSET
- `ClienteRepository::countAll(?string $termo)` para total de registros
- `ClienteService::listarPaginado()` retorna `['clientes' => [...], 'paginacao' => [...]]`
- Controller passa `$paginacao` para a view
- View `index.php` exibe controles de paginação Bootstrap
- **12 clientes por página** (mesmo padrão do Tags)
- Preservar parâmetros de busca nas URLs de paginação

**Arquivos a alterar:** ClienteRepository, ClienteService, ClienteController, views/clientes/index.php

---

### Melhoria 2 — Ordenação Dinâmica

**Complexidade:** Baixa  
**Padrão:** Mesmo da Melhoria 2 do módulo Tags

**O que fazer:**
- Ordenação por: nome (A-Z / Z-A), data de cadastro (recentes / antigos), cidade
- Indicador visual da coluna ordenada
- Preservar filtros durante troca de ordenação

**Arquivos a alterar:** ClienteRepository, ClienteController, views/clientes/index.php

---

### Melhoria 3 — Campos Adicionais no Formulário UI

**Complexidade:** Baixa  
**Pré-requisito:** Backend já aceita todos os campos (fix B2)

**O que fazer:**
- Adicionar inputs em `create.php` para: endereco, cidade, estado (select com UFs), observacoes (textarea)
- Adicionar inputs em `edit.php` com mesmos campos pré-populados
- Select de UF com as 27 opções brasileiras (validação já existe no Validator)
- Manter layout responsivo com Bootstrap grid

**Arquivos a alterar:** views/clientes/create.php, views/clientes/edit.php

---

### Melhoria 4 — Exibição do Histórico de Compras

**Complexidade:** Baixa  
**Pré-requisito:** Backend já passa `$historicoCompras` para a view (fix B4)

**O que fazer:**
- Card "Histórico de Compras" na view `show.php`
- Tabela com: Arte, Valor, Data da Venda
- Totalizador: Total Gasto, Quantidade de Compras, Ticket Médio
- Estado vazio elegante quando não há compras
- Link para cada venda (GET /vendas/{id})

**Arquivos a alterar:** views/clientes/show.php

---

### Melhoria 5 — Estatísticas do Cliente

**Complexidade:** Média

**O que fazer:**
- Mini-cards no show.php: Total Gasto (R$), Quantidade de Compras, Ticket Médio, Última Compra
- `ClienteRepository::getEstatisticas(int $clienteId)` — query com SUM, COUNT, AVG, MAX
- `ClienteService::getEstatisticasCliente()` — métricas derivadas
- Proteção contra divisão por zero (clientes sem compras)

**Arquivos a alterar:** ClienteRepository, ClienteService, ClienteController, views/clientes/show.php

---

### Melhoria 6 — Máscara de Telefone + Validação Client-Side

**Complexidade:** Baixa

**Problema atual:** O `create.php` tem `data-mask="phone"` mas o `app.js` procura `data-mask="telefone"`. A máscara inline (script no fim do create/edit) funciona, mas a do app.js global nunca ativa.

**O que fazer:**
- Alinhar atributo: `data-mask="telefone"` no HTML (para compatibilidade com app.js)
- OU: Manter script inline e remover `data-mask` (mais simples)
- Adicionar validação HTML5: `pattern="[0-9() -]+"` e `minlength="14"` (formato com máscara)
- Feedback visual em tempo real: borda vermelha se incompleto
- Bloquear submit se telefone preenchido mas incompleto

**Arquivos a alterar:** views/clientes/create.php, views/clientes/edit.php, opcionalmente public/assets/js/app.js

---

## 📐 FLUXOS DETALHADOS

### Criar Cliente (POST /clientes) — Após Fase 1

```
1. ClienteController::store() recebe Request
2. validateCsrf($request) — proteção CSRF
3. $request->only([8 campos]) — captura todos os campos (fix B2)
4. ClienteService::criar($dados)
   4a. validator->validate($dados) — valida todos os campos (fix B3)
       → Se falha: throw ValidationException
   4b. validarEmailUnico($email) — se email fornecido (fix B6)
   4c. normalizarDados() — Title Case nome/cidade, UPPER estado, lowercase email
   4d. ClienteRepository::create($dados) → Cliente
5. flashSuccess("Cliente cadastrado!")
6. redirectTo('/clientes')

Em caso de ERRO de validação (fix B8 + B9):
   → $_SESSION['_errors'] = erros (direto na sessão)
   → $_SESSION['_old_input'] = dados do formulário
   → back() → GET /clientes/criar
   → create() NÃO limpa sessão → form exibe erros e dados anteriores
```

### Editar Cliente (PUT /clientes/{id}) — Após Fase 1

```
1. ClienteController::update() recebe Request + id
2. validateCsrf($request)
3. $request->only([8 campos])
4. ClienteService::atualizar($id, $dados)
   4a. findOrFail($id) — verifica existência
   4b. validator->validateUpdate($dados) — validação flexível
   4c. validarEmailUnico() — se email mudou
   4d. normalizarDados() + Repository::update()
5. flashSuccess + redirectTo('/clientes/{id}')

Em caso de ERRO:
   → $_SESSION['_errors'] e $_SESSION['_old_input']
   → Re-renderiza view diretamente (sem redirect, sem passar por edit() que limparia)
```

### Excluir Cliente (DELETE /clientes/{id}) — Após Fase 1

```
1. ClienteController::destroy() recebe Request + id
2. validateCsrf($request)
3. ClienteService::remover($id)
   3a. findOrFail($id) — verifica existência
   3b. hasVendas($id) — se tem vendas, bloqueia (fix B6)
   3c. Repository::delete($id) — exclui
4. flashSuccess + redirectTo('/clientes')
```

---

## 🔧 NOTAS TÉCNICAS IMPORTANTES

### Desalinhamento Sistêmico: Response vs Helpers (B8)

Este é o bug mais significativo da Fase 1 e afeta **todo o framework**, não apenas Clientes:

```
Response::withErrors()  → salva em $_SESSION['_flash']['errors']
Response::withInput()   → salva em $_SESSION['_flash']['old']

Helper errors()         → lê de $_SESSION['_errors']
Helper old()            → lê de $_SESSION['_old_input']
```

**Impacto:** Qualquer controller que use o padrão `return $this->back()->withErrors()->withInput()` vai ter erros invisíveis. O VendaController já contornava escrevendo direto. O ClienteController agora também. **Outros módulos devem ser verificados.**

**Solução definitiva (para futuro):** Alinhar o Response para salvar no mesmo local que os helpers leem, ou vice-versa. Isso resolveria o problema em todos os módulos de uma vez.

### Limpeza Seletiva de Sessão (B9)

A regra é:
- `create()` → **NUNCA** limpar (precisa dos erros do store)
- `edit()` → **SEMPRE** limpar (impede contaminação do create)
- `index()` → **SEMPRE** limpar (navegação limpa dados)
- `show()` → **SEMPRE** limpar

### Máscara de Telefone: data-mask Desalinhado

O `create.php` e `edit.php` usam `data-mask="phone"` mas o `app.js` busca `data-mask="telefone"`. A máscara nunca ativa via `app.js`, mas funciona via script inline no fim de cada view. Será corrigido na Melhoria 6.

### Normalização de Dados

O `ClienteService::normalizarDados()` aplica:
- **nome** → `mb_convert_case(trim(), MB_CASE_TITLE)` (Title Case)
- **email** → `strtolower(trim())` (minúsculas)
- **telefone** → `preg_replace('/[^0-9]/', '')` (apenas dígitos)
- **cidade** → `mb_convert_case(trim(), MB_CASE_TITLE)` (Title Case)
- **estado** → `mb_strtoupper(trim())` (MAIÚSCULAS — 2 letras UF)

### Validação de Telefone Brasileiro

O `ClienteValidator` valida:
- Remove tudo que não é dígito
- Mínimo 10 dígitos (fixo: DDD + 8 dígitos)
- Máximo 11 dígitos (celular: DDD + 9 dígitos)
- Mensagem clara: "Telefone incompleto (X dígitos). Informe DDD + número."

### Update com Re-renderização Direta

No `update()`, ao invés de `back()` (que faria redirect → GET edit → limpa sessão), o Controller re-renderiza a view diretamente:
```php
catch (ValidationException $e) {
    $_SESSION['_errors'] = $e->getErrors();
    $_SESSION['_old_input'] = $request->all();
    $cliente = $this->clienteService->buscar($id);
    return $this->view('clientes/edit', [...]); // Direto, sem redirect
}
```

---

## 🗂️ ARQUIVOS ENTREGUES NA FASE 1

| Arquivo | Caminho | Bugs Corrigidos |
|---------|---------|-----------------|
| ClienteController.php | `src/Controllers/` | B1, B2, B4, B8, B9 |
| ClienteRepository.php | `src/Repositories/` | B5, B6, B7, allOrdered |
| ClienteService.php | `src/Services/` | B4 (getHistoricoCompras) |
| ClienteValidator.php | `src/Validators/` | B3, B10 (telefone reforçado) |

---

## 📊 PROCESSO DE DEBUG (Registro)

Durante a Fase 1, foram criados 3 scripts de diagnóstico para identificar a cadeia de falha:

1. **debug_telefone.php** — Testou Validator isoladamente. Resultado: ✅ Todos os 8 testes passaram. Validator funciona perfeitamente.

2. **debug_controller.php** — Verificou o arquivo do Controller, padrões no código, método store(), rotas e formulário. Resultado: ✅ Controller correto, formulário correto, rotas corretas.

3. **debug_final.php** — Verificou OPcache, conexão com banco (descobriu nome `artflow2_db`), listou dados no banco, testou POST real, e analisou JavaScript. Resultado: ✅ Todos os telefones no banco válidos. Descobriu: `data-mask="phone"` vs `data-mask="telefone"`.

**Conclusão do debug:** A validação backend sempre funcionou. O problema era a **exibição de feedback** (B8 + B9) que fazia parecer que dados inválidos eram aceitos.

---

## 📌 PRÓXIMAS AÇÕES

1. **Melhoria 1 (Paginação)** — Implementar seguindo padrão Tags. Baixa complexidade.
2. **Melhoria 3 (Campos UI)** — Adicionar inputs de cidade/estado/endereço/obs nos forms. Backend já pronto.
3. **Melhoria 6 (Máscara)** — Alinhar `data-mask` entre HTML e JS. Correção rápida.
4. **Investigar bug sistêmico B8** — Verificar se ArteController e MetaController têm o mesmo problema de `withErrors()`/`withInput()`.

---

**Última atualização:** 13/02/2026  
**Status:** ✅ Fase 1 COMPLETA — 9 bugs corrigidos, CRUD estável  
**Próxima ação:** Melhoria 1 (Paginação) ou Melhoria 3 (Campos UI)
