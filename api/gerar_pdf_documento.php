<?php
session_start();
require_once 'config.php'; // Para conexão com o banco de dados ($conn)

header('Access-Control-Allow-Origin: *'); // Permitir requisições de qualquer origem (ajuste se necessário por segurança)
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Pre-flight request. Responda adequadamente.
    exit;
}

$documento_uid = null;
if (isset($_GET['uid'])) {
    $documento_uid = $_GET['uid'];
}

if (!$documento_uid) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'UID do documento não fornecido.']);
    exit;
}

// Verifique se o usuário tem permissão para acessar este documento, se necessário.
// Por exemplo, verificando a sessão $_SESSION['user_id'] e se ele é o dono ou tem acesso.
// Esta parte da lógica de permissão depende de como seu sistema funciona.
// Por simplicidade, este exemplo não inclui verificação de permissão complexa,
// mas em um sistema real, isso seria crucial.

// IMPORTANTíssimo: Substitua 'documentos_indicacao' pelo nome real da sua tabela
// e 'arquivo_word_blob' pelo nome real da coluna que armazena o conteúdo DOCX.
// Também, 'documento_uid' deve ser o nome da coluna do UID.
$sql = "SELECT arquivo_word_blob, nome_documento FROM documentos_indicacao WHERE documento_uid = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    error_log("Erro no prepare statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao preparar a consulta.']);
    exit;
}

$stmt->bind_param("s", $documento_uid);

if (!$stmt->execute()) {
    http_response_code(500);
    error_log("Erro ao executar statement: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao executar a consulta.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($arquivo_word_blob, $nome_documento);
    $stmt->fetch();

    if ($arquivo_word_blob) {
        $filename = "documento.docx"; // Nome padrão
        if (!empty($nome_documento)) {
            // Limpa o nome do documento para usar como nome de arquivo
            $sane_nome_documento = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nome_documento);
            $filename = $sane_nome_documento . ".docx";
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        // O header Content-Disposition abaixo faria o navegador tentar baixar o arquivo
        // diretamente. Para o fetch() do JavaScript, ele não é estritamente necessário
        // e pode até ser omitido se o JS apenas precisa do blob.
        // header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($arquivo_word_blob)); // Se o blob estiver em uma variável

        echo $arquivo_word_blob;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Arquivo Word não encontrado no banco de dados para este UID.']);
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Documento não encontrado com o UID fornecido.']);
}

$stmt->close();
$conn->close();
exit;
?> 