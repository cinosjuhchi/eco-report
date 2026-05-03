<?php
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';

if ($type === 'regencies') {
    $id = $_GET['province_id'] ?? '';
    $url = 'https://wilayah.id/api/regencies/' . rawurlencode($id) . '.json';
} elseif ($type === 'districts') {
    $id = $_GET['regency_id'] ?? '';
    $url = 'https://wilayah.id/api/districts/' . rawurlencode($id) . '.json';
} else {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

if (!preg_match('/^\d+(?:\.\d+)*$/', $id)) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
    ]
]);

$res = @file_get_contents($url, false, $ctx);
if ($res === false) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$decoded = json_decode($res, true);
$data = (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) ? $decoded['data'] : [];

echo json_encode(['ok' => true, 'data' => $data]);
