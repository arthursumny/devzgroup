document.addEventListener('DOMContentLoaded', function() {
    const formProposta = document.getElementById('formProposta');
    const tabelaProdutosBody = document.getElementById('tabelaProdutosBody');
    const btnAdicionarItem = document.getElementById('btnAdicionarItem');
    const btnPrevisualizar = document.getElementById('btnPrevisualizar');
    const btnGerarDoc = document.getElementById('btnGerarDoc');
    const valorTotalInput = document.querySelector('input[name="valor_total"]');

    let itensProposta = ITENS_PROPOSTA || [];

    function showMessage(message, type = 'success') {
        const messageDiv = document.getElementById('formMessage');
        if (messageDiv) {
            messageDiv.className = `form-message ${type}`;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }

    function formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    function calcularTotalItem(quantidade, valorUnitario) {
        return quantidade * valorUnitario;
    }

    function atualizarValorTotal() {
        const total = itensProposta.reduce((acc, item) => {
            return acc + calcularTotalItem(
                parseFloat(item.quantidade) || 0,
                parseFloat(item.valor_unitario) || 0
            );
        }, 0);
        
        valorTotalInput.value = total.toFixed(2);
    }

    function renderizarTabelaProdutos() {
        tabelaProdutosBody.innerHTML = itensProposta.map((item, index) => `
            <tr>
                <td>
                    <input type="text" class="form-control" value="${item.descricao}" 
                           onchange="atualizarItem(${index}, 'descricao', this.value)">
                </td>
                <td>
                    <input type="number" class="form-control" value="${item.quantidade}" min="1"
                           onchange="atualizarItem(${index}, 'quantidade', this.value)">
                </td>
                <td>
                    <input type="number" class="form-control" value="${item.valor_unitario}" step="0.01" min="0"
                           onchange="atualizarItem(${index}, 'valor_unitario', this.value)">
                </td>
                <td>${formatarMoeda(calcularTotalItem(item.quantidade, item.valor_unitario))}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removerItem(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    window.atualizarItem = function(index, campo, valor) {
        itensProposta[index][campo] = valor;
        if (campo === 'quantidade' || campo === 'valor_unitario') {
            atualizarValorTotal();
        }
        renderizarTabelaProdutos();
    };

    window.removerItem = function(index) {
        itensProposta.splice(index, 1);
        renderizarTabelaProdutos();
        atualizarValorTotal();
    };

    btnAdicionarItem.addEventListener('click', function() {
        itensProposta.push({
            descricao: '',
            quantidade: 1,
            valor_unitario: 0
        });
        renderizarTabelaProdutos();
    });

    formProposta.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('itens_proposta', JSON.stringify(itensProposta));

        try {
            const response = await fetch('api/gerenciar_propostas.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('Erro na requisição');
            
            const result = await response.json();
            
            if (result.success) {
                showMessage(result.message);
                if (!PROPOSTA_ID) { // Se for uma nova proposta
                    setTimeout(() => {
                        window.location.href = 'propostas-parceiro.php';
                    }, 2000);
                }
            } else {
                throw new Error(result.message || 'Erro ao salvar proposta');
            }
        } catch (error) {
            console.error('Erro:', error);
            showMessage(error.message, 'error');
        }
    });

    if (btnPrevisualizar) {
        btnPrevisualizar.addEventListener('click', function() {
            // Implementar lógica de pré-visualização
            alert('Funcionalidade de pré-visualização em desenvolvimento');
        });
    }

    if (btnGerarDoc) {
        btnGerarDoc.addEventListener('click', async function() {
            if (!PROPOSTA_ID) return;
            
            try {
                await gerarDocumentoProposta(PROPOSTA_ID, this);
            } catch (error) {
                console.error('Erro:', error);
                showMessage(error.message, 'error');
            }
        });
    }

    // Inicializar tabela se houver itens
    if (itensProposta.length > 0) {
        renderizarTabelaProdutos();
        atualizarValorTotal();
    }
});
