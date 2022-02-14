<?php

require_once '../src/Writer.php';

$data = [
    ['id' => 123, 'name' => 'Jean Dupont', 'email' => 'jdupont@orange.fr'],
    ['id' => 234, 'name' => 'Mélanie Lefèbvre', 'email' => 'mdurand@free.fr'],
];

sfaut\Csv\Writer::create('test-2.csv', $data, ['bom' => 'UTF-8', 'separator' => ';']);
