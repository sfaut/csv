<?php

namespace sfaut\Csv;

class Writer
{
    public static function all(string $file, array $records, array $parameters = [])
    {
        $separator = $parameters['separator'] ?? ',';
        $enclosure = $parameters['enclosure'] ?? '"';
        $escape = $parameters['escape'] ?? '';

        $stream = fopen($file, 'w');

        foreach ($records as $record) {
            fputcsv($stream, $record, $separator, $enclosure, $escape);
        }
    }
}