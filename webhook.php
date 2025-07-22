<?php
include("conexao.php");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

if ($id && $status) {
    if ($status === 'cancelado') {
        $conn->query("DELETE FROM pedidos WHERE id = $id");
    } else {
        $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
}
