<?php

namespace sfaut\Csv;

class Reader implements \Iterator
{
    const BOM = [
        'UTF8' => "\xEF\xBB\xBF",
        'UTF16_BE' => "\xFE\xFF",
        'UTF16_LE' => "\xFF\xFE",
        'UTF32_BE' => "\x00\x00\xFE\xFF",
        'UTF32_LE' => "\xFE\xFF\x00\x00",
    ];

    public string $delimiter;
    public string $enclosure;
    public string $escape;

    public string $fromEncoding;
    public string $toEncoding;

    public string $file; // File path/name
    protected $handler = null; // File resource
    protected string $bom; // BOM type detected

    protected int $index; // Entry index, first entry (maybe header) is 0

    public bool $header; // Is first entry an header ?
    protected array $fields = []; // Header fields names
    protected int $fieldsCount = 0; // Header fields count, used to determine entries validity

    // Processing
    public $map;
    public $filter;
    public $filtered; // Compteur de lignes filtrées/rejetées

    protected int $start; // Data starting byte in file (for rewind after header)
    protected ?array $entry = null; // Current CSV row, null is file ended

    public function __construct(string $file, array $parameters = [])
    {
        $this->delimiter = $parameters['delimiter'] ?? ',';
        $this->enclosure = $parameters['enclosure'] ?? '"';
        $this->escape = $parameters['escape'] ?? "\x00";
        $this->fromEncoding = $parameters['fromEncoding'] ?? 'UTF-8';
        $this->toEncoding = $parameters['toEncoding'] ?? 'UTF-8';
        $this->file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        $this->index = 0;
        $this->header = $parameters['header'] ?? true;
        $this->map = $parameters['map'] ?? null;
        $this->filter = $parameters['filter'] ?? null;

        if ($this->fromEncoding !== $this->toEncoding) {
            if (!extension_loaded('mbstring')) {
                throw new \Exception("Many encodings are used, PHP extension mbstring required");
            }
            $encodings_supported = mb_list_encodings();
            $encodings_supported_list = implode(', ', $encodings_supported);
            if (!in_array($this->fromEncoding, $encodings_supported)) {
                throw new \Exception("Declared input encoding \"$this->fromEncoding\" is not supported, supported one are $encodings_supported_list");
            }
            if (!in_array($this->toEncoding, $encodings_supported)) {
                throw new \Exception("Declared output encoding \"$this->toEncoding\" is not supported");
            }
        }

        if (!file_exists($this->file)) {
            throw new \Exception("File $this->file does not exists");
        }

        if (!is_readable($this->file)) {
            throw new \Exception("File $this->file is not readable");
        }

        $this->handler = @fopen($this->file, 'r');

        if ($this->handler === false) {
            throw new \Exception("File $this->file opening failed");
        }

        if (@flock($this->handler, LOCK_SH) === false) {
            throw new \Exception("File $this->file locking failed");
        }

        if ($this->header === false) {
            // No header
        } else {
            // TODO: secure this
            $header = fgetcsv($this->handler, 0, $this->delimiter, $this->enclosure, $this->escape);
            $header = array_map([$this, 'convert'], $header);
            $this->fields = $header;
            $this->fieldsCount = count($this->fields);
        }

        $this->start = ftell($this->handler);
    }

    protected function convert(string $value): string
    {
        if ($this->fromEncoding !== $this->toEncoding) {
            return mb_convert_encoding($value, $this->fromEncoding, $this->toEncoding);
        } else {
            return $value;
        }
    }

    public function readAll()
    {
        $result = [];
        foreach ($this as $entry) {
            $result[] = $entry;
        }
        return $result;
    }

    public function read(): ?array
    {
        $entry = @fgetcsv($this->handler, 0, $this->delimiter, $this->enclosure, $this->escape);

        if ($entry === false) { // File end ?
            return null;
        }

        // Entry index handling
        $this->index++;

        // Encodes handlings
        $entry = array_map([$this, 'convert'], $entry);

        // Header handling
        if ($this->header !== false) {
            $valuesCount = count($entry);
            if ($valuesCount !== $this->fieldsCount) {
                throw new \Exception("Header values count $this->fieldsCount and entry values count $valuesCount at row number $this->index are not equal");
            }
            $entry = array_combine($this->fields, $entry);
        }

        // Map handling
        if (is_callable($this->map)) {
            $entry = ($this->map)($entry, $this->index);
        }

        // Filter handling
        if (is_callable($this->filter)) {
            if (($this->filter)($entry, $this->index) === false) {
                $this->filtered++;
                return $this->read();
            }
        }

        return $entry;
    }

    public function __destruct()
    {
        if (is_resource($this->handler)) {
            fclose($this->handler);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////// INTERFACE ITERATOR IMPLEMENTATION //////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function rewind()
    {
        fseek($this->handler, $this->start, SEEK_SET);
        $this->index = 0;
        $this->filtered = 0;
        $this->entry = $this->read();
    }

    public function valid()
    {
        return $this->entry !== null;
    }

    public function current()
    {
        return $this->entry;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        if ($this->entry !== null) {
            $this->entry = $this->read();
        }
    }
}
