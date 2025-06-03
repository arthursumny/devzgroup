<?php
// Este arquivo é apenas para testes, para verificar se o PHPWord está funcionando corretamente

// Carrega o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Verifica se a classe PhpWord existe
if (class_exists('\\PhpOffice\\PhpWord\\PhpWord')) {
    echo "PHPWord está instalado corretamente!<br>";
    
    // Verifica o renderizador de PDF
    try {
        \PhpOffice\PhpWord\Settings::setPdfRendererPath(__DIR__ . '/../vendor/dompdf/dompdf');
        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        echo "Configuração do renderizador DomPDF bem-sucedida!<br>";
        
        // Cria um documento de teste
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Teste de PhpWord para PDF');
        
        // Diretório temporário
        $tempDir = sys_get_temp_dir();
        $docxPath = $tempDir . '/teste_phpword.docx';
        $pdfPath = $tempDir . '/teste_phpword.pdf';
        
        // Salva como DOCX
        $docxWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $docxWriter->save($docxPath);
        echo "Arquivo DOCX de teste gerado em: $docxPath<br>";
        
        // Tenta converter para PDF
        $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($pdfPath);
        
        if (file_exists($pdfPath)) {
            echo "Arquivo PDF de teste gerado com sucesso em: $pdfPath<br>";
            echo "Conversão de DOCX para PDF está funcionando!<br>";
        } else {
            echo "Falha ao gerar o arquivo PDF.<br>";
        }
        
    } catch (Exception $e) {
        echo "Erro na configuração do renderizador de PDF: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Erro: PHPWord não está instalado corretamente!<br>";
    
    // Exibe as classes disponíveis
    echo "Classes disponíveis no namespace PhpOffice:<br>";
    $classes = get_declared_classes();
    foreach ($classes as $class) {
        if (strpos($class, 'PhpOffice') !== false) {
            echo "- $class<br>";
        }
    }
}

// Verifica a presença do DomPDF
if (class_exists('\\Dompdf\\Dompdf')) {
    echo "DomPDF está instalado corretamente!<br>";
} else {
    echo "Erro: DomPDF não está instalado corretamente!<br>";
}

// Exibe o diretório vendor para verificação
echo "Diretório vendor: " . __DIR__ . '/../vendor/<br>';
echo "Arquivos no diretório vendor/phpoffice:<br>";
$phpofficeDir = __DIR__ . '/../vendor/phpoffice';
if (is_dir($phpofficeDir)) {
    $files = scandir($phpofficeDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "Diretório phpoffice não encontrado!<br>";
}
?> 