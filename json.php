<?php

require('jsonClasses.php');

$specData = $_POST['specData'];
header('Content-Type: application/json');

try {
    $jsonSpec = new orecrush\json\JsonSpec($specData);
    $form = $jsonSpec->toForm();

    echo json_encode([
        'html'      => $form,
        'message'   => 'OK',
        'error'     => 0,
    ]);
} catch (jsonSpecException $e) {
    echo json_encode([
        'message'   => $e->getMessage(),
        'error'     => 1,
    ]);
}