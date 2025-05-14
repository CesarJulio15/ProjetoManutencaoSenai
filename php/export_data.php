<?php
session_start();
// Segurança: apenas coordenador pode exportar
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit;
}
require __DIR__ . '/../../../phpspreadsheet/vendor/autoload.php';
require_once 'conexao.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Query com JOINs para obter nomes de responsável e executante
$sql = "
    SELECT 
        m.numero_os, m.titulo, m.descricao, m.localizacao_ambiente,
        d1.nome AS responsavel, d2.nome AS executante,
        m.status, m.data_abertura, m.data_encerramento,
        m.prioridade, m.bloco, m.tipo_manutencao, m.origem
    FROM manutencao m
    JOIN docentes d1 ON m.responsavel = d1.id
    LEFT JOIN docentes d2 ON m.executante = d2.id
    ORDER BY m.data_abertura DESC
";

$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Cabeçalhos
$headers = [
    'A1' => 'Número OS',
    'B1' => 'Título',
    'C1' => 'Descrição',
    'D1' => 'Localização / Ambiente',
    'E1' => 'Responsável',
    'F1' => 'Executante',
    'G1' => 'Status',
    'H1' => 'Data Abertura',
    'I1' => 'Data Encerramento',
    'J1' => 'Prioridade',
    'K1' => 'Bloco',
    'L1' => 'Tipo Manutenção',
    'M1' => 'Origem',
];

foreach ($headers as $cell => $label) {
    $sheet->setCellValue($cell, $label);
}

// Preencher os dados
$rowIndex = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowIndex, $row['numero_os']);
    $sheet->setCellValue('B' . $rowIndex, $row['titulo']);
    $sheet->setCellValue('C' . $rowIndex, $row['descricao']);
    $sheet->setCellValue('D' . $rowIndex, $row['localizacao_ambiente']);
    $sheet->setCellValue('E' . $rowIndex, $row['responsavel']);
    $sheet->setCellValue('F' . $rowIndex, $row['executante']);
    $sheet->setCellValue('G' . $rowIndex, $row['status']);
    $sheet->setCellValue('H' . $rowIndex, $row['data_abertura']);
    $sheet->setCellValue('I' . $rowIndex, $row['data_encerramento']);
    $sheet->setCellValue('J' . $rowIndex, $row['prioridade']);
    $sheet->setCellValue('K' . $rowIndex, $row['bloco']);
    $sheet->setCellValue('L' . $rowIndex, $row['tipo_manutencao']);
    $sheet->setCellValue('M' . $rowIndex, $row['origem']);
    $rowIndex++;
}

// Baixar como arquivo Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ordens_servico_exportadas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
