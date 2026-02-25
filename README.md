# JSON Decode

PHP JSON Decode large data with lesser resources

## Examples

### Validating JSON.

```PHP
<?php

require_once __DIR__ . '/AutoloadJsonDecode.php';

use CustomJsonDecode\JsonDecoder;

// Creating json file handle
$fp = fopen('/usr/local/var/www/rnd/test.json', 'rb');

// Create JsonDecode Object.
JsonDecoder::init($fp);
$jsonDecodeObj = JsonDecoder::getObject();

// Validate JSON
$jsonDecodeObj->validate();

$jsonDecodeObj = null;
```

### Accessing data of Array after indexing JSON.

```PHP
<?php

require_once __DIR__ . '/AutoloadJsonDecode.php';

use CustomJsonDecode\JsonDecoder;

// Creating json file handle
$fp = fopen('/usr/local/var/www/rnd/test.json', 'rb');

// Create JsonDecode Object.
JsonDecoder::init($fp);
$jsonDecodeObj = JsonDecoder::getObject();

// Indexing JSON
$jsonDecodeObj->indexJson();

// Transverse across key 'data'
if (
    $jsonDecodeObj->isset('data') && $jsonDecodeObj->jsonType('data') === 'Array'
) {
    for ($i = 0, $iCount = $jsonDecodeObj->count('data'); $i < $iCount; $i++) {
        $key = "data:{$i}";

        // Row details without Sub arrays
        $row = $jsonDecodeObj->get($key);
        print_r($row);

        // Row with Sub arrays / Complete $key array recursively.
        $completeRow = $jsonDecodeObj->getCompleteArray($key);
        print_r($completeRow);
    }
}

$jsonDecodeObj = null;
```

## 🤝 Contributing

Issues and feature requests are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Heavy-JsonDecode/issues)

## Author

👤 **Ramesh N. Jangid (Sharma)**

- Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](LICENSE) licensed.
