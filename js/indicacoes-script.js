document.addEventListener('DOMContentLoaded', function() {
    const formNovaIndicacao = document.getElementById('formNovaIndicacao');
    const listaIndicacoesDiv = document.getElementById('listaIndicacoes');
    const indicacaoMessageDiv = document.getElementById('indicacaoMessage');

    // Função para exibir mensagens
    function showMessage(message, type = 'error') {
        indicacaoMessageDiv.textContent = message;
        indicacaoMessageDiv.className = `form-message ${type}`; // Adiciona classe para estilização
    }

    // Carregar indicações existentes ao carregar a página
    async function carregarIndicacoes() {
        if (!listaIndicacoesDiv) return;
        listaIndicacoesDiv.innerHTML = '<p>Carregando suas indicações...</p>';

        try {
            // Usaremos POST para enviar o parceiro_id de forma segura, ou GET se preferir
            const response = await fetch('api/gerenciar_indicacoes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_indicacoes&parceiro_id=${ID_PARCEIRO_LOGADO}`
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                throw new Error(errorData?.message || `Erro HTTP: ${response.status}`);
            }

            const indicacoes = await response.json();

            if (indicacoes.length === 0) {
                listaIndicacoesDiv.innerHTML = '<p>Você ainda não cadastrou nenhuma indicação.</p>';
                return;
            }

            listaIndicacoesDiv.innerHTML = ''; // Limpa a mensagem de "carregando"
            indicacoes.forEach(indicacao => {
                const card = document.createElement('div');
                card.classList.add('indicacao-card');
                card.innerHTML = `
                    <h3>${escapeHTML(indicacao.nome_indicado)}</h3>
                    <p><strong>Empresa:</strong> ${escapeHTML(indicacao.empresa_indicada) || 'N/A'}</p>
                    <p><strong>Telefone:</strong> ${escapeHTML(indicacao.telefone_indicado) || 'N/A'}</p>
                    <p><strong>Email:</strong> ${escapeHTML(indicacao.email_indicado) || 'N/A'}</p>
                    <p><strong>Produto/Serviço:</strong> ${escapeHTML(indicacao.produto_interesse) || 'N/A'}</p>
                    <p><strong>Detalhes:</strong> ${escapeHTML(indicacao.detalhes_indicacao) || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-${indicacao.status_indicacao?.toLowerCase().replace(' ', '-')}">${escapeHTML(indicacao.status_indicacao)}</span></p>
                    <p><small>Criado em: ${new Date(indicacao.data_criacao).toLocaleDateString()}</small></p>
                    <div class="indicacao-actions">
                        <button class="btn-delete-indicacao" data-id="${indicacao.id}">Excluir</button>
                    </div>
                `;
                listaIndicacoesDiv.appendChild(card);
            });

            // Adicionar event listeners para os botões de exclusão
            document.querySelectorAll('.btn-delete-indicacao').forEach(button => {
                button.addEventListener('click', function() {
                    const indicacaoId = this.dataset.id;
                    if (confirm('Tem certeza que deseja excluir esta indicação?')) {
                        excluirIndicacao(indicacaoId);
                    }
                });
            });

        } catch (error) {
            console.error('Erro ao carregar indicações:', error);
            listaIndicacoesDiv.innerHTML = `<p style="color:red;">Erro ao carregar indicações: ${error.message}</p>`;
        }
    }

    // Salvar nova indicação
    if (formNovaIndicacao) {
        formNovaIndicacao.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(formNovaIndicacao);
            formData.append('action', 'save_indicacao'); // Adiciona a ação para o backend
            
            const submitButton = formNovaIndicacao.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Salvando...';
            showMessage('', ''); // Limpa mensagens anteriores

            try {
                const response = await fetch('api/gerenciar_indicacoes.php', {
                    method: 'POST',
                    body: formData 
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showMessage(result.message || 'Indicação salva com sucesso!', 'success');
                    formNovaIndicacao.reset();
                    carregarIndicacoes(); // Recarrega a lista
                } else {
                    showMessage(result.message || 'Erro ao salvar indicação.');
                }
            } catch (error) {
                console.error('Erro ao salvar indicação:', error);
                showMessage('Erro de comunicação ao salvar. Tente novamente.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    }

    // Excluir indicação
    async function excluirIndicacao(id) {
        try {
            const response = await fetch('api/gerenciar_indicacoes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_indicacao&id=${id}&parceiro_id=${ID_PARCEIRO_LOGADO}` // Envia parceiro_id para verificação
            });

            const result = await response.json();

            if (response.ok && result.success) {
                alert(result.message || 'Indicação excluída com sucesso!');
                carregarIndicacoes(); // Recarrega a lista
            } else {
                alert(result.message || 'Erro ao excluir indicação.');
            }
        } catch (error) {
            console.error('Erro ao excluir indicação:', error);
            alert('Erro de comunicação ao excluir. Tente novamente.');
        }
    }
    
    // Função para escapar HTML e prevenir XSS
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }


    // Carregar indicações iniciais
    carregarIndicacoes();
});