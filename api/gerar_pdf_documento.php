<?php
session_start();
ob_start(); // Start output buffering at the very beginning

// Database connection details (same as gerenciar_documentos_indicacao.php)
define('DB_HOST', 'mysql64-farm2.uni5.net');
define('DB_USER', 'devzgroup');
define('DB_PASS', 'D3vzgr0up');
define('DB_NAME', 'devzgroup');

// Define upload and temp PDF directory paths
// UPLOAD_DIR should resolve to /app/uploads/
define('UPLOAD_DIR', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('TEMP_PDF_DIR', UPLOAD_DIR . 'temp_pdf' . DIRECTORY_SEPARATOR);

// Helper function for database connection
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB Connection Error: " . $conn->connect_error);
        // Do not output JSON here directly if headers might be sent later for file download
        // Instead, throw an exception or return null to be handled by the main logic
        throw new Exception("Erro de conexão com o banco.");
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// --- Main script logic ---
$parceiro_id_sessao = null;
$is_parceiro_logado_para_api = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parceiro') {
    $parceiro_id_sessao = (int)$_SESSION['user_id'];
    $is_parceiro_logado_para_api = true;
}

if (!$is_parceiro_logado_para_api) {
    ob_clean(); // Clean buffer before JSON output
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acesso não autorizado."]);
    exit;
}

$documento_uid = trim(filter_input(INPUT_GET, 'uid', FILTER_SANITIZE_STRING) ?? '');

if (empty($documento_uid)) {
    ob_clean();
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "UID do documento é obrigatório."]);
    exit;
}

$conn = null;
$outputPdfPath = null; // Initialize to ensure it's defined for finally block

try {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT generated_docx_path, nome_documento FROM documentos_indicacao WHERE documento_uid = ? AND parceiro_id = ?");
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    $stmt->bind_param("si", $documento_uid, $parceiro_id_sessao);
    $stmt->execute();
    $result = $stmt->get_result();
    $doc_data = $result->fetch_assoc();
    $stmt->close();

    if (!$doc_data) {
        ob_clean();
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Documento não encontrado ou acesso negado."]);
        $conn->close();
        exit;
    }

    if (empty($doc_data['generated_docx_path'])) {
        ob_clean();
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Arquivo DOCX gerado não encontrado para este documento."]);
        $conn->close();
        exit;
    }

    $fullDocxPath = UPLOAD_DIR . $doc_data['generated_docx_path'];

    if (!file_exists($fullDocxPath)) {
        error_log("DOCX file not found at: " . $fullDocxPath);
        ob_clean();
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Arquivo DOCX físico não encontrado no servidor."]);
        $conn->close();
        exit;
    }

    // Ensure TEMP_PDF_DIR exists and is writable
    if (!is_dir(TEMP_PDF_DIR)) {
        if (!mkdir(TEMP_PDF_DIR, 0775, true)) {
            throw new Exception("Não foi possível criar o diretório temporário para PDFs: " . TEMP_PDF_DIR);
        }
    }
    if (!is_writable(TEMP_PDF_DIR)) {
        throw new Exception("Diretório temporário para PDFs não tem permissão de escrita: " . TEMP_PDF_DIR);
    }
    
    // Check for soffice availability (simple check)
    $soffice_check = shell_exec('command -v soffice');
    if (empty(trim($soffice_check))) {
        error_log("LibreOffice (soffice) não encontrado no sistema.");
        throw new Exception("Ferramenta de conversão (LibreOffice) não disponível no servidor.");
    }


    // DOCX to PDF Conversion using LibreOffice
    $escaped_outdir = escapeshellarg(TEMP_PDF_DIR);
    $escaped_docx_path = escapeshellarg($fullDocxPath);
    // The command will output to TEMP_PDF_DIR. The output filename will be the same as input, but with .pdf
    $command = "soffice --headless --convert-to pdf:writer_pdf_Export --outdir " . $escaped_outdir . " " . $escaped_docx_path;
    
    shell_exec($command . ' 2>&1'); // Capture stderr too, though direct error checking is limited with shell_exec like this

    $outputPdfPath = TEMP_PDF_DIR . pathinfo($fullDocxPath, PATHINFO_FILENAME) . '.pdf';

    if (!file_exists($outputPdfPath)) {
        error_log("Falha na conversão para PDF. Comando: $command. PDF não encontrado em: $outputPdfPath");
        throw new Exception("Erro ao converter documento para PDF. O arquivo de saída não foi gerado.");
    }

    // Serve PDF File
    ob_clean(); // Clean any previous output (like database errors if not caught properly)

    $sane_nome_documento = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $doc_data['nome_documento'] ?? 'documento_indicacao');
    $downloadPdfFilename = "Indicacao_" . $sane_nome_documento . ".pdf";
    if (empty(trim($sane_nome_documento)) || $sane_nome_documento === '_') {
        $downloadPdfFilename = pathinfo($outputPdfPath, PATHINFO_BASENAME);
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $downloadPdfFilename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($outputPdfPath));

    flush(); // Flush system output buffer
    readfile($outputPdfPath);
    
    $conn->close();
    exit;

} catch (Exception $e) {
    error_log("Erro em gerar_pdf_documento.php: " . $e->getMessage());
    if ($conn && $conn->thread_id) { // Check if connection is still open
        $conn->close();
    }
    ob_clean(); // Clean buffer before JSON error output
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(500); // Internal Server Error for exceptions
    echo json_encode(["success" => false, "message" => $e->getMessage()]); // Send specific error message
    exit;
} finally {
    // Cleanup: Delete the temporary PDF file if it exists
    if ($outputPdfPath && file_exists($outputPdfPath)) {
        unlink($outputPdfPath);
    }
    // Ensure output buffer is ended if not already cleaned and exited
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

?>
