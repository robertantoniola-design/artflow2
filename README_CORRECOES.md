# 🔧 ArtFlow 2.0 - Correções de Formulários

**Data:** 29/01/2026  
**Versão:** 2.0.0-beta-fix1

---

## 📋 Resumo das Correções

### Bug #1: Inconsistência no Campo CSRF (CRÍTICO)
**Problema:** O `BaseController.php` só validava tokens com nome `_token` ou `csrf_token`, mas várias views usavam `_csrf`.

**Solução (A+B):**
1. **BaseController.php** - Agora aceita `_token`, `_csrf` e `csrf_token`
2. **Views corrigidas** - Padronizadas para usar `_token`
3. **functions.php** - `csrf_field()` agora gera campo com nome `_token`

### Bug #2: Variáveis Inconsistentes em vendas/create.php
**Problema:** O controller passava `artes` e `clientes`, mas a view esperava `artesDisponiveis` e `clientesSelect`.

**Solução:** `VendaController.php` agora passa ambos os nomes (compatibilidade).

---

## 📁 Arquivos Corrigidos

```
artflow2_correcoes/
├── src/
│   ├── Controllers/
│   │   ├── BaseController.php      ← CSRF aceita múltiplos nomes
│   │   └── VendaController.php     ← Variáveis da view corrigidas
│   └── Helpers/
│       └── functions.php           ← csrf_field() usa _token
└── views/
    ├── artes/
    │   ├── create.php              ← _csrf → _token
    │   └── edit.php                ← _csrf → _token
    ├── metas/
    │   └── edit.php                ← _csrf → _token
    └── tags/
        └── edit.php                ← _csrf → _token
```

---

## 🚀 Como Aplicar as Correções

### Passo 1: Backup
```bash
# Faça backup do projeto atual
cd C:\xampp\htdocs
xcopy /E /I artflow2 artflow2_backup
```

### Passo 2: Copiar Arquivos Corrigidos
```bash
# Copie os arquivos corrigidos para o projeto
# Substitua os arquivos existentes pelos novos

# BaseController.php
copy artflow2_correcoes\src\Controllers\BaseController.php artflow2\src\Controllers\

# VendaController.php
copy artflow2_correcoes\src\Controllers\VendaController.php artflow2\src\Controllers\

# functions.php
copy artflow2_correcoes\src\Helpers\functions.php artflow2\src\Helpers\

# Views - Artes
copy artflow2_correcoes\views\artes\create.php artflow2\views\artes\
copy artflow2_correcoes\views\artes\edit.php artflow2\views\artes\

# Views - Tags
copy artflow2_correcoes\views\tags\edit.php artflow2\views\tags\

# Views - Metas
copy artflow2_correcoes\views\metas\edit.php artflow2\views\metas\
```

### Passo 3: Limpar Cache do Navegador
```
Ctrl + Shift + R (hard refresh)
```

### Passo 4: Testar
Acesse cada formulário e teste:
1. Criar nova arte: `http://localhost/artflow2/artes/criar`
2. Editar arte existente
3. Criar/editar tag
4. Criar/editar meta
5. Registrar venda

---

## ✅ Checklist de Testes

| Módulo | Criar | Editar | Excluir |
|--------|:-----:|:------:|:-------:|
| Artes  | ⬜    | ⬜     | ⬜      |
| Tags   | ⬜    | ⬜     | ⬜      |
| Metas  | ⬜    | ⬜     | ⬜      |
| Vendas | ⬜    | ⬜     | ⬜      |

---

## 🔍 Detalhes Técnicos

### BaseController.php - Linha 171
```php
// ANTES (BUG):
$token = $request->get('_token') ?? $request->get('csrf_token');

// DEPOIS (CORRIGIDO):
$token = $request->get('_token') 
      ?? $request->get('_csrf') 
      ?? $request->get('csrf_token');
```

### functions.php - csrf_field()
```php
// ANTES:
return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';

// DEPOIS:
return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
```

### Views - Padrão CSRF
```php
<!-- ANTES -->
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

<!-- DEPOIS (padronizado) -->
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
```

---

## 📝 Notas

- As correções são **retrocompatíveis** - o BaseController aceita tanto `_token` quanto `_csrf`
- O VendaController passa **ambos** os nomes de variáveis para garantir compatibilidade
- Recomendamos usar `_token` como padrão em todas as novas views

---

*Correções geradas em 29/01/2026*
