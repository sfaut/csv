<?php

namespace sfaut\Csv;

class Reader implements \Iterator
{
    // Main CSV file properties
    public string $separator;
    public string $enclosure;
    public string $escape;

    // Transcoding will be done if source and target encodings are different
    public string $fromEncoding;
    public string $toEncoding;

    public string $file; // File path/name
    protected $stream = null; // File stream resource
    protected string $bom; // BOM type detected

    protected int $index; // record index, first record (maybe header) is 0

    public bool $header; // Is first record an header ?
    protected array $columns = []; // Header columns names
    protected int $columnsCount = 0; // Header columns count, used to determine entries validity

    // Processing
    public $map;
    public $filter;
    public $filtered; // Compteur de lignes filtrées/rejetées

    protected int $startingByte; // Data starting byte in file (for rewind after header)
    protected ?object $record = null; // Current CSV row, null is file ended

    protected function __construct(array $parameters)
    {
        $this->separator = $parameters['separator'] ?? ',';
        $this->enclosure = $parameters['enclosure'] ?? '"';
        $this->escape = $parameters['escape'] ?? '';
        $this->fromEncoding = $parameters['fromEncoding'] ?? 'UTF-8';
        $this->toEncoding = $parameters['toEncoding'] ?? 'UTF-8';
        $this->index = 0;
        $this->header = $parameters['header'] ?? true;

        // Callbacks
        $this->map = $parameters['map'] ?? null;
        $this->filter = $parameters['filter'] ?? null;

        if ($this->fromEncoding !== $this->toEncoding) {
            if (!extension_loaded('mbstring')) {
                throw new \Exception('Many encodings are used, PHP extension mbstring required');
            }
            $encodings_supported = mb_list_encodings();
            if (!in_array($this->fromEncoding, $encodings_supported)) {
                throw new \Exception("Input encoding {$this->fromEncoding} is not supported");
            }
            if (!in_array($this->toEncoding, $encodings_supported)) {
                throw new \Exception("Output encoding {$this->toEncoding} is not supported");
            }
        }
    }

    public static function open(string $file, array $parameters = []): self
    {
        $csv = new self($parameters);

        $csv->file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);

        if (!file_exists($csv->file)) {
            throw new \Exception("File {$csv->file} does not exists");
        }

        if (!is_readable($csv->file)) {
            throw new \Exception("File {$csv->file} is not readable");
        }

        $csv->stream = @fopen($csv->file, 'r');

        if ($csv->stream === false) {
            throw new \Exception("File {$csv->file} opening failed");
        }

        if (@flock($csv->stream, LOCK_SH) === false) {
            throw new \Exception("File {$csv->file} locking failed");
        }

        $chunk = fread($csv->stream, 3);
        if ($chunk !== "\xEF\xBB\xBF") { // BOM UTF-8 ?
            rewind($csv->stream);
        }

        if ($csv->header === false) {
            // No header
        } else {
            // TODO: secure this
            $header = fgetcsv($csv->stream, 0, $csv->separator, $csv->enclosure, $csv->escape);
            $header = array_map([$csv, 'transcode'], $header);
            $csv->columns = $header;
            $csv->columnsCount = count($csv->columns);
        }

        $csv->startingByte = ftell($csv->stream);

        return $csv;
    }

    protected function transcode(string $value): string
    {
        if ($this->fromEncoding !== $this->toEncoding) {
            return mb_convert_encoding($value, $this->fromEncoding, $this->toEncoding);
        } else {
            return $value;
        }
    }

    public static function all(string $file, array $parameters = []): array
    {
        $csv = self::open($file, $parameters);
        $result = [];
        foreach ($csv as $record) {
            $result[] = $record;
        }
        return $result;
    }

    public function read(): ?object
    {
        $record = @fgetcsv($this->stream, 0, $this->separator, $this->enclosure, $this->escape);

        if ($record === false) { // File end ?
            return null;
        }

        // record index handling
        $this->index++;

        // Encodes handlings
        $record = array_map([$this, 'transcode'], $record);

        // Header handling
        if ($this->header !== false) {
            $valuesCount = count($record);
            if ($valuesCount !== $this->columnsCount) {
                throw new \Exception("Header values count {$this->columnsCount} and record values count {$valuesCount} at row number {$this->index} are not equal");
            }
            $record = (object)array_combine($this->columns, $record);
        }

        // Map handling
        if (is_callable($this->map)) {
            $record = ($this->map)($record, $this->index);
        }

        // Filter handling
        if (is_callable($this->filter)) {
            if (($this->filter)($record, $this->index) === false) {
                $this->filtered++;
                return $this->read();
            }
        }

        return $record;
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////// INTERFACE ITERATOR IMPLEMENTATION //////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function rewind()
    {
        fseek($this->stream, $this->startingByte, SEEK_SET);
        $this->index = 0;
        $this->filtered = 0;
        $this->record = $this->read();
    }

    public function valid()
    {
        return ($this->record !== null);
    }

    public function current()
    {
        return $this->record;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        if ($this->record !== null) {
            $this->record = $this->read();
        }
    }
}