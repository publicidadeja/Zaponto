<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$draw = $_POST['draw'];
$start = $_POST['start'];
$length = $_POST['length'];
$search = $_POST['search']['value'];

// Construa sua query com base nos filtros
$query = "SELECT * FROM notificacoes WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (titulo LIKE :search OR mensagem LIKE :search)";
}

// Adicione os filtros adicionais
if (!empty($_POST['data_inicio'])) {
    $query .= " AND data >= :data_inicio";
}

if (!empty($_POST['data_fim'])) {
    $query .= " AND data <= :data_fim";
}

// Execute a query e retorne os resultados no formato esperado pelo DataTables
$response = [
    "draw" => intval($draw),
    "recordsTotal" => $total_records,
    "recordsFiltered" => $filtered_records,
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);