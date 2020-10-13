# csv-reader

Makes CSV files easily usable.

## Philosophy

- KISS – Fire & Forget

## Installation

### Composer

In your terminal, with [Composer](https://getcomposer.org/), execute :

```
> composer install
> composer require sfaut/csv-reader:*
```

### Raw installation

1. Download the [latest source code release](https://github.com/sfaut/csv-reader/releases/latest)
2. Include `/csv-reader/Reader.php` in your PHP script

## Example -- Basics

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

require_once __DIR__ . '/vendor/autoload.php';
// or
// require_once __DIR__ . '/path/to/csv-reader/Reader.php';

$csv_file = __DIR__ . '/path/to/countries.csv';

$csv = new Csv\Reader($csv_file);

print_r($csv->readAll());
```

Renders something like :

```
Array (
    [0] => Array (
        [country] => Japon
        [capital] => Tokyo
        [continent] => Asie
    )
    [1] => Array (
        [country] => Hongrie
        [capital] => Budapest
        [continent] => Europe
    )
    [2] => Array (
        [country] => Brésil
        [capital] => Brasilia
        [continent] => Amérique
    )
)
``` 

## Example -- Header

By default the first CSV entry is considered like a CSV header. So, first line values are used to build an associative array for each CSV entry. We can disable auto-header with :

```php
$csv = new Csv\Reader($csv_file, ['header' => false]);
````

## Example -- Mapping

Each CSV entry can be modified while reading. Fields can be added or removed, values can be updated.

```php
const populations = [
    'Brésil' => 210_000_000,
    'Japon' => 127_000_000,
];

// Merges continent to country
// Adds a field population
// Removes capital
$csv = new Csv\Reader($csv_file, [
    'map' => fn($entry) => [
        'country' => $entry['country'] . ' // ' . $entry['continent'],
        'population' => populations[$entry['country']] ?? '(unknow)',
    ],
]);

print_r($csv->readAll());
```

Renders something like :

```
Array (
    [0] => Array (
        [country] => Japon // Asie
        [population] => 127000000
    )
    [1] => Array (
        [country] => Hongrie // Europe
        [population] => (unknow)
    )
    [2] => Array (
        [country] => Brésil // Amérique
        [population] => 210000000
    )
)
```

## Example -- Filtering

Each CSV entry can be filtered while reading. Filter is applied after mapper.

```php
$csv = new Csv\Reader($csv_file, [
    // Searches additional data
    'map' => fn($entry) => $entry + ['population' => populations[$entry['country']],
    // Applies filter 
    'filter' => fn($entry) => $entry['population'] >= 200_000_000,
]);

print_r($csv->readAll());
```

Renders something like :

```
Array (
    [0] => Array (
        [country] => Brésil
        [capital] => Brasilia
        [continent] => Amérique
        [population] => 210000000
    )
)
```
