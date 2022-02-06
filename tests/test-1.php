<?php

require_once '../src/Reader.php';

$data = sfaut\Csv\Reader::readAll('data.csv', ['separator' => ';', 'header' => true]);

print_r($data);