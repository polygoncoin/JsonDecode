<?php

/**
 * Custom Json Decode
 * php version 7
 *
 * @category  JsonDecode
 * @package   CustomJsonDecode
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */

namespace CustomJsonDecode;

use CustomJsonDecode\JsonDecodeEngine;

/**
 * Custom Json Decode
 * php version 7
 *
 * @category  JsonDecode
 * @package   CustomJsonDecode
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonDecode
{
    /**
     * Json File Handle
     *
     * @var null|resource
     */
    private $jsonFileHandle = null;

    /**
     * JSON file indexes
     * Contains start and end positions for requested indexes
     *
     * @var null|array
     */
    public $jsonFileIndex = null;

    /**
     * Allowed Payload length
     *
     * @var int
     */
    private $allowedPayloadLength = 100 * 1024 * 1024; // 100 MB

    /**
     * Json Decode Engine Object
     *
     * @var null|JsonDecodeEngine
     */
    private $jsonDecodeEngine = null;

    /**
     * Constructor
     *
     * @param resource $jsonFileHandle JSON File handle
     */
    public function __construct(&$jsonFileHandle)
    {
        $this->jsonFileHandle = &$jsonFileHandle;

        // File Stats - Check for size
        $fileStats = fstat(stream: $this->jsonFileHandle);
        if (
            isset($fileStats['size'])
            && $fileStats['size'] > $this->allowedPayloadLength
        ) {
            die('File size greater than allowed size');
        }
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): void
    {
        // Init Json Decode Engine
        $this->jsonDecodeEngine = new JsonDecodeEngine(
            jsonFileHandle: $this->jsonFileHandle
        );
    }
    /**
     * Validates JSON
     *
     * @return void
     */
    public function validate(): void
    {
        foreach ($this->jsonDecodeEngine->process() as &$valueArr) {
            ;
        }
    }

    /**
     * Index file JSON
     *
     * @return void
     */
    public function indexJson(): void
    {
        $this->jsonFileIndex = null;
        foreach ($this->jsonDecodeEngine->process(index: true) as $keys => $val) {
            if (
                isset($val['_s_'])
                && isset($val['_e_'])
            ) {
                $jsonFileIndex = &$this->jsonFileIndex;
                for ($i = 0, $iCount = count(value: $keys); $i < $iCount; $i++) {
                    if (
                        is_numeric(value: $keys[$i])
                        && !isset($jsonFileIndex[$keys[$i]])
                    ) {
                        $jsonFileIndex[$keys[$i]] = [];
                        if (!isset($jsonFileIndex['_c_'])) {
                            $jsonFileIndex['_c_'] = 0;
                        }
                        if (is_numeric(value: $keys[$i])) {
                            $jsonFileIndex['_c_']++;
                        }
                    }
                    $jsonFileIndex = &$jsonFileIndex[$keys[$i]];
                }
                $jsonFileIndex['_s_'] = $val['_s_'];
                $jsonFileIndex['_e_'] = $val['_e_'];
            }
        }
    }

    /**
     * Keys exist
     *
     * @param null|string $keys Keys exist (values separated by colon)
     *
     * @return bool
     */
    public function isset($keys = null): bool
    {
        $return = true;
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    $return = false;
                    break;
                }
            }
        }
        return $return;
    }

    /**
     * Key exist
     *
     * @param null|string $keys Key values separated by colon
     *
     * @return string
     */
    public function jsonType($keys = null): string
    {
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        $return = 'Object';
        if (
            isset($jsonFileIndex['_s_'])
            && isset($jsonFileIndex['_e_'])
            && isset($jsonFileIndex['_c_'])
        ) {
            $return = 'Array';
        }
        return $return;
    }

    /**
     * Count of array element
     *
     * @param null|string $keys Key values separated by colon
     *
     * @return int
     */
    public function count($keys = null): mixed
    {
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        if (
            !(isset($jsonFileIndex['_s_'])
            && isset($jsonFileIndex['_e_'])
            && isset($jsonFileIndex['_c_']))
        ) {
            return 0;
        }
        return $jsonFileIndex['_c_'];
    }

    /**
     * Pass the keys and get whole json content belonging to keys
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    public function get($keys = ''): mixed
    {
        if (!$this->isset(keys: $keys)) {
            return false;
        }
        $valueArr = [];
        $this->load(keys: $keys);
        foreach ($this->jsonDecodeEngine->process() as $keyArr => $valueArr) {
            break;
        }
        return $valueArr;
    }

    /**
     * Get complete JSON for Kays
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    public function getCompleteArray($keys = ''): mixed
    {
        if (!$this->isset(keys: $keys)) {
            return false;
        }
        $this->load(keys: $keys);
        return json_decode(
            json: $this->jsonDecodeEngine->getJsonString(),
            associative: true
        );
    }

    /**
     * Start processing the JSON string for a keys
     * Perform search inside keys of JSON like $json['data'][0]['data1']
     *
     * @param string $keys Key values separated by colon
     *
     * @return void
     * @throws \Exception
     */
    public function load($keys): void
    {
        if (empty($keys) && $keys != 0) {
            $this->jsonDecodeEngine->startIndex = null;
            $this->jsonDecodeEngine->endIndex = null;
            return;
        }
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        if (
            isset($jsonFileIndex['_s_'])
            && isset($jsonFileIndex['_e_'])
        ) {
            $this->jsonDecodeEngine->startIndex = $jsonFileIndex['_s_'];
            $this->jsonDecodeEngine->endIndex = $jsonFileIndex['_e_'];
        } else {
            throw new \Exception(message: "Invalid keys '{$keys}'", code: 400);
        }
    }
}
