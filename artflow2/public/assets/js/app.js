/**
 * ============================================
 * ARTFLOW 2.0 - JAVASCRIPT PRINCIPAL
 * ============================================
 * 
 * Funcionalidades:
 * 1. Toggle Sidebar (mobile)
 * 2. Dark Mode
 * 3. Busca Global (AJAX)
 * 4. Confirmação de Delete
 * 5. Máscaras de Input + Validação Client-Side [MELHORIA 6]
 * 6. Alertas auto-dismiss
 * 
 * MELHORIA 6 (14/02/2026) — Alterações na seção 5:
 * - Máscara de telefone reescrita com formatação progressiva
 * - Adicionado: validarTelefoneVisual() — feedback em tempo real
 * - Adicionado: bloqueio de submit se telefone incompleto
 * - Centraliza toda lógica de telefone (views não precisam de scripts inline)
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. TOGGLE SIDEBAR (MOBILE)
    // ==========================================
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Criar overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (menuToggle) {
        menuToggle.addEventListener('click', openSidebar);
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', closeSidebar);
    }
    
    overlay.addEventListener('click', closeSidebar);
    
    // Fechar ao redimensionar para desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
    
    // ==========================================
    // 2. DARK MODE
    // ==========================================
    const darkModeToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;
    
    // Verificar preferência salva ou do sistema
    function getPreferredTheme() {
        const saved = localStorage.getItem('theme');
        if (saved) return saved;
        
        return window.matchMedia('(prefers-color-scheme: dark)').matches 
            ? 'dark' 
            : 'light';
    }
    
    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Atualizar ícone do botão
        if (darkModeToggle) {
            const icon = darkModeToggle.querySelector('i');
            const text = darkModeToggle.querySelector('span');
            
            if (theme === 'dark') {
                icon.className = 'bi bi-sun';
                if (text) text.textContent = 'Modo Claro';
            } else {
                icon.className = 'bi bi-moon-stars';
                if (text) text.textContent = 'Modo Escuro';
            }
        }
    }
    
    // Aplicar tema inicial
    setTheme(getPreferredTheme());
    
    // Toggle ao clicar
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const current = html.getAttribute('data-theme');
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
    
    // ==========================================
    // 3. BUSCA GLOBAL (AJAX)
    // ==========================================
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            const termo = this.value.trim();
            
            // Limpar timeout anterior
            clearTimeout(searchTimeout);
            
            if (termo.length < 2) {
                searchResults.classList.remove('active');
                searchResults.innerHTML = '';
                return;
            }
            
            // Debounce de 300ms
            searchTimeout = setTimeout(function() {
                fetch(`/dashboard/busca?termo=${encodeURIComponent(termo)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.total > 0) {
                            renderSearchResults(data.resultados);
                            searchResults.classList.add('active');
                        } else {
                            searchResults.innerHTML = `
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-search"></i>
                                    Nenhum resultado encontrado
                                </div>
                            `;
                            searchResults.classList.add('active');
                        }
                    })
                    .catch(error => {
                        console.error('Erro na busca:', error);
                    });
            }, 300);
        });
        
        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
        
        // Fechar ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchResults.classList.remove('active');
                searchInput.blur();
            }
        });
    }
    
    function renderSearchResults(resultados) {
        let html = '';
        
        // Artes
        if (resultados.artes && resultados.artes.length > 0) {
            html += '<div class="p-2 bg-light border-bottom"><strong>Artes</strong></div>';
            resultados.artes.forEach(item => {
                html += `
                    <a href="${item.url}" class="d-block p-2 border-bottom text-decoration-none">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-brush text-primary"></i>
                            <div>
                                <div class="text-dark">${escapeHtml(item.nome)}</div>
                                <small class="text-muted">${item.status}</small>
                            </div>
                        </div>
                    </a>
                `;
            });
        }
        
        // Clientes
        if (resultados.clientes && resultados.clientes.length > 0) {
            html += '<div class="p-2 bg-light border-bottom"><strong>Clientes</strong></div>';
            resultados.clientes.forEach(item => {
                html += `
                    <a href="${item.url}" class="d-block p-2 border-bottom text-decoration-none">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person text-success"></i>
                            <div>
                                <div class="text-dark">${escapeHtml(item.nome)}</div>
                                <small class="text-muted">${item.email || ''}</small>
                            </div>
                        </div>
                    </a>
                `;
            });
        }
        
        searchResults.innerHTML = html;
    }
    
    // ==========================================
    // 4. CONFIRMAÇÃO DE DELETE
    // ==========================================
    document.querySelectorAll('[data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Tem certeza que deseja excluir?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Formulários de delete via JavaScript
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.getAttribute('data-confirm') || 'Tem certeza que deseja excluir?';
            const form = this.closest('form');
            
            if (confirm(message)) {
                // Adicionar campo _method se não existir
                if (!form.querySelector('input[name="_method"]')) {
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);
                }
                form.submit();
            }
        });
    });
    
    // ==========================================
    // 5. MÁSCARAS DE INPUT + VALIDAÇÃO [MELHORIA 6]
    // ==========================================
    
    /**
     * [MELHORIA 6] Máscara de Telefone Brasileiro — Reescrita
     * 
     * ANTES: usava regex simples que não formatava progressivamente.
     * AGORA: formatação progressiva enquanto digita + validação visual.
     * 
     * Funciona em qualquer input com data-mask="telefone".
     * Formatos suportados:
     *   - Fixo:    (XX) XXXX-XXXX  (10 dígitos)
     *   - Celular: (XX) XXXXX-XXXX (11 dígitos)
     * 
     * Comportamento:
     *   - Só permite dígitos numéricos
     *   - Limita a 11 dígitos (DDD + 9 dígitos)
     *   - Aplica formatação progressiva enquanto digita
     *   - Valida visualmente ao sair do campo (blur)
     *   - Remove caracteres não-numéricos colados via clipboard
     */
    document.querySelectorAll('input[data-mask="telefone"]').forEach(function(input) {
        
        // --- Evento principal: formata enquanto digita ---
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Só dígitos
            
            // Limita a 11 dígitos (DDD 2 + número 9)
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            // Aplica máscara progressiva
            if (value.length > 6) {
                // Formato completo: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
            } else if (value.length > 2) {
                // Parcial após DDD: (XX) XXX...
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
            } else if (value.length > 0) {
                // Início do DDD: (X...
                value = '(' + value;
            }
            
            e.target.value = value;
            
            // [MELHORIA 6] Valida visualmente após cada digitação
            validarTelefoneVisual(input);
        });
        
        // --- [MELHORIA 6] Evento blur: valida ao sair do campo ---
        input.addEventListener('blur', function() {
            validarTelefoneVisual(input);
        });
    });
    
    /**
     * [MELHORIA 6] Validação Visual do Telefone
     * 
     * Verifica se o telefone está:
     *   - Vazio (OK — campo opcional)
     *   - Completo com 10 ou 11 dígitos (OK)
     *   - Parcialmente preenchido (ERRO — exibe feedback visual)
     * 
     * Integração com Bootstrap 5:
     *   - Usa classe 'is-invalid' para borda vermelha
     *   - Cria/reutiliza div.invalid-feedback para mensagem
     *   - Não duplica feedback se PHP server-side já criou um
     * 
     * @param {HTMLInputElement} input - O campo de telefone
     */
    function validarTelefoneVisual(input) {
        const valor = input.value.replace(/\D/g, ''); // Extrai só dígitos
        const feedbackId = input.id + '-mask-feedback';
        
        // Busca ou cria o elemento de feedback
        let feedback = document.getElementById(feedbackId);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = feedbackId;
            feedback.className = 'invalid-feedback';
            feedback.style.display = 'none';
            feedback.textContent = 'Telefone incompleto. Informe DDD + número (10 ou 11 dígitos)';
            
            // Insere após o input, respeitando feedback PHP existente
            const parent = input.parentNode;
            const phpFeedback = parent.querySelector('.invalid-feedback:not([id])');
            if (phpFeedback) {
                // Feedback do PHP já existe sem ID — reutiliza
                phpFeedback.id = feedbackId;
                feedback = phpFeedback;
            } else if (!document.getElementById(feedbackId)) {
                parent.appendChild(feedback);
            }
        }
        
        // Lógica: vazio = OK (opcional), 10 ou 11 dígitos = OK, outro = incompleto
        if (valor.length === 0 || valor.length === 10 || valor.length === 11) {
            // ✅ Válido ou vazio — remove estado de erro
            input.classList.remove('is-invalid');
            input.classList.remove('telefone-incompleto');
            feedback.style.display = 'none';
        } else {
            // ❌ Incompleto — aplica estado de erro
            input.classList.add('is-invalid');
            input.classList.add('telefone-incompleto');
            feedback.style.display = 'block';
            feedback.textContent = 'Telefone incompleto. Informe DDD + número (10 ou 11 dígitos)';
        }
    }
    
    /**
     * [MELHORIA 6] Bloqueio de Submit — Impede envio com telefone incompleto
     * 
     * Intercepta o submit de qualquer formulário que contenha
     * input[data-mask="telefone"] e verifica:
     *   - Vazio → permite (campo opcional)
     *   - 10 ou 11 dígitos → permite (válido)
     *   - 1-9 dígitos → bloqueia (incompleto) + mostra erro + foca no campo
     */
    document.querySelectorAll('form').forEach(function(form) {
        var telefoneInput = form.querySelector('input[data-mask="telefone"]');
        if (!telefoneInput) return; // Só atua em forms com campo de telefone
        
        form.addEventListener('submit', function(e) {
            var valor = telefoneInput.value.replace(/\D/g, '');
            
            // Vazio = OK (opcional), 10 ou 11 = OK (válido)
            if (valor.length === 0 || valor.length === 10 || valor.length === 11) {
                return true; // Permite submit
            }
            
            // Bloqueia submit — telefone incompleto
            e.preventDefault();
            
            // Força validação visual para mostrar o erro
            validarTelefoneVisual(telefoneInput);
            
            // Foca no campo com problema
            telefoneInput.focus();
            
            // Scroll suave até o campo (útil em formulários longos)
            telefoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            return false;
        });
    });
    
    // Máscara de dinheiro (inalterada)
    document.querySelectorAll('input[data-mask="dinheiro"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = 'R$ ' + value;
        });
    });
    
    // ==========================================
    // 6. ALERTAS AUTO-DISMISS
    // ==========================================
    // Fecha flash messages após 10s, mas preserva alertas com data-persist="true"
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        if (alert.dataset.persist === 'true') return; // ← Ignora alertas persistentes
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 10000);
    });
    
    // ==========================================
    // 7. TOOLTIPS (Bootstrap)
    // ==========================================
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
    
    // ==========================================
    // FUNÇÕES UTILITÁRIAS
    // ==========================================
    
    /**
     * Escapa HTML para prevenir XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Formata número como moeda brasileira
     */
    window.formatMoney = function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    };
    
    /**
     * Formata data para padrão brasileiro
     */
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    };
    
    /**
     * Faz requisição AJAX
     */
    window.ajax = function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaults, ...options };
        
        return fetch(url, config).then(response => response.json());
    };
    
    /**
     * Mostra notificação toast
     */
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
        return container;
    }
    
});

/**
 * ==========================================
 * MÓDULO: DASHBOARD
 * ==========================================
 */
const Dashboard = {
    
    /**
     * Atualiza dados do dashboard
     */
    refresh: function() {
        fetch('/dashboard/refresh')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateCards(data.data.cards);
                    this.updateMeta(data.data.meta);
                }
            })
            .catch(error => console.error('Erro ao atualizar dashboard:', error));
    },
    
    /**
     * Atualiza cards de estatísticas
     */
    updateCards: function(cards) {
        for (const [key, value] of Object.entries(cards)) {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = typeof value === 'number' && key.includes('vendas') 
                    ? formatMoney(value) 
                    : value;
            }
        }
    },
    
    /**
     * Atualiza progresso da meta
     */
    updateMeta: function(meta) {
        const progressBar = document.querySelector('.meta-progress');
        if (progressBar && meta.porcentagem !== undefined) {
            progressBar.style.width = `${Math.min(meta.porcentagem, 100)}%`;
            progressBar.setAttribute('aria-valuenow', meta.porcentagem);
        }
    },
    
    /**
     * Inicializa gráfico de vendas mensais
     */
    initVendasChart: function(canvasId, dados) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        const labels = dados.map(d => d.mes);
        const valores = dados.map(d => d.total);
        
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Vendas (R$)',
                    data: valores,
                    backgroundColor: 'rgba(99, 102, 241, 0.5)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }
};