document.addEventListener('DOMContentLoaded', function() {
    const listaPropostasTableBody = document.getElementById('listaPropostasTableBody');
    const btnGerarNovo = document.querySelector('.btn-gerar-novo');

    // Elementos do Filtro
    const filtroNomeInput = document.getElementById('filtroNome');
    const filtroDataInput = document.getElementById('filtroData');
    const filtroStatusInput = document.getElementById('filtroStatus');
    const btnAplicarFiltros = document.getElementById('btnAplicarFiltros');
    const btnLimparFiltros = document.getElementById('btnLimparFiltros');

    let todasAsPropostas = []; // Para armazenar a lista completa de propostas

    function showMessage(message, type = 'success') {
        // Implementar sistema de notificação
        alert(message);
    }

    async function carregarPropostas() {
        if (!listaPropostasTableBody || typeof ID_PARCEIRO_LOGADO === 'undefined') return;

        listaPropostasTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Carregando propostas...</td></tr>`;

        try {
            const response = await fetch(`api/gerenciar_propostas.php?action=get_propostas&parceiro_id=${ID_PARCEIRO_LOGADO}`);
            if (!response.ok) throw new Error('Erro ao carregar propostas');
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Erro ao carregar propostas');

            todasAsPropostas = result.data || [];
            aplicarFiltrosEExibir();

        } catch (error) {
            console.error('Erro:', error);
            listaPropostasTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Erro ao carregar propostas: ${error.message}</td></tr>`;
        }
    }

    function formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    function formatarData(data) {
        return new Date(data).toLocaleDateString('pt-BR');
    }

    function aplicarFiltrosEExibir() {
        if (!listaPropostasTableBody) return;

        const nomeFiltro = filtroNomeInput ? filtroNomeInput.value.toLowerCase() : '';
        const dataFiltro = filtroDataInput ? filtroDataInput.value : '';
        const statusFiltro = filtroStatusInput ? filtroStatusInput.value : '';

        const propostasFiltradas = todasAsPropostas.filter(proposta => {
            const matchNome = !nomeFiltro || proposta.nome_cliente.toLowerCase().includes(nomeFiltro);
            const matchData = !dataFiltro || proposta.data_criacao.includes(dataFiltro);
            const matchStatus = !statusFiltro || proposta.status === statusFiltro;
            return matchNome && matchData && matchStatus;
        });

        if (propostasFiltradas.length === 0) {
            listaPropostasTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">Nenhuma proposta encontrada</td></tr>`;
            return;
        }

        listaPropostasTableBody.innerHTML = propostasFiltradas.map(proposta => `
            <tr>
                <td>${proposta.nome_cliente}</td>
                <td>${formatarMoeda(proposta.valor_total)}</td>
                <td>${proposta.status}</td>
                <td>${formatarData(proposta.data_criacao)}</td>
                <td>${formatarData(proposta.data_validade)}</td>
                <td>
                    <button class="btn-action btn-edit" onclick="editarProposta('${proposta.id}')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-download" onclick="gerarDocumentoProposta('${proposta.id}', this)" title="Baixar Proposta">
                        <i class="fas fa-file-word"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="excluirProposta('${proposta.id}')" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    if (btnAplicarFiltros) {
        btnAplicarFiltros.addEventListener('click', aplicarFiltrosEExibir);
    }

    if (btnLimparFiltros) {
        btnLimparFiltros.addEventListener('click', () => {
            if (filtroNomeInput) filtroNomeInput.value = '';
            if (filtroDataInput) filtroDataInput.value = '';
            if (filtroStatusInput) filtroStatusInput.value = '';
            aplicarFiltrosEExibir();
        });
    }

    // Carregar propostas ao iniciar
    if (listaPropostasTableBody && typeof ID_PARCEIRO_LOGADO !== 'undefined') {
        carregarPropostas();
    }
});

async function editarProposta(id) {
    window.location.href = `formulario-proposta.php?id=${id}`;
}

async function excluirProposta(id) {
    if (!confirm('Tem certeza que deseja excluir esta proposta?')) return;

    try {
        const response = await fetch('api/gerenciar_propostas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_proposta&id=${id}`
        });

        if (!response.ok) throw new Error('Erro ao excluir proposta');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Erro ao excluir proposta');

        window.location.reload();

    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao excluir proposta: ' + error.message);
    }
}

async function gerarDocumentoProposta(id, buttonElement) {
    if (!id) {
        alert("ID da proposta não fornecido.");
        return;
    }

    const originalButtonContent = buttonElement ? buttonElement.innerHTML : '<i class="fas fa-file-word"></i>';
    if (buttonElement) {
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
    }

    try {
        // 1. Buscar dados da proposta
        const response = await fetch(`api/gerenciar_propostas.php?action=get_proposta_details&id=${id}`);
        if (!response.ok) throw new Error('Erro ao buscar dados da proposta');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Erro ao buscar dados da proposta');

        // 2. Buscar template
        const responseTemplate = await fetch('docx/proposta_comercial.docx');
        if (!responseTemplate.ok) throw new Error('Erro ao carregar template');
        
        const templateArrayBuffer = await responseTemplate.arrayBuffer();

        // 3. Gerar documento
        const zip = new PizZip(templateArrayBuffer);
        const doc = new window.docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true
        });

        // 4. Preparar dados para o template
        const propostaData = result.data;
        doc.setData(propostaData);
        doc.render();

        // 5. Gerar arquivo final
        const out = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        });

        // 6. Download do arquivo
        const fileName = `proposta_${propostaData.numero_proposta || id}.docx`;
        saveAs(out, fileName);

        if (buttonElement) {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalButtonContent;
        }

    } catch (error) {
        console.error('Erro ao gerar proposta:', error);
        alert('Erro ao gerar proposta: ' + error.message);
        if (buttonElement) {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalButtonContent;
        }
    }
}
