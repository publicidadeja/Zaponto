<?php
require_once '../../includes/db.php';
require_once '../../includes/admin-auth.php';
require_once '../../vendor/autoload.php'; // Para PhpSpreadsheet

function exportarParaExcel($dados) {
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Cabeçalhos
    $sheet->setCellValue('A1', 'Data');
    $sheet->setCellValue('B1', 'Tipo');
    $sheet->setCellValue('C1', 'Título');
    $sheet->setCellValue('D1', 'Mensagem');
    $sheet->setCellValue('E1', 'Total Usuários');
    $sheet->setCellValue('F1', 'Taxa de Leitura');
    
    // Dados
    $row = 2;
    foreach ($dados as $item) {
        $sheet->setCellValue('A'.$row, $item['data_criacao']);
        $sheet->setCellValue('B'.$row, $item['tipo']);
        $sheet->setCellValue('C'.$row, $item['titulo']);
        $sheet->setCellValue('D'.$row, $item['mensagem']);
        $sheet->setCellValue('E'.$row, $item['total_usuarios']);
        $sheet->setCellValue('F'.$row, $item['taxa_leitura'].'%');
        $row++;
    }
    
    // Configurar cabeçalho HTTP
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="notificacoes.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
}