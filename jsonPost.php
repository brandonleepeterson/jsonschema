<?php

require('jsonClasses.php');

// Form Data.
$data = [];
parse_str($_POST['form'], $data);

$specData = $_POST['specData'];
header('Content-Type: application/json');

try {
    $jsonSpec = new orecrush\json\JsonSpec($specData);

    $message = "Valid!";
    $invalidParams = [];
    if (!$jsonSpec->validate($data)) {
        $message = "Invalid!";
        $invalidParams = $jsonSpec->invalidParams;
    }

    echo json_encode([
        'message'       => $message,
        'invalidParams' => $invalidParams,
        'error'         => 0,
    ]);
} catch (jsonSpecException $e) {
    echo json_encode([
        'message'   => $e->getMessage(),
        'error'     => 1,
    ]);
}
