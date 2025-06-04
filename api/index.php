<?php
// API de teste para o Sistema PROEIS
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'API do Sistema PROEIS está funcionando!',
    'timestamp' => date('Y-m-d H:i:s')
]);
