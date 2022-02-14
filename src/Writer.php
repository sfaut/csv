<?php

namespace sfaut\Csv;

class Writer
{
    public string $file; // File path/name
    public $stream = null; // File stream resource

    public string $bom; // BOM name or value if name unknow
    public bool $header; // Writes properties of first record as CSV header ?
    public array $columns = []; // Header columns' names

    // CSV file properties
    public string $separator;
    public string $enclosure;
    public string $escape;

    protected function __construct(array $parameters)
    {
        $this->bom = $parameters['bom'] ?? '';
        $this->header = $parameters['header'] ?? true;
        $this->separator = $parameters['separator'] ?? ',';
        $this->enclosure = $parameters['enclosure'] ?? '"';
        $this->escape = $parameters['escape'] ?? '';
    }

    protected static function open(string $file, array $parameters, string $mode): self
    {
        $csv = new self($parameters);
        $csv->file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        $csv->stream = fopen($file, $mode);
        return $csv;
    }

    public function write($record)
    {
        fputcsv($this->stream, (array)$record, $this->separator, $this->enclosure, $this->escape);
    }

    public static function create(string $file, array $records, array $parameters = [])
    {
        $csv = self::open($file, $parameters, 'w');
        if ($csv->bom !== '') {
            $buffer = $csv->bom === 'UTF-8' ? "\xEF\xBB\xBF" : $csv->bom;
            fwrite($csv->stream, $buffer);
        }
        if ($csv->header && !empty($records)) {
            $csv->columns = array_keys((array)$records[0]);
            $csv->write($csv->columns);
        }
        foreach ($records as $record) {
            $csv->write($record);
        }
    }
}