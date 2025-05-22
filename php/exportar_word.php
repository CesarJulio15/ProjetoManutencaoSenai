<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/conexao.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

if (!isset($_GET['numero_os'])) {
    die("Número da OS não informado.");
}

$numero_os = $_GET['numero_os'];

// Consulta igual a do detalhe.php
$stmt = $conn->prepare("
    SELECT m.*, 
           d1.nome AS nome_responsavel, 
           d2.nome AS nome_executante 
    FROM manutencao m
    JOIN docentes d1 ON m.responsavel = d1.id 
    LEFT JOIN docentes d2 ON m.executante = d2.id 
    WHERE m.numero_os = ?
");
$stmt->bind_param("s", $numero_os);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("OS não encontrada.");
}

$os = $result->fetch_assoc();

// Cria documento Word
$phpWord = new PhpWord();
$section = $phpWord->addSection();

// CABEÇALHO COM LOGO E TABELA
$header = $section->addHeader();

// 1. LOGOMARCA CENTRALIZADA — metade do tamanho anterior
$logoPath = __DIR__ . '/../img/senai-logo.png';
if (file_exists($logoPath)) {
    $header->addImage($logoPath, [
        'width' => 60, // metade do tamanho anterior
        'height' => 18,
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
    ]);
}

// 2. ESTILO da TABELA: borda geral, mas sem borda inferior nas células do cabeçalho
$phpWord->addTableStyle('TabelaCabecalho', [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 80,
    'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
]);

$table = $header->addTable('TabelaCabecalho');

$boldCenter = ['bold' => true];
$centerAlign = ['alignment' => 'center'];

// LINHA 1 — títulos sem borda inferior (estilo inline)
$table->addRow();
$table->addCell(3500)->addText('EMISSOR', $boldCenter, $centerAlign);
$table->addCell(1500)->addText('CÓDIGO', $boldCenter, $centerAlign);
$table->addCell(2000)->addText('PÁGINA', $boldCenter, $centerAlign);

// LINHA 2 — conteúdo real
$table->addRow();
$table->addCell(3500)->addText('Escola Senai "Elias Miguel Haddad"' . "\nJacareí - SP", [], $centerAlign);
$table->addCell(1500)->addText('Minuta', [], $centerAlign);
$table->addCell(2000)->addPreserveText('Página {PAGE} de {NUMPAGES}', [], $centerAlign);

// LINHA 3 — “TÍTULO” mesclado, sem borda inferior
$table->addRow();
$table->addCell(7000, ['gridSpan' => 3, 'borderBottomSize' => 0])->addText('TÍTULO', $boldCenter, $centerAlign);

// LINHA 4 — título do plano de ensino mesclado
$table->addRow();
$table->addCell(7000, ['gridSpan' => 3])->addText(
    'Emissão de Ordem de serviço',
    [],
    $centerAlign
);
// === DADOS PRINCIPAIS ===
$section->addTextBreak(2);
$section->addTitle("Detalhes da Ordem de Serviço", 1);

$dados = [
    'Número da OS:' => $os['numero_os'],
    'Título:' => $os['titulo']?? '—',
    'Local:' => $os['localizacao_ambiente'] ?? '—',
    'Bloco:' => $os['bloco'],
    'Descrição:' => $os['descricao']?? '—',
    'Status:' => $os['status'],
    'Responsável:' => $os['nome_responsavel'],
    'Executante:' => $os['nome_executante'] ?? 'Não designado',
    'Data de Abertura:' => date('d/m/Y', strtotime($os['data_abertura'])),
    'Data de Conclusão:' => !empty($os['data_conclusao']) ? date('d/m/Y', strtotime($os['data_conclusao'])) : '—',
];

foreach ($dados as $titulo => $valor) {
    $section->addText("{$titulo} {$valor}");
}

// === IMAGEM DA OS (se houver) ===
if (!empty($os['foto'])) {
    $section->addPageBreak();
    // Detecta tipo MIME real da imagem
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($os['foto']);

    // Define extensão com base no tipo
    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        default => null
    };

    if ($ext !== null) {
        $tempImagePath = __DIR__ . "/../temp/foto_os_" . $os['numero_os'] . "." . $ext;
        file_put_contents($tempImagePath, $os['foto']);

        // Verifica se é uma imagem válida
        if (getimagesize($tempImagePath) !== false) {
            //$section->addTextBreak(1);
            $section->addTitle("Imagem da Ordem de Serviço", 2);
            $section->addImage($tempImagePath, [
                'width' => 400,
                'alignment' => 'center',
            ]);
        } else {
            $section->addText("A imagem da OS é inválida ou está corrompida.");
        }
    } else {
        $section->addText("Formato de imagem não suportado.");
    }
}
// === QR CODE ===
$urlOS = 'https://devgomes.com/projetos/SENAImanutencao/php/detalhe.php?numero_os=' . urlencode($os['numero_os']);
$qrcodePath = __DIR__ . '/../temp/qrcode_' . $os['numero_os'] . '.png';

$options = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG]);
$qr = new QRCode($options);
$qr->render($urlOS, $qrcodePath);

if (file_exists($qrcodePath) && getimagesize($qrcodePath) !== false) {
    $section->addPageBreak();
    $section->addTitle("Acesse Digitalmente", 2);
    $section->addText("Escaneie o QR Code abaixo para visualizar esta ordem de serviço online.", [], ['alignment' => 'center']);
    $section->addImage($qrcodePath, [
        'width' => 120,
        'height' => 120,
        'alignment' => 'center',
    ]);
}
// === EXPORTA O DOCUMENTO ===
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=ordem_servico_" . $os['numero_os'] . ".docx");
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save("php://output");

// Limpa o QR gerado
if (file_exists($qrcodePath)) {
    unlink($qrcodePath);
}
// Limpar imagem temporária, se existir
if (isset($tempImagePath) && file_exists($tempImagePath)) {
    unlink($tempImagePath);
}

exit;
