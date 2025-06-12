<?php
session_start();

$documento_uid_param = $_GET['uid'] ?? null;
$dados_documento = null;

if (empty($documento_uid_param)) {
    die("Erro: Identificador do documento não fornecido.");
}

// Conectar ao DB para buscar dados do documento
define('DB_HOST_VIS', 'mysql64-farm2.uni5.net');
define('DB_USER_VIS', 'devzgroup');
define('DB_PASS_VIS', 'D3vzgr0up');
define('DB_NAME_VIS', 'devzgroup');

$conn_vis = new mysqli(DB_HOST_VIS, DB_USER_VIS, DB_PASS_VIS, DB_NAME_VIS);
if ($conn_vis->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn_vis->connect_error);
}
$conn_vis->set_charset("utf8mb4");

// Buscar dados do documento
$stmt_doc = $conn_vis->prepare("SELECT d.*, u.nome_completo as nome_parceiro_criador, u.username as username_parceiro_criador 
                                FROM documentos_indicacao d 
                                JOIN usuarios u ON d.parceiro_id = u.id 
                                WHERE d.documento_uid = ?");
if ($stmt_doc) {
    $stmt_doc->bind_param("s", $documento_uid_param);
    $stmt_doc->execute();
    $result_doc = $stmt_doc->get_result();
    $dados_documento = $result_doc->fetch_assoc();
    $stmt_doc->close();
}

if (!$dados_documento) {
    $conn_vis->close();
    die("Erro: Documento não encontrado ou inválido.");
}

$conn_vis->close();

// Processar tabela de valores
$tabela_valores = !empty($dados_documento['tabela_valores_json']) ? json_decode($dados_documento['tabela_valores_json'], true) : [];
$tabela_produtos_visiveis = array_filter($tabela_valores, function($item) {
    return isset($item['visivel']) && $item['visivel'] === true;
});

// Função para sanitizar dados
function sanitize($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento de Indicação - <?php echo sanitize($dados_documento['nome_documento']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            line-height: 1.2;
            color: #000;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        #documento-para-pdf {
            background-color: white;
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-height: 297mm;
        }
        
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 11pt;
            margin-top: 8px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .subsection-title {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 6px;
            margin-bottom: 4px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 6px;
        }
        
        .field-group {
            margin-bottom: 2px;
            font-size: 9pt;
            line-height: 1.1;
        }
        
        .field-label {
            font-weight: bold;
            display: inline;
        }
        
        .field-value {
            display: inline;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        .company-info {
            text-align: justify;
            margin-bottom: 8px;
            line-height: 1.2;
            font-size: 9pt;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: top;
            font-size: 9pt;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .currency {
            text-align: right;
        }
        
        .signature-area {
            margin-top: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .signature-box {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 25px;
            font-size: 9pt;
        }
        
        .observations {
            margin: 8px 0;
            min-height: 30px;
        }
        
        .declaration {
            margin: 8px 0;
            text-align: justify;
            line-height: 1.2;
            font-size: 9pt;
        }
        
        .btn-container {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
        }
        
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .section-title {
            page-break-after: avoid;
            break-after: avoid;
        }
        
        .subsection-title {
            page-break-after: avoid;
            break-after: avoid;
        }
        
        .content-grid {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .observations {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .signature-area {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            
            .btn-container {
                display: none;
            }
            
            #documento-para-pdf {
                box-shadow: none;
                margin: 0;
                padding: 10mm;
                max-width: none;
            }
            
            table {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>
    <div class="btn-container">
        <button id="btn-baixar-pdf" class="btn">Baixar PDF</button>
    </div>

    <div id="documento-para-pdf">
        <div class="header-title">
            Formulário de Indicação de Negócios
        </div>

        <div class="section-title">1. IDENTIFICAÇÃO DAS PARTES</div>
        
        <div class="company-info">
            <strong>DEVZ SOLUÇÕES LTDA</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 35.115.124/0001-05, com sede à Rua Felipe Schmidt, nº 654, Centro, Joaçaba-SC, CEP 89600-000, neste ato representada na forma de seu contrato social, doravante denominada simplesmente por <strong>DEVZGROUP</strong>. Fone: (49) 3307-3150
        </div>

        <div class="subsection-title">1.2. AGENTE INDICADOR DO NEGÓCIO</div>
        
        <div class="content-grid">
            <div class="field-group">
                <span class="field-label">Nome/Razão social:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_nome_razao_social']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Nome fantasia:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_nome_fantasia']); ?></span>
            </div>
            <div class="field-group full-width">
                <span class="field-label">Endereço:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_endereco']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Complemento:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_complemento']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Bairro:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_bairro']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Cidade:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_cidade']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">CEP:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_cep']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">UF:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_uf']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">CPF/CNPJ:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_cpf_cnpj']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Nome do representante legal:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_representante_legal']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Cargo/Função:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_cargo']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">CPF:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_cpf_representante']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">RG:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_rg_representante']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">E-mail:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_email']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Telefone:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['ag_telefone']); ?></span>
            </div>
        </div>

        <div class="subsection-title">1.3. INFORMAÇÕES BANCÁRIAS PARA PAGAMENTO</div>
        
        <div class="content-grid">
            <div class="field-group">
                <span class="field-label">Nome/Razão social:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_nome_razao_social']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">CPF/CNPJ:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_cpf_cnpj']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Banco:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_nome']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Agência:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_agencia']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Conta:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_conta']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Tipo (Corrente/Poupança):</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_tipo_conta']); ?></span>
            </div>
            <div class="field-group full-width">
                <span class="field-label">Chave Pix:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['banco_chave_pix']); ?></span>
            </div>
        </div>

        <?php if (!empty($tabela_produtos_visiveis)): ?>
        <div class="section-title">2. TABELA DE VALORES</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 60%;">Produto/Serviço</th>
                    <th style="width: 20%;">Custo JED</th>
                    <th style="width: 20%;">Venda Cliente Final</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tabela_produtos_visiveis as $produto): ?>
                <tr>
                    <td><?php echo sanitize($produto['produto']); ?></td>
                    <td class="currency">R$ <?php echo sanitize($produto['custo_jed']); ?></td>
                    <td class="currency">R$ <?php echo sanitize($produto['venda_cliente_final']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="section-title">3. FORMA DE PAGAMENTO</div>
        <div class="field-group">
            <span class="field-label">Tipo de pagamento:</span>
            <span class="field-value"><?php echo sanitize($dados_documento['pagamento_tipo']); ?></span>
        </div>

        <div class="section-title">4. OBSERVAÇÕES</div>
        <div class="observations">
            <div class="field-group">
                <span class="field-label">Observações do Parceiro sobre as Indicações:</span><br>
                <span class="field-value"><?php echo nl2br(sanitize($dados_documento['obs_anotacoes'])); ?></span>
            </div>
            <br>
            <div class="field-group">
                <span class="field-label">Observações do PA sobre as Indicações:</span><br>
                <span class="field-value"><?php echo nl2br(sanitize($dados_documento['obs_pa_indicacoes'])); ?></span>
            </div>
        </div>

        <div class="section-title">5. DECLARAÇÃO</div>
        <div class="declaration">
            Declaro que as informações prestadas neste documento são verdadeiras e concordo com os termos estabelecidos no contrato de parceria com a DEVZGROUP.
        </div>

        <div class="content-grid" style="margin-top: 30px;">
            <div class="field-group">
                <span class="field-label">Local:</span>
                <span class="field-value"><?php echo sanitize($dados_documento['decl_local']); ?></span>
            </div>
            <div class="field-group">
                <span class="field-label">Data:</span>
                <span class="field-value"><?php echo formatarData($dados_documento['decl_data']); ?></span>
            </div>
        </div>

        <div class="signature-area">
            <div>
                <div class="signature-box">
                    <?php echo sanitize($dados_documento['decl_resp_parceiro']); ?><br>
                    <strong>Responsável pelo Parceiro</strong>
                </div>
            </div>
            <div>
                <div class="signature-box">
                    <?php echo sanitize($dados_documento['fetched_decl_resp_pa'] ?? 'DEVZGROUP'); ?><br>
                    <strong>Responsável pela DEVZGROUP</strong>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.getElementById('btn-baixar-pdf').addEventListener('click', function () {
            const element = document.getElementById('documento-para-pdf');
            const nomeDocumento = "<?php echo 'indicacao_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $dados_documento['nome_documento'] ?? 'documento') . '.pdf'; ?>";
            
            const opt = {
                margin:       [0.3, 0.3, 0.3, 0.3],
                filename:     nomeDocumento.replace(/[^a-zA-Z0-9_.-]/g, '_'),
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 1.5,
                    useCORS: true, 
                    letterRendering: true,
                    height: window.innerHeight,
                    width: window.innerWidth
                },
                jsPDF:        { 
                    unit: 'in', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            this.disabled = true;
            this.innerText = 'Gerando PDF...';

            html2pdf().from(element).set(opt).save().then(() => {
                this.disabled = false;
                this.innerText = 'Baixar PDF';
            }).catch((error) => {
                console.error('Erro ao gerar PDF:', error);
                alert('Erro ao gerar PDF. Tente novamente.');
                this.disabled = false;
                this.innerText = 'Baixar PDF';
            });
        });
    </script>
</body>
</html> 