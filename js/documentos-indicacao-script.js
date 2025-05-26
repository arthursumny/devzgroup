document.addEventListener('DOMContentLoaded', function() {
    const listaDocumentosDiv = document.getElementById('listaDocumentos');
    const formDocumento = document.getElementById('formDocumentoIndicacao');
    const formDocMessageDiv = document.getElementById('formDocMessage');
    const btnGerarNovoDoc = document.querySelector('.actions-bar .btn-gerar-novo');

    // VariÃ¡veis globais da página (se existirem)
    // ID_PARCEIRO_LOGADO (definido no HTML da página de listagem indicadores-parceiro.php)
    // DOCUMENTO_UID_ATUAL (definido no HTML da página do formulário formulario-indicacao.php)
    // IS_PARCEIRO_DONO (definido no HTML da página do formulário formulario-indicacao.php)
    // STATUS_DOCUMENTO_ATUAL (definido no HTML da página do formulário formulario-indicacao.php)


    function showMessage(element, message, type = 'error') {
        if (element) {
            element.textContent = message;
            element.className = `form-message ${type}`;
            element.style.display = 'block';
        }
    }
    function clearMessage(element) {
        if (element) {
            element.textContent = '';
            element.style.display = 'none';
        }
    }
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, match => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[match]);
    }

    // --- LÃ³gica para indicadores-parceiro.php (Listagem) ---
    if (btnGerarNovoDoc && typeof ID_PARCEIRO_LOGADO !== 'undefined') {
        btnGerarNovoDoc.addEventListener('click', async function(event) {
            event.preventDefault();
            const nomeDocumento = prompt("Digite um nome para este novo documento de indicação:", "Novo Documento de indicação");
            if (nomeDocumento === null || nomeDocumento.trim() === "") {
                return; 
            }
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando link...';
            this.classList.add('disabled');
            try {
                const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=generate_new_document_link&parceiro_id=${ID_PARCEIRO_LOGADO}&nome_documento=${encodeURIComponent(nomeDocumento.trim())}`
                });
                const result = await response.json();
                if (result.success && result.link_compartilhavel) {
                    prompt(`Link gerado para "${escapeHTML(result.nome_documento)}"! Copie e compartilhe:`, result.link_compartilhavel);
                    carregarDocumentos();
                } else {
                    alert(result.message || "Erro ao gerar o link do documento.");
                }
            } catch (error) {
                console.error("Erro ao gerar link:", error);
                alert("Erro de comunicação ao gerar o link.");
            } finally {
                this.innerHTML = '<i class="fas fa-plus-circle"></i> Gerar Novo Documento';
                this.classList.remove('disabled');
            }
        });
    }
    
    async function carregarDocumentos() {
        if (!listaDocumentosDiv || typeof ID_PARCEIRO_LOGADO === 'undefined') return;
        listaDocumentosDiv.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Carregando seus documentos...</p>';
        try {
            const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_documentos&parceiro_id=${ID_PARCEIRO_LOGADO}`
            });
            if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
            const result = await response.json();

            if (!result.success) {
                 listaDocumentosDiv.innerHTML = `<p style="color:red;">${escapeHTML(result.message)}</p>`;
                 return;
            }
            if (!result.data || result.data.length === 0) {
                listaDocumentosDiv.innerHTML = '<p>você ainda não gerou nenhum documento de indicação.</p>';
                return;
            }

            listaDocumentosDiv.innerHTML = '';
            result.data.forEach(doc => {
                const card = document.createElement('div');
                card.classList.add('documento-card');
                // O link de ediÃ§Ã£o/visualização não o parceiro agora Ã© o mesmo link pÃºblico,
                // mas a página formulario-indicacao.php saberÃ¡ se Ã© o parceiro dono.
                const linkDoc = `formulario-indicacao.php?uid=${doc.documento_uid}`;
                
                let finalizarBtnHtml = '';
                if (doc.status_documento !== 'Finalizado pelo Parceiro' && doc.status_documento !== 'Assinado') {
                    finalizarBtnHtml = `<button class="btn btn-success btn-finalizar-parceiro" data-uid="${doc.documento_uid}" title="Marcar como finalizado e pronto para gerar PDF/enviar"><i class="fas fa-check-circle"></i> Finalizar</button>`;
                } else {
                    finalizarBtnHtml = `<button class="btn btn-success" disabled><i class="fas fa-check-circle"></i> Finalizado</button>`;
                }

                card.innerHTML = `
                    <div class="documento-card-header">
                        <h3 class="documento-nome" data-uid="${doc.documento_uid}">${escapeHTML(doc.nome_documento) || 'Documento sem nome'}</h3>
                        <button class="btn-edit-nome" data-uid="${doc.documento_uid}" title="Editar nome do documento"><i class="fas fa-pencil-alt"></i></button>
                    </div>
                    <p class="doc-uid">Link Compartilhável: <input type="text" value="${window.location.origin}${window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'))}/${linkDoc}" readonly onclick="this.select(); document.execCommand('copy'); alert('Link copiado!');" style="width:100%; cursor:pointer;" title="Clique para copiar o link"></p>
                    <p><strong>Agente Indicador (Preenchido):</strong> ${escapeHTML(doc.ag_nome_razao_social) || 'Pendente de preenchimento'}</p>
                    <p><strong>Status:</strong> ${escapeHTML(doc.status_documento)}</p>
                    <p><small>Criado em: ${new Date(doc.data_criacao).toLocaleDateString()}</small></p>
                    <div class="documento-actions">
                        <a href="${linkDoc}" class="btn btn-edit"><i class="fas fa-edit"></i> Editar/Ver Detalhes</a>
                        ${finalizarBtnHtml}
                        <button class="btn btn-primary btn-baixar-pdf-parceiro" data-uid="${doc.documento_uid}"><i class="fas fa-file-pdf"></i> Baixar PDF</button>
                        <button class="btn btn-delete btn-delete-documento" data-uid="${doc.documento_uid}"><i class="fas fa-trash"></i> Excluir</button>
                    </div>
                `;
                listaDocumentosDiv.appendChild(card);
            });

            // Adicionar Event Listeners para os botÃµes nos cards
            document.querySelectorAll('.btn-delete-documento').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    if (confirm('Tem certeza que deseja excluir este documento? Esta ação não pode ser desfeita.')) {
                        excluirDocumento(docUID);
                    }
                });
            });
            document.querySelectorAll('.btn-edit-nome').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    const nomeAtual = this.closest('.documento-card-header').querySelector('.documento-nome').textContent;
                    const novoNome = prompt("Digite o novo nome para o documento:", nomeAtual);
                    if (novoNome !== null && novoNome.trim() !== "" && novoNome.trim() !== nomeAtual) {
                        editarNomeDocumento(docUID, novoNome.trim());
                    }
                });
            });
            document.querySelectorAll('.btn-finalizar-parceiro').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    finalizarDocumentoParceiro(docUID, this);
                });
            });
            document.querySelectorAll('.btn-baixar-pdf-parceiro').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    // Apenas permitir download se o documento estiver em um estado "finalizado"
                    const cardElement = this.closest('.documento-card');
                    const statusElement = cardElement.querySelector('p strong + span'); // Encontra o span do status
                    const statusAtual = statusElement ? statusElement.textContent : '';
                    
                    // if (statusAtual === 'Finalizado pelo Parceiro' || statusAtual === 'Assinado' || statusAtual === 'Finalizado pelo Cliente') {
                        window.open(`api/gerar_pdf_documento.php?uid=${docUID}`, '_blank');
                    // } else {
                    //    alert('O documento precisa ser finalizado antes de gerar o PDF.');
                    // }
                });
            });

        } catch (error) {
            console.error('Erro ao carregar documentos:', error);
            listaDocumentosDiv.innerHTML = `<p style="color:red;">Erro ao carregar documentos. Tente novamente.</p>`;
        }
    }
    
    async function editarNomeDocumento(uid, novoNome) { 
        if (typeof ID_PARCEIRO_LOGADO === 'undefined') {
            alert("Erro: ID do parceiro não definido."); return;
        }
        try {
            const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_documento_nome&documento_uid=${uid}&nome_documento=${encodeURIComponent(novoNome)}&parceiro_id=${ID_PARCEIRO_LOGADO}`
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message || 'Nome do documento atualizado!');
                carregarDocumentos(); 
            } else {
                alert(result.message || 'Erro ao atualizar o nome do documento.');
            }
        } catch (error) {
            console.error('Erro ao editar nome do documento:', error);
            alert('Erro de comunicação não editar o nome.');
        }
    }
    async function excluirDocumento(uid) {
        if (typeof ID_PARCEIRO_LOGADO === 'undefined') {
            alert("Erro: ID do parceiro não definido para exclusÃ£o."); return;
        }
        try {
            const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_documento&documento_uid=${uid}&parceiro_id=${ID_PARCEIRO_LOGADO}`
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message || 'Documento excluÃ­do com sucesso!');
                if (listaDocumentosDiv) { carregarDocumentos(); }
            } else {
                alert(result.message || 'Erro ao excluir documento.');
            }
        } catch (error) {
            console.error('Erro ao excluir documento:', error);
            alert('Erro de comunicação não excluir. Tente novamente.');
        }
    }

    async function finalizarDocumentoParceiro(uid, buttonElement) {
        if (typeof ID_PARCEIRO_LOGADO === 'undefined') {
            alert("Erro: ação não permitida."); return;
        }
        if (!confirm('Tem certeza que deseja marcar este documento como finalizado por você? ApÃ³s esta ação,não link pÃºblico não poderÃ¡ mais ser editado.')) {
            return;
        }
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';
        try {
            const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=finalize_documento_parceiro&documento_uid=${uid}&parceiro_id=${ID_PARCEIRO_LOGADO}`
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message || 'Documento finalizado pelo parceiro!');
                carregarDocumentos();
            } else {
                alert(result.message || 'Erro ao finalizar documento.');
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Doc.';
            }
        } catch (error) {
            alert('Erro de comunicação não finalizar.');
            buttonElement.disabled = false;
            buttonElement.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar Doc.';
        }
    }

    // Função para configurar os botões de mostrar/ocultar itens da tabela
    function setupToggleVisibilityButtons() {
        if (!IS_PARCEIRO_DONO) return; // Só para parceiros donos

        formDocumento.querySelectorAll('.btn-toggle-visibility').forEach(button => {
            button.addEventListener('click', function() {
                if (this.disabled) return; // Não fazer nada se o botão estiver desabilitado

                const row = this.closest('tr');
                const visibilidadeInput = row.querySelector('input.input-visibilidade');
                if (!visibilidadeInput) return;

                const isCurrentlyVisible = visibilidadeInput.value === 'true';
                if (isCurrentlyVisible) {
                    visibilidadeInput.value = 'false';
                    this.innerHTML = '<i class="fas fa-eye"></i> Mostrar';
                    row.classList.add('item-oculto');
                } else {
                    visibilidadeInput.value = 'true';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar';
                    row.classList.remove('item-oculto');
                }
            });
        });
    }


    async function carregarDadosFormulario(uid) {
        if (!formDocumento || !uid) return;
        const submitButton = formDocumento.querySelector('button[type="submit"]');
        if(submitButton) submitButton.disabled = true;
    
        try {
        const response = await fetch(`api/gerenciar_documentos_indicacao.php?action=get_documento_details_public&uid=${uid}`);
        if (!response.ok) throw new Error("Falha ao buscar dados do documento.");
        const result = await response.json();
    
        if (result.success && result.data) {
        const dados = result.data;
        // Preencher campos gerais
        for (const key in dados) {
        if (formDocumento.elements[key]) {
        if (formDocumento.elements[key].type === 'date' && dados[key]) {
        formDocumento.elements[key].value = dados[key].split('T')[0];
        } else if (formDocumento.elements[key].type === 'select-one' && formDocumento.elements[key].name === 'pagamento_tipo') {
        formDocumento.elements[key].value = dados[key] || 'Split'; // PadrÃ£o se nulo
        }
        else {
        formDocumento.elements[key].value = dados[key] || '';
        }
        }
        }
        // Preencher tabela de valores e configurar visibilidade
        const tabelaValoresBody = document.getElementById('tabelaValoresBody');
        if (tabelaValoresBody && dados.tabela_valores_json) {
            const tabelaDataCompleta = JSON.parse(dados.tabela_valores_json);
            const rowsNoDOM = tabelaValoresBody.querySelectorAll('tr');
    
            if (IS_PARCEIRO_DONO) {
                // Lógica para o PARCEIRO DONO
                rowsNoDOM.forEach((row, index) => { // Para o parceiro, os índices do DOM e da API devem coincidir
                    const itemData = tabelaDataCompleta[index];
                    if (itemData) {
                        // Preencher inputs da tabela
                        const custoJedInput = row.querySelector(`input[name="tabela_valores[${index}][custo_jed]"]`);
                        if (custoJedInput) custoJedInput.value = itemData.custo_jed || '';
                        
                        const vendaClienteFinalInput = row.querySelector(`input[name="tabela_valores[${index}][venda_cliente_final]"]`);
                        if (vendaClienteFinalInput) vendaClienteFinalInput.value = itemData.venda_cliente_final || '';
    
                        const sugestaoInput = row.querySelector(`input[name="tabela_valores[${index}][sugestao]"]`);
                        if (sugestaoInput) sugestaoInput.value = itemData.sugestao || '';
    
                        // Atualizar input hidden de visibilidade
                        const visibilidadeInputHidden = row.querySelector(`input[name="tabela_valores[${index}][visivel]"]`);
                        if (visibilidadeInputHidden) visibilidadeInputHidden.value = itemData.visivel ? 'true' : 'false';
    
                        // Atualizar botão de toggle
                        const toggleBtn = row.querySelector('.btn-toggle-visibility'); // Assumindo um por linha
                        if (toggleBtn) {
                            toggleBtn.innerHTML = itemData.visivel ? '<i class="fas fa-eye-slash"></i> Ocultar' : '<i class="fas fa-eye"></i> Mostrar';
                        }
    
                        // Aplicar/remover classe 'item-oculto'
                        if (itemData.visivel) {
                            row.classList.remove('item-oculto');
                        } else {
                            row.classList.add('item-oculto');
                        }
                    }
                });
            } else {
                // Lógica para o CONVIDADO
                // O PHP já renderizou APENAS as linhas visíveis.
                // Os valores dos inputs também já foram preenchidos pelo PHP na carga inicial.
                // O JS aqui apenas garante que a classe 'item-oculto' não seja aplicada a essas linhas.
                rowsNoDOM.forEach(row => {
                    row.classList.remove('item-oculto');
                    // Nota: Se os valores dos inputs precisarem ser atualizados dinamicamente para o convidado
                    // (ex: após um "Salvar Progresso" que altere 'venda_cliente_final'),
                    // seria necessário implementar uma forma de mapear a 'row' do DOM ao 'itemData' correto
                    // da 'tabelaDataCompleta', por exemplo, usando um atributo 'data-original-index'
                    // adicionado pelo PHP na tag <tr>. Por ora, esta correção foca no problema visual.
                });
            }
        }
    
    // Atualizar campos de responsÃ¡vel legal (display)
    const respParceiroDisplay = document.getElementById('decl_resp_parceiro_display');
    if (respParceiroDisplay) {
    const nomeRep = formDocumento.elements['ag_representante_legal'].value;
    const cpfRep = formDocumento.elements['ag_cpf_representante'].value;
    respParceiroDisplay.value = `${nomeRep || ''}${cpfRep ? ' (CPF: ' + cpfRep + ')' : ''}`;
    }


    // Habilitar/desabilitar campos com base em IS_PARCEIRO_DONO e STATUS_DOCUMENTO_ATUAL
    // A query agora inclui 'button' para que os botões de ação também sejam afetados.
    const isFinalizadoParceiro = STATUS_DOCUMENTO_ATUAL === 'Finalizado pelo Parceiro' || STATUS_DOCUMENTO_ATUAL === 'Assinado';
    const isFinalizadoCliente = STATUS_DOCUMENTO_ATUAL === 'Finalizado pelo Cliente';

    formDocumento.querySelectorAll('input, textarea, select, button').forEach(el => {
        // Ensure 'decl_resp_parceiro' and 'decl_resp_pa' are always readonly (inputs)
        if (el.name === 'decl_resp_parceiro' || el.name === 'decl_resp_pa') {
            el.readOnly = true;
            return; 
        }

        let makeReadOnly = false;
        let makeDisabled = false;

        if (isFinalizadoParceiro) {
            makeReadOnly = true;
            makeDisabled = true; // Desabilita tudo, incluindo botões e selects
        } else {
            // Campos editáveis apenas pelo parceiro
            if (el.name === 'pagamento_tipo' || el.name === 'obs_pa_indicacoes' || 
               (el.name && el.name.includes('[custo_jed]')) || 
               (el.name && el.name.includes('[sugestao]')) ||
               el.classList.contains('btn-toggle-visibility')) { // Botão de toggle é restrito ao parceiro
                if (!IS_PARCEIRO_DONO) {
                    makeReadOnly = true;
                    makeDisabled = true;
                }
            } 
            // Campos que o cliente não pode editar após finalizar (e não é parceiro dono)
            else if (isFinalizadoCliente && !IS_PARCEIRO_DONO) {
                makeReadOnly = true;
                makeDisabled = true;
            }
        }

        if (el.tagName === 'SELECT' || el.tagName === 'BUTTON') {
            if (makeDisabled) el.disabled = true;
            // Se não for para desabilitar, o PHP já cuidou do estado inicial,
            // e o JS não deve re-habilitar indiscriminadamente aqui,
            // exceto para o botão de submit que é tratado no final.
            // Para botões de toggle, o PHP já os desabilita se $disabled_geral.
            // Se IS_PARCEIRO_DONO for false, eles nem são renderizados ou são desabilitados pelo PHP.
            else if (IS_PARCEIRO_DONO && el.classList.contains('btn-toggle-visibility') && !isFinalizadoParceiro) {
                 el.disabled = false; // Garante que o parceiro possa usar se o doc não estiver finalizado por ele
            }

        } else { // input, textarea
            if (makeReadOnly) el.readOnly = true;
        }
    });
    
    if(submitButton) {
        submitButton.disabled = isFinalizadoParceiro || (isFinalizadoCliente && !IS_PARCEIRO_DONO);
    }

    const btnFinalizarCliente = document.getElementById('btnFinalizarCliente');
    if (btnFinalizarCliente) {
        btnFinalizarCliente.disabled = isFinalizadoParceiro || isFinalizadoCliente;
        if(isFinalizadoCliente) btnFinalizarCliente.textContent = 'Enviado para Parceiro';
    }

    // Configurar os botões de toggle após os dados serem carregados e os estados dos botões definidos
    setupToggleVisibilityButtons();


    } else {
    showMessage(formDocMessageDiv, result.message || "Documento não encontrado ou erro ao carregar.", 'error');
    formDocumento.querySelectorAll('input, textarea, select, button').forEach(el => el.disabled = true);
    }
    } catch (error) {
    console.error("Erro ao carregar dados do formulário:", error);
    showMessage(formDocMessageDiv, "Erro de comunicação não carregar dados.", 'error');
    if(submitButton) submitButton.disabled = false; // Re-habilita em caso de erro de comunicação
    }
    }

    if (formDocumento) {
        if (typeof DOCUMENTO_UID_ATUAL !== 'undefined' && DOCUMENTO_UID_ATUAL) {
            carregarDadosFormulario(DOCUMENTO_UID_ATUAL); // setupToggleVisibilityButtons é chamado dentro dela
        }
        // Atualizar display do responsÃ¡vel legal do parceiro indicador dinamicamente
        const agRepLegalInput = formDocumento.elements['ag_representante_legal'];
        const agCpfRepInput = formDocumento.elements['ag_cpf_representante'];
        const declRespParceiroDisplay = document.getElementById('decl_resp_parceiro_display');

        function atualizarDisplayResponsavel() {
            if (declRespParceiroDisplay && agRepLegalInput && agCpfRepInput) {
                const nome = agRepLegalInput.value;
                const cpf = agCpfRepInput.value;
                declRespParceiroDisplay.value = `${nome || ''}${cpf ? ' (CPF: ' + cpf + ')' : ''}`;
            }
        }
        if (agRepLegalInput) agRepLegalInput.addEventListener('input', atualizarDisplayResponsavel);
        if (agCpfRepInput) agCpfRepInput.addEventListener('input', atualizarDisplayResponsavel);


        formDocumento.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(formDocumento);
            formData.append('action', 'save_documento_public'); 
            
            const submitButton = formDocumento.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            clearMessage(formDocMessageDiv);

            try {
                const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                    method: 'POST',
                    body: formData 
                });
                const result = await response.json();
                if (result.success) {
                    showMessage(formDocMessageDiv, result.message || 'Progresso salvo com sucesso!', 'success');
                    // Se o status mudou, pode ser necessÃ¡rio atualizar a UI dos botÃµes
                    if (result.status_documento && result.status_documento !== window.STATUS_DOCUMENTO_ATUAL) {
                        // Atualiza a variÃ¡vel global e os botÃµes
                        window.STATUS_DOCUMENTO_ATUAL = result.status_documento; 
                        const btnFinalizarCliente = document.getElementById('btnFinalizarCliente');
                        if (btnFinalizarCliente) {
                            const isFinalizadoCliente = result.status_documento === 'Finalizado pelo Cliente';
                            const isFinalizadoParceiro = result.status_documento === 'Finalizado pelo Parceiro' || result.status_documento === 'Assinado';
                            btnFinalizarCliente.disabled = isFinalizadoCliente || isFinalizadoParceiro;
                            if(isFinalizadoCliente) btnFinalizarCliente.textContent = 'Enviado para Parceiro';
                        }
                    }
                } else {
                    showMessage(formDocMessageDiv, result.message || 'Erro ao salvar progresso.');
                }
            } catch (error) {
                console.error('Erro ao salvar progresso:', error);
                showMessage(formDocMessageDiv, 'Erro de comunicação não salvar. Tente novamente.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save"></i> Salvar Progresso';
            }
        });

        const btnFinalizarCliente = document.getElementById('btnFinalizarCliente');
        if (btnFinalizarCliente) {
            btnFinalizarCliente.addEventListener('click', async function() {
                if (!DOCUMENTO_UID_ATUAL) {
                    alert('Erro: UID do documento não encontrado.'); return;
                }
                if (!confirm('Tem certeza que deseja concluir o preenchimento e enviar para o parceiro? ApÃ³s esta ação,não não poderÃ¡ mais editar este formulário.')) {
                    return;
                }
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                try {
                    const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        // Usar uma ação não para o cliente finalizar, se a API for diferente de 'finalize_documento_parceiro'
                        // Por enquanto, vamos assumir que 'finalize_documento_public' na API lida com isso ou que o status muda ao salvar se completo.
                        // Para uma ação não de finalização não cliente:
                        body: `action=finalize_documento_cliente&documento_uid=${DOCUMENTO_UID_ATUAL}` // Precisa criar essa action na API
                    });
                    const result = await response.json();
                    if (result.success) {
                        showMessage(formDocMessageDiv, result.message || 'Documento enviado para o parceiro!', 'success');
                        this.textContent = 'Enviado para Parceiro';
                        formDocumento.querySelectorAll('input, textarea, button[type="submit"]').forEach(el => el.disabled = true);
                    } else {
                        showMessage(formDocMessageDiv, result.message || 'Erro ao enviar documento.');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check-circle"></i> Concluir e Enviar para Parceiro';
                    }
                } catch (error) {
                    showMessage(formDocMessageDiv, 'Erro de comunicação não enviar.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check-circle"></i> Concluir e Enviar para Parceiro';
                }
            });
        }
    }

    if (listaDocumentosDiv && typeof ID_PARCEIRO_LOGADO !== 'undefined') {
        carregarDocumentos();
    }
});