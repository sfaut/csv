<?php

require_once '../src/Reader.php';

$data = sfaut\Csv\Reader::all('data.csv', ['separator' => ';', 'header' => true]);

print_r($data);