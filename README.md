# Csv\Reader

Makes CSV files easily usable.

## Philosophy

- KISS – Fire & Forget

## Installation

### Composer

In your terminal, with [Composer](https://getcomposer.org/) and in your project root, execute :

```
> composer require sfaut/csv
```

## Example -- Read all in a row

Data source `/path/to/countries.csv` :

```
country,capital,continent
Japon,Tokyo,Asie
Hongrie,Budapest,Europe
Brésil,Brasilia,Amérique
```

```php
<?php

use sfaut\Csv;

require_once '/path/to/vendor/autoload.php';

$csv_file = '/path/to/countries.csv';
$csv = Csv\Reader::open($csv_file);
print_r($csv->readAll());
```

> To shorten following examples, `use`, Composer autloader and `$csv_file` initialization will be snipped.

Renders something like :

```
Array (
    [0] => stdClass Object (
        [0] => country
        [1] => capital
        [2] => continent
    )
    [1] => stdClass Object (
        [0] => Japon
        [1] => Tokyo
        [2] => Asie
    )
    [2] => stdClass Object (
        [0] => Hongrie
        [1] => Budapest
        [2] => Europe
    )
    [3] => stdClass Object (
        [0] => Brésil
        [1] => Brasilia
        [2] => Amérique
    )
)
```

## Example -- Use header

The first CSV file entry can be used to automatically name records' fields.
The header parameter must be explicitely flaged `true` in order to avoid data loss.

```php
$csv = Csv\Reader::open($csv_file, ['header' => true]);
print_r($csv->readAll());
```

Renders something like :

```
Array (
    [0] => stdClass Object (
        [country] => Japon
        [capital] => Tokyo
        [continent] => Asie
    )
    [1] => stdClass Object (
        [country] => Hongrie
        [capital] => Budapest
        [continent] => Europe
    )
    [2] => stdClass Object (
        [country] => Brésil
        [capital] => Brasilia
        [continent] => Amérique
    )
)
```

## Example -- Iterate

`Csv\Reader` implements `Iterator` interface, so we can iterate with a `foreach()` loop:

```php
$csv = Csv\Reader::open($csv_file, ['header' => true]);

foreach ($csv as $record) {
    print_r($record);
}
```

## Example -- Mapping

Each CSV record can be modified while reading. Fields can be added, removed or reordered; values can be casted or aupdated.

```php
const populations = [
    'Brésil' => 210_000_000,
    'Japon' => 127_000_000,
];

// Merges continent to country
// Adds a field population according to populations constant
// Removes capital
$csv = Csv\Reader::open($csv_file, [
    'map' => fn ($record) => (object)[
        'country' => $record->country . ' // ' . $record->continent,
        'population' => populations[$record->country] ?? null,
    ],
]);

print_r($csv->readAll());
```

Renders something like :

```
Array (
    [0] => stdClass Object (
        [country] => Japon // Asie
        [population] => 127000000
    )
    [1] => stdClass Object (
        [country] => Hongrie // Europe
        [population] =>
    )
    [2] => stdClass Object (
        [country] => Brésil // Amérique
        [population] => 210000000
    )
)
```

## Example -- Filtering

Each CSV record can be filtered while reading. Filter is applied after mapper.

We want to keep only European countries:

```php
$csv = Csv\Reader::open($csv_file, [
    'filter' => fn ($entry) => ($record->continent === 'Europe'),
]);

print_r($csv->readAll());
```

Renders something like :

```
Array (
    [0] => stdClass Object (
        [country] => Hongrie
        [capital] => Budapest
        [continent] => Europe
    )
)
```

## Properties

Each of these `Csv\Reader` properties must be initialized with `$parameters` array passed to `Csv\Reader::open($file, $parameters)`.
You should not access them in write in other way.

|Property         |Type        |Description                                                                                                                        |
|-----------------|------------|-----------------------------------------------------------------------------------------------------------------------------------|
|`separator`      |string(1)   |Character separating each field, `,` by default.                                                                                   |
|`enclosure`      |string(1)   |Character enclosing each field, `"` by default.                                                                                    |
|`escape`         |string(1)   |Character escaping enclosure character, `''` (empty string) by default, in the case enclosure is escaped by doubling.              |
|`fromEncoding`   |string      |Encoding of input file, must be one of `mb_list_encodings()`, eg. `Windows-1252`, useful if different of `toEncoding property`.    |
|`toEncoding`     |string      |Encoding of output, must be one of `mb_list_encodings()`, eg. `UTF-8`, useful if different of `fromEncoding`.                      |
|`header`         |boolean     |Flag indicating if first row is used as field name.                                                                                |
|`map`            |callback    |Signature `fn ($record[, $index])`, returns the record mapped.                                                                       |
|`filter`         |callback    |Signature `fn ($record[, $index])`, excludes the record of the result if the callback returns `false`, includes it if returns `true`.|
