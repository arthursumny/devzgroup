document.addEventListener('DOMContentLoaded', function() {
    const listaDocumentosTableBody = document.getElementById('listaDocumentosTableBody'); // Changed from listaDocumentosDiv
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
        if (!listaDocumentosTableBody || typeof ID_PARCEIRO_LOGADO === 'undefined') return;
        // The initial "Carregando..." message is already in the HTML tbody.
        // We will replace it if data is fetched or an error occurs.

        try {
            const response = await fetch('api/gerenciar_documentos_indicacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_documentos&parceiro_id=${ID_PARCEIRO_LOGADO}`
            });
            if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
            const result = await response.json();

            if (!result.success) {
                 listaDocumentosTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">${escapeHTML(result.message)}</td></tr>`;
                 return;
            }
            if (!result.data || result.data.length === 0) {
                listaDocumentosTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Você ainda não gerou nenhum documento de indicação.</td></tr>`;
                return;
            }

            listaDocumentosTableBody.innerHTML = ''; // Clear loading message or previous content
            result.data.forEach(doc => {
                const tr = document.createElement('tr');
                const linkDoc = `formulario-indicacao.php?uid=${doc.documento_uid}`;

                const tdNome = tr.insertCell();
                tdNome.textContent = escapeHTML(doc.nome_documento) || 'Documento sem nome';
                // It might be good to allow editing the name via an icon in actions later.
                // For now, clicking the name could lead to details, or it's just text.

                const tdAgente = tr.insertCell();
                tdAgente.textContent = escapeHTML(doc.ag_nome_razao_social) || 'Pendente';

                const tdStatus = tr.insertCell();
                tdStatus.textContent = escapeHTML(doc.status_documento);

                const tdCriadoEm = tr.insertCell();
                tdCriadoEm.textContent = new Date(doc.data_criacao).toLocaleDateString();

                const tdAcoes = tr.insertCell();
                tdAcoes.classList.add('acoes-cell'); // Adiciona a classe para estilização da célula

                // Botão Visualizar/Editar Detalhes
                const viewBtn = document.createElement('a');
                viewBtn.href = linkDoc;
                viewBtn.classList.add('btn-acao', 'btn-view');
                viewBtn.title = "Visualizar / Editar Detalhes";
                viewBtn.innerHTML = '<i class="fas fa-eye"></i>';
                tdAcoes.appendChild(viewBtn);

                // Botão Finalizar pelo Parceiro
                if (doc.status_documento !== 'Finalizado pelo Parceiro' && doc.status_documento !== 'Assinado') {
                    const finalizarBtn = document.createElement('button');
                    finalizarBtn.classList.add('btn-acao', 'btn-finalizar-parceiro');
                    finalizarBtn.dataset.uid = doc.documento_uid;
                    finalizarBtn.title = "Marcar como finalizado pelo parceiro";
                    finalizarBtn.innerHTML = '<i class="fas fa-check-circle"></i>';
                    tdAcoes.appendChild(finalizarBtn);
                } else {
                    // Opcional: Mostrar um ícone estático se já finalizado
                    const finalizadoIcon = document.createElement('span');
                    finalizadoIcon.classList.add('btn-acao'); // Para manter o espaçamento/tamanho
                    finalizadoIcon.title = doc.status_documento;
                    finalizadoIcon.innerHTML = '<i class="fas fa-check-circle" style="color: green;"></i>'; // Estilo inline para cor, pode ser classe CSS
                    finalizadoIcon.style.cursor = 'default';
                    //tdAcoes.appendChild(finalizadoIcon); // Descomente se quiser mostrar um ícone para finalizados
                }

                // Botão Baixar PDF
                const pdfBtn = document.createElement('button');
                pdfBtn.classList.add('btn-acao', 'btn-baixar-pdf-parceiro');
                pdfBtn.dataset.uid = doc.documento_uid;
                pdfBtn.title = "Baixar PDF do Documento";
                pdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i>';
                tdAcoes.appendChild(pdfBtn);

                // Botão Gerar Word (se aplicável)
                if (doc.status_documento === 'Finalizado pelo Parceiro' || doc.status_documento === 'Assinado') {
                    const gerarWordBtn = document.createElement('button');
                    gerarWordBtn.classList.add('btn-acao', 'btn-gerar-word');
                    gerarWordBtn.dataset.uid = doc.documento_uid;
                    gerarWordBtn.title = "Gerar Documento Word (.docx)";
                    gerarWordBtn.innerHTML = '<i class="fas fa-file-word"></i>';
                    tdAcoes.appendChild(gerarWordBtn);
                }

                // Botão Excluir Documento
                const deleteBtn = document.createElement('button');
                deleteBtn.classList.add('btn-acao', 'btn-delete'); // Usa a classe btn-delete do CSS
                deleteBtn.dataset.uid = doc.documento_uid;
                deleteBtn.title = "Excluir Documento";
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                tdAcoes.appendChild(deleteBtn);
                
                // tdAcoes.innerHTML = viewBtnHtml + " " + finalizarBtnHtml + " " + pdfBtnHtml + " " + deleteBtnHtml + " " + gerarWordBtnHtml;
                // A linha acima foi substituída pela criação e apensamento dos elementos individualmente.

                listaDocumentosTableBody.appendChild(tr);
            });

            // Re-attach Event Listeners for the new buttons
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    if (confirm('Tem certeza que deseja excluir este documento? Esta ação não pode ser desfeita.')) {
                        excluirDocumento(docUID);
                    }
                });
            });
            // Removed .btn-edit-nome listener as the button is removed. editarNomeDocumento function remains.

            document.querySelectorAll('.btn-finalizar-parceiro').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    finalizarDocumentoParceiro(docUID, this);
                });
            });
            document.querySelectorAll('.btn-baixar-pdf-parceiro').forEach(button => {
                button.addEventListener('click', function() {
                    const docUID = this.dataset.uid;
                    // Logic for checking status before download can be added here or server-side
                    window.open(`api/gerar_pdf_documento.php?uid=${docUID}`, '_blank');
                });
            });
            // Note: Event listener for btn-gerar-word will be added in a subsequent step/task.

        } catch (error) {
            console.error('Erro ao carregar documentos:', error);
            if (listaDocumentosTableBody) { // Check if still exists
                listaDocumentosTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Erro ao carregar documentos. Tente novamente.</td></tr>`;
            }
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
                if (listaDocumentosTableBody) { carregarDocumentos(); } // Target updated
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

    if (listaDocumentosTableBody && typeof ID_PARCEIRO_LOGADO !== 'undefined') { // Target updated
        carregarDocumentos();
    }

    // --- Event Listener for dynamically added buttons in the table ---
    if (listaDocumentosTableBody) {
        listaDocumentosTableBody.addEventListener('click', function(event) {
            const targetButton = event.target.closest('button.btn-action');
            if (!targetButton) return;

            const docUID = targetButton.dataset.uid;

            if (targetButton.classList.contains('btn-gerar-word')) {
                event.preventDefault();
                if (docUID) {
                    gerarDocumentoWord(docUID, targetButton);
                }
            }
            // Note: Other buttons like delete, finalize, download PDF are already handled
            // by querySelectorAll after carregarDocumentos. If issues arise with dynamic content
            // for those, they could be moved here as well. For now, only .btn-gerar-word is added.
        });
    }

});

async function gerarDocumentoWord(uid, buttonElement) {
    if (!uid) {
        alert('UID do documento não encontrado.');
        return;
    }

    const originalButtonContent = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';

    try {
        // 1. Fetch document details
        const responseDocDetails = await fetch(`api/gerenciar_documentos_indicacao.php?action=get_documento_details_public&uid=${uid}`);
        if (!responseDocDetails.ok) {
            throw new Error(`Erro ao buscar detalhes do documento: ${responseDocDetails.statusText}`);
        }
        const resultDocDetails = await responseDocDetails.json();
        if (!resultDocDetails.success || !resultDocDetails.data) {
            throw new Error(resultDocDetails.message || 'Não foi possível obter os dados do documento.');
        }
        const docData = resultDocDetails.data;

        // 2. Fetch the .docx template
        // Path is relative to the HTML file, so if HTML is at root and js is in /js, docx in /docx, this path is correct.
        const responseTemplate = await fetch('docx/indicacao.docx'); 
        if (!responseTemplate.ok) {
            throw new Error('Erro ao carregar o template Word (docx/indicacao.docx). Verifique o caminho e a disponibilidade do arquivo.');
        }
        const templateArrayBuffer = await responseTemplate.arrayBuffer();

        // 3. Create PizZip instance and load template
        const zip = new PizZip(templateArrayBuffer);
        const doc = new window.docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true,
            nullGetter: function(part) { 
                if (part.module === "raw" && part.type === "placeholder") {
                     // Check against a list of known prefixes or exact names
                    const knownPrefixes = ["AG_", "BANCO_", "DECL_", "OBS_", "PA_", "ITEM_", "PAGAMENTO_TIPO"];
                    if (knownPrefixes.some(prefix => part.value.startsWith(prefix)) || part.value === "PAGAMENTO_TIPO") {
                        return ""; // Return empty string for your specific placeholders
                    }
                }
                // For any other undefined/null placeholder, docxtemplater will throw an error by default.
                // To return "" for *all* undefined placeholders (less strict):
                // return ""; 
                // However, it's often better to ensure all template variables are explicitly handled.
                // If an error is thrown here for an unexpected placeholder, it means the template has a variable
                // not accounted for in templateData or the nullGetter logic.
                // For this implementation, we only make known ones empty.
                // If you want truly ALL missing to be empty, just return "" here unconditionally.
                // For now, let's stick to the provided logic which is more specific.
                return ""; // Fallback as per original snippet for specific placeholders
            }
        });

        // 4. Prepare data for the template
        const templateData = {
            AG_NOME_RAZAO_SOCIAL: docData.ag_nome_razao_social || '',
            AG_NOME_FANTASIA: docData.ag_nome_fantasia || '',
            AG_CPF_CNPJ: docData.ag_cpf_cnpj || '', 
            AG_ENDERECO: docData.ag_endereco || '', // Assuming template uses AG_ENDERECO
            AG_COMPLEMENTO: docData.ag_complemento || '',
            AG_BAIRRO: docData.ag_bairro || '',
            AG_CIDADE: docData.ag_cidade || '',
            AG_CEP: docData.ag_cep || '',
            AG_UF: docData.ag_uf || '',
            AG_REP_LEGAL: docData.ag_representante_legal || '',
            AG_CARGO: docData.ag_cargo || '',
            AG_CPF_REP: docData.ag_cpf_representante || '',
            AG_EMAIL: docData.ag_email || '',
            AG_TELEFONE: docData.ag_telefone || '',
            BANCO_NOME_TITULAR: docData.banco_nome_razao_social || '',
            BANCO_CPF_CNPJ_TITULAR: docData.banco_cpf_cnpj || '',
            BANCO_NOME: docData.banco_nome || '',
            BANCO_AGENCIA: docData.banco_agencia || '',
            BANCO_CONTA: docData.banco_conta || '',
            BANCO_TIPO_CONTA: docData.banco_tipo_conta || '',
            BANCO_CHAVE_PIX: docData.banco_chave_pix || '',
            PAGAMENTO_TIPO: docData.pagamento_tipo || '',
            OBS_PA_INDICACOES: docData.obs_anotacoes || '', 
            PA_INDICACOES: docData.obs_pa_indicacoes || '', 
            DECL_LOCAL: docData.decl_local || '',
            DECL_DATA: docData.decl_data ? new Date(docData.decl_data).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) : '', // Added timeZone UTC
            DECL_RESP_PARCEIRO: docData.decl_resp_parceiro || '',
            DECL_RESP_PA: docData.decl_resp_pa || '',
            TABELA_PRODUTOS: []
        };

        if (docData.tabela_valores_json) {
            const tabelaValores = JSON.parse(docData.tabela_valores_json);
            templateData.TABELA_PRODUTOS = tabelaValores
                .filter(item => item.visivel !== false && item.visivel !== 'false')
                .map(item => ({
                    ITEM_PRODUTO_SERVICO: item.produto || '',
                    ITEM_CUSTO_JED: item.custo_jed || '',
                    ITEM_VENDA_CLIENTE_FINAL: item.venda_cliente_final || '',
                    ITEM_SUGESTAO_PARCEIRO: item.sugestao || ''
                }));
        }

        doc.setData(templateData);
        doc.render(); 

        const out = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        });

        let fileName = "indicacao_documento.docx";
        if (docData.nome_documento) {
            // Sanitize filename: replace non-alphanumeric (excluding _, ., -) with _
            const sanitizedName = docData.nome_documento.replace(/[^\w.-]/gi, '_');
            fileName = `indicacao_${sanitizedName}.docx`;
        }
        saveAs(out, fileName);

    } catch (error) {
        console.error('Erro ao gerar documento Word:', error);
        // Provide a more user-friendly error message, potentially distinguishing between network/fetch errors and template processing errors.
        let userMessage = 'Erro ao gerar documento Word.';
        if (error.message.includes("template Word")) {
            userMessage = `Erro ao carregar o template Word (${error.message}). Verifique se o arquivo 'docx/indicacao.docx' existe no servidor.`;
        } else if (error.message.includes("dados do documento")) {
            userMessage = `Erro ao buscar dados do documento: ${error.message}.`;
        } else {
            userMessage = `Ocorreu um erro inesperado: ${error.message}`;
        }
        alert(userMessage);
    } finally {
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalButtonContent;
    }
}