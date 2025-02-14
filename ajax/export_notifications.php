<?php
require_once '../../includes/db.php';
require_once '../../includes/admin-auth.php';
require_once '../../includes/functions.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;

// Receber os filtros da URL
$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// Buscar dados filtrados
$notificacoes = buscarNotificacoesComFiltros($pdo, $filtros);

// Função para exportar Excel
function exportarExcel($dados) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Cabeçalhos
    $sheet->setCellValue('A1', 'Data');
    $sheet->setCellValue('B1', 'Tipo');
    $sheet->setCellValue('C1', 'Título');
    $sheet->setCellValue('D1', 'Mensagem');
    $sheet->setCellValue('E1', 'Total Usuários');
    $sheet->setCellValue('F1', 'Taxa de Leitura');
    
    // Estilo dos cabeçalhos
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    
    // Dados
    $row = 2;
    foreach ($dados as $item) {
        $sheet->setCellValue('A'.$row, date('d/m/Y H:i', strtotime($item['data_criacao'])));
        $sheet->setCellValue('B'.$row, ucfirst($item['tipo']));
        $sheet->setCellValue('C'.$row, $item['titulo']);
        $sheet->setCellValue('D'.$row, $item['mensagem']);
        $sheet->setCellValue('E'.$row, $item['total_usuarios']);
        $sheet->setCellValue('F'.$row, number_format($item['taxa_leitura'], 2).'%');
        $row++;
    }
    
    // Ajustar largura das colunas
    foreach(range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Configurar cabeçalho HTTP
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="notificacoes_'.date('Y-m-d').'.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Salvar arquivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Função para exportar PDF
function exportarPDF($dados) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Configurar conteúdo igual ao Excel
    $sheet->setCellValue('A1', 'Data');
    $sheet->setCellValue('B1', 'Tipo');
    $sheet->setCellValue('C1', 'Título');
    $sheet->setCellValue('D1', 'Mensagem');
    $sheet->setCellValue('E1', 'Total Usuários');
    $sheet->setCellValue('F1', 'Taxa de Leitura');
    
    $row = 2;
    foreach ($dados as $item) {
        $sheet->setCellValue('A'.$row, date('d/m/Y H:i', strtotime($item['data_criacao'])));
        $sheet->setCellValue('B'.$row, ucfirst($item['tipo']));
        $sheet->setCellValue('C'.$row, $item['titulo']);
        $sheet->setCellValue('D'.$row, $item['mensagem']);
        $sheet->setCellValue('E'.$row, $item['total_usuarios']);
        $sheet->setCellValue('F'.$row, number_format($item['taxa_leitura'], 2).'%');
        $row++;
    }
    
    // Configurar cabeçalho HTTP
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="notificacoes_'.date('Y-m-d').'.pdf"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Mpdf');
    $writer->save('php://output');
    exit;
}

// Determinar formato e exportar
$formato = $_GET['formato'] ?? 'excel';

try {
    // Registrar a exportação
    registrarExportacao($pdo, $_SESSION['admin_id'], 'notificacoes', $formato, $filtros);
    
    // Executar exportação
    if ($formato === 'pdf') {
        exportarPDF($notificacoes);
    } else {
        exportarExcel($notificacoes);
    }
} catch (Exception $e) {
    die('Erro na exportação: ' . $e->getMessage());
}