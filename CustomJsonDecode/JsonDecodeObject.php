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

/**
 * Custom Json Decode Object
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
class JsonDecodeObject
{
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
     * Assoc / Array
     *
     * @var string
     */
    public $mode = '';

    /**
     * Assoc key for parent object
     *
     * @var null|string
     */
    public $assocKey = null;

    /**
     * Array key for parent object
     *
     * @var null|string
     */
    public $arrayKey = null;

    /**
     * Object values
     *
     * @var array
     */
    public $assocValues = [];

    /**
     * Array values
     *
     * @var int|string[]
     */
    public $arrayValues = [];

    /**
     * Constructor
     *
     * @param string      $mode     Values can be one among Array
     * @param null|string $assocKey Key
     */
    public function __construct($mode, $assocKey = null)
    {
        $this->mode = $mode;

        $assocKey = !is_null(value: $assocKey) ? trim(string: $assocKey) : $assocKey;
        $this->assocKey = !empty($assocKey) ? $assocKey : null;
    }
}
