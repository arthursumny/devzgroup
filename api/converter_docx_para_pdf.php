<?php
// Carrega o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

// Configurações
$tempDir = sys_get_temp_dir() . '/docx2pdf_' . uniqid();
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Função para limpar arquivos temporários
function limparArquivosTemporarios($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (is_dir("$dir/$file")) {
                    limparArquivosTemporarios("$dir/$file");
                } else {
                    unlink("$dir/$file");
                }
            }
        }
        rmdir($dir);
    }
}

// Verifica se o arquivo foi enviado
if (!isset($_FILES['docxFile']) || $_FILES['docxFile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum arquivo DOCX válido foi enviado.'
    ]);
    exit;
}

// Obtém o nome do arquivo de saída
$outputFileName = isset($_POST['outputFileName']) ? $_POST['outputFileName'] : 'documento.pdf';

// Salva o arquivo DOCX temporariamente
$docxPath = $tempDir . '/' . basename($_FILES['docxFile']['name']);
$pdfPath = $tempDir . '/' . basename($outputFileName);

if (!move_uploaded_file($_FILES['docxFile']['tmp_name'], $docxPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar o arquivo temporário.'
    ]);
    exit;
}

// Registra o erro antes de iniciar a conversão
error_log('Iniciando conversão de DOCX para PDF: ' . $docxPath . ' -> ' . $pdfPath);

try {
    // Carregar o documento DOCX com PHPWord
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
    
    // Configurar o DomPDF como renderizador
    \PhpOffice\PhpWord\Settings::setPdfRendererPath(__DIR__ . '/../vendor/dompdf/dompdf');
    \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
    
    // Criar o escritor de PDF
    $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
    
    // Salvar o arquivo PDF
    $pdfWriter->save($pdfPath);
    
    if (!file_exists($pdfPath)) {
        throw new \Exception('O arquivo PDF não foi gerado.');
    }
    
    // Envia o PDF gerado como resposta
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($outputFileName) . '"');
    header('Content-Length: ' . filesize($pdfPath));
    readfile($pdfPath);
    
} catch (\Exception $e) {
    // Registra o erro
    error_log('Erro na conversão: ' . $e->getMessage());
    
    // Retorna erro como JSON
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao converter o documento: ' . $e->getMessage()
    ]);
} finally {
    // Limpa os arquivos temporários
    limparArquivosTemporarios($tempDir);
}
?> 