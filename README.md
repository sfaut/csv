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

## Example

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
