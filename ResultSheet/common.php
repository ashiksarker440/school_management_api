<?php
function result_sheet_respond($status, $data, $message, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function result_sheet_id($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id !== false && $id > 0 ? $id : null;
}
?>
