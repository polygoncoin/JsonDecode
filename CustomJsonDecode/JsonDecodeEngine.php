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

use CustomJsonDecode\JsonDecodeObject;

/**
 * Custom Json Decode Engine
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
class JsonDecodeEngine
{
    /**
     * File Handle
     *
     * @var null|resource
     */
    private $jsonFileHandle = null;

    /**
     * Array of JsonDecodeObject _objects
     *
     * @var JsonDecodeObject[]
     */
    private $objects = [];

    /**
     * Current JsonDecodeObject object
     *
     * @var null|JsonDecodeObject
     */
    private $currentObject = null;

    /**
     * Characters that are escaped while creating JSON
     *
     * @var string[]
     */
    private $escapers = array(
        "\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' '
    );

    /**
     * Characters that are escaped with for $escapers while creating JSON
     *
     * @var string[]
     */
    private $replacements = array(
        "\\\\", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' '
    );

    /**
     * JSON file start position
     *
     * @var null|int
     */
    public $startIndex = null;

    /**
     * JSON file end position
     *
     * @var null|int
     */
    public $endIndex = null;

    /**
     * JSON char counter
     * Starts from $_s_ till $_e_
     *
     * @var null|int
     */
    private $charCounter = null;

    /**
     * Constructor
     *
     * @param resource $jsonFileHandle JSON file handle
     */
    public function __construct(&$jsonFileHandle)
    {
        $this->jsonFileHandle = &$jsonFileHandle;
    }

    /**
     * Start processing the JSON string
     *
     * @param bool $index Index output
     *
     * @return \Generator
     */
    public function process($index = false): \Generator
    {
        // Flags Variable
        $quote = false;

        // Values inside Quotes
        $keyValue = '';
        $valueValue = '';

        // Values without Quotes
        $nullStr = null;

        // Variable mode - key/value;
        $varMode = 'keyValue';

        $strToEscape  = '';
        $prevIsEscape = false;

        $this->charCounter = $this->startIndex !== null ? $this->startIndex : 0;
        fseek(
            stream: $this->jsonFileHandle,
            offset: $this->charCounter,
            whence: SEEK_SET
        );

        for (
            ;
            (
                ($char = fgetc(stream: $this->jsonFileHandle)) !== false &&
                (
                    ($this->endIndex === null) ||
                    ($this->endIndex !== null
                        && $this->charCounter <= $this->endIndex
                    )
                )
            );
            $this->charCounter++
        ) {
            switch (true) {
                case $quote === false:
                    switch (true) {
                        // Start of Key or value inside quote
                        case $char === '"':
                            $quote = true;
                            $nullStr = '';
                            break;

                        //Switch mode to value collection after colon
                        case $char === ':':
                            $varMode = 'valueValue';
                            break;

                        // Start or End of Array
                        case in_array(needle: $char, haystack: ['[',']','{','}']):
                            $arr = $this->handleOpenClose(
                                char: $char,
                                keyValue: $keyValue,
                                nullStr: $nullStr,
                                index: $index
                            );
                            if ($arr !== false) {
                                yield $arr['key'] => $arr['value'];
                            }
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        // Check for null values
                        case $char === ',' && !is_null(value: $nullStr):
                            $nullStr = $this->checkNullStr(nullStr: $nullStr);
                            switch ($this->currentObject->mode) {
                                case 'Array':
                                    $this->currentObject->arrayValues[] = $nullStr;
                                    break;
                                case 'Assoc':
                                    if (!empty($keyValue)) {
                                        $this->currentObject->assocValues[$keyValue] = $nullStr;
                                    }
                                    break;
                            }
                            $nullStr = null;
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        //Switch mode to value collection after colon
                        case in_array(needle: $char, haystack: $this->escapers):
                            break;

                        // Append char to null string
                        case !in_array(needle: $char, haystack: $this->escapers):
                            $nullStr .= $char;
                            break;
                    }
                    break;

                case $quote === true:
                    switch (true) {
                        // Collect string to be escaped
                        case $varMode === 'valueValue'
                            && ($char === '\\'
                                || ($prevIsEscape
                                    && in_array(
                                        needle: $strToEscape . $char,
                                        haystack: $this->replacements
                                    )
                            )):
                            $strToEscape .= $char;
                            $prevIsEscape = true;
                            break;

                        // Escape value with char
                        case $varMode === 'valueValue'
                            && $prevIsEscape === true
                            && in_array(
                                needle: $strToEscape . $char,
                                haystack: $this->replacements
                            ):
                            $$varMode .= str_replace(
                                search: $this->replacements,
                                replace: $this->escapers,
                                subject: $strToEscape . $char
                            );
                            $strToEscape = '';
                            $prevIsEscape = false;
                            break;

                        // Escape value without char
                        case $varMode === 'valueValue' && $prevIsEscape === true
                            && in_array(
                                needle: $strToEscape,
                                haystack: $this->replacements
                            ):
                            $$varMode .= str_replace(
                                search: $this->replacements,
                                replace: $this->escapers,
                                subject: $strToEscape
                            ) . $char;
                            $strToEscape = '';
                            $prevIsEscape = false;
                            break;

                        // Closing double quotes
                        case $char === '"':
                            $quote = false;
                            switch (true) {
                                // Closing quote of Key
                                case $varMode === 'keyValue':
                                    $varMode = 'valueValue';
                                    break;

                                // Closing quote of Value
                                case $varMode === 'valueValue':
                                    $this->currentObject->assocValues[$keyValue] = $valueValue;
                                    $keyValue = $valueValue = '';
                                    $varMode = 'keyValue';
                                    break;
                            }
                            break;

                        // Collect values for key or value
                        default:
                            $$varMode .= $char;
                    }
                    break;
            }
            break;
        }
        $this->objects = [];
        $this->currentObject = null;
    }

    /**
     * Get JSON string
     *
     * @return bool|string
     */
    public function getJsonString(): bool|string
    {
        $offset = $this->startIndex !== null ? $this->startIndex : 0;
        $length = $this->endIndex - $offset + 1;

        return stream_get_contents(
            stream: $this->jsonFileHandle,
            length: $length,
            offset: $offset
        );
    }

    /**
     * Handles array / object open close char
     *
     * @param string $char     Character among any one "[" "]" "{" "}"
     * @param string $keyValue String value of key of an object
     * @param string $nullStr  String present in JSON without double quotes
     * @param bool   $index    Index output
     *
     * @return array|bool
     */
    private function handleOpenClose($char, $keyValue, $nullStr, $index): array|bool
    {
        $arr = false;
        switch ($char) {
            case '[':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startArray(key: $keyValue);
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startObject(key: $keyValue);
                break;
            case ']':
                if (!empty($keyValue)) {
                    $this->currentObject->arrayValues[] = $keyValue;
                    if (is_null(value: $this->currentObject->arrayKey)) {
                        $this->currentObject->arrayKey = 0;
                    } else {
                        $this->currentObject->arrayKey++;
                    }
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_s_' => $this->currentObject->startIndex,
                            '_e_' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->arrayValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->currentObject->arrayValues
                        ];
                    }
                }
                $this->currentObject = null;
                $this->popPreviousObject();
                break;
            case '}':
                if (!empty($keyValue) && !empty($nullStr)) {
                    $nullStr = $this->checkNullStr(nullStr: $nullStr);
                    $this->currentObject->assocValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_s_' => $this->currentObject->startIndex,
                            '_e_' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->assocValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->currentObject->assocValues
                        ];
                    }
                }
                $this->currentObject = null;
                $this->popPreviousObject();
                break;
        }
        if (
            $arr !== false
            && !empty($arr)
            && isset($arr['value'])
            && $arr['value'] !== false
            && count(value: $arr['value']) > 0
        ) {
            return $arr;
        }
        return false;
    }

    /**
     * Check String present in JSON without double quotes for null or int
     *
     * @param string $nullStr String present in JSON without double quotes
     *
     * @return bool|int|null
     */
    private function checkNullStr($nullStr): bool|int|null
    {
        $return = false;
        if ($nullStr === 'null') {
            $return = null;
        } elseif (is_numeric(value: $nullStr)) {
            $return = (int)$nullStr;
        }
        if ($return === false) {
            $this->isBadJson(str: $nullStr);
        }
        return $return;
    }

    /**
     * Start of array
     *
     * @param null|string $key Used while creating simple array inside an object
     *
     * @return void
     */
    private function startArray($key = null): void
    {
        $this->pushCurrentObject(key: $key);
        $this->currentObject = new JsonDecodeObject(mode: 'Array', assocKey: $key);
        $this->currentObject->startIndex = $this->charCounter;
    }

    /**
     * Start of object
     *
     * @param null|string $key Used while creating object inside an object
     *
     * @return void
     */
    private function startObject($key = null): void
    {
        $this->pushCurrentObject(key: $key);
        $this->currentObject = new JsonDecodeObject(mode: 'Assoc', assocKey: $key);
        $this->currentObject->startIndex = $this->charCounter;
    }

    /**
     * Push current object
     *
     * @param string $key Key
     *
     * @return void
     */
    private function pushCurrentObject($key): void
    {
        if ($this->currentObject) {
            if (
                $this->currentObject->mode === 'Assoc'
                && (is_null(value: $key) || empty(trim(string: $key)))
            ) {
                $this->isBadJson(str: $key);
            }
            if (
                $this->currentObject->mode === 'Array'
                && (is_null(value: $key) || empty(trim(string: $key)))
            ) {
                $this->isBadJson(str: $key);
            }
            array_push($this->objects, $this->currentObject);
        }
    }

    /**
     * Pop Previous object
     *
     * @return void
     */
    private function popPreviousObject(): void
    {
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop($this->objects);
        } else {
            $this->currentObject = null;
        }
    }

    /**
     * Increment arrayKey counter for array of _objects or arrays
     *
     * @return void
     */
    private function increment(): void
    {
        if (
            !is_null(value: $this->currentObject)
            && $this->currentObject->mode === 'Array'
        ) {
            if (is_null(value: $this->currentObject->arrayKey)) {
                $this->currentObject->arrayKey = 0;
            } else {
                $this->currentObject->arrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values
     *
     * @return array|bool
     */
    private function getObjectValues(): array|bool
    {
        $arr = false;
        if (
            !is_null(value: $this->currentObject)
            && $this->currentObject->mode === 'Assoc'
            && count(value: $this->currentObject->assocValues) > 0
        ) {
            $arr = $this->currentObject->assocValues;
            $this->currentObject->assocValues = [];
        }
        return $arr;
    }

    /**
     * Check for a valid JSON
     *
     * @param string $str String
     *
     * @return void
     */
    private function isBadJson($str): void
    {
        $str =  !is_null(value: $str) ? trim(string: $str) : $str;
        if (!empty($str)) {
            die("Invalid JSON: {$str}");
        }
    }

    /**
     * Generated Array
     *
     * @return array
     */
    private function getKeys(): array
    {
        $keys = [];
        $return = &$keys;
        $objCount = count(value: $this->objects);
        if ($objCount > 0) {
            for ($i = 0; $i < $objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Assoc':
                        if (!is_null(value: $this->objects[$i]->assocKey)) {
                            $keys[] = $this->objects[$i]->assocKey;
                        }
                        break;
                    case 'Array':
                        if (!is_null(value: $this->objects[$i]->assocKey)) {
                            $keys[] = $this->objects[$i]->assocKey;
                        }
                        if (!is_null(value: $this->objects[$i]->arrayKey)) {
                            $keys[] = $this->objects[$i]->arrayKey;
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Assoc':
                    if (!is_null(value: $this->currentObject->assocKey)) {
                        $keys[] = $this->currentObject->assocKey;
                    }
                    break;
                case 'Array':
                    if (!is_null(value: $this->currentObject->assocKey)) {
                        $keys[] = $this->currentObject->assocKey;
                    }
                    break;
            }
        }
        return $return;
    }

    /**
     * Generated Assoc Array
     *
     * @return array
     */
    private function getAssocKeys(): array
    {
        $keys = [];
        $return = &$keys;
        $objCount = count(value: $this->objects);
        if ($objCount > 0) {
            for ($i = 0; $i < $objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Assoc':
                        if (!is_null(value: $this->objects[$i]->assocKey)) {
                            $keys[$this->objects[$i]->assocKey] = [];
                            $keys = &$keys[$this->objects[$i]->assocKey];
                        }
                        break;
                    case 'Array':
                        if (!is_null(value: $this->objects[$i]->assocKey)) {
                            $keys[$this->objects[$i]->assocKey] = [];
                            $keys = &$keys[$this->objects[$i]->assocKey];
                        }
                        if (!is_null(value: $this->objects[$i]->arrayKey)) {
                            $keys[$this->objects[$i]->arrayKey] = [];
                            $keys = &$keys[$this->objects[$i]->arrayKey];
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Assoc':
                    if (!is_null(value: $this->currentObject->assocKey)) {
                        $keys[$this->currentObject->assocKey] = [];
                        $keys = &$keys[$this->currentObject->assocKey];
                    }
                    break;
                case 'Array':
                    if (!is_null(value: $this->currentObject->assocKey)) {
                        $keys[$this->currentObject->assocKey] = [];
                        $keys = &$keys[$this->currentObject->assocKey];
                    }
                    break;
            }
        }
        return $return;
    }
}
