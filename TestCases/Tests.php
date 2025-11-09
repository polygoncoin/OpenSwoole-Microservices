<?php

/**
 * TestCases
 * php version 8.3
 *
 * @category  Tests
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCases;

/**
 * Tests
 * php version 8.3
 *
 * @category  Tests
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

class Tests
{
    /**
     * Process Auth based requests
     *
     * @return string
     */
    function processAuth(): string
    {
        return include __DIR__ . DIRECTORY_SEPARATOR . 'AuthTests.php';
    }

    /**
     * Process Open to web api requests
     *
     * @return string
     */
    function processOpen(): string
    {
        return include __DIR__ . DIRECTORY_SEPARATOR . 'OpenTests.php';
    }

    /**
     * Process Open to web api requests - Request/Response are in XML format
     *
     * @return string
     */
    function processXml(): string
    {
        return include __DIR__ . DIRECTORY_SEPARATOR . 'OpenTestsXml.php';
    }

    /**
     * Process Auth based requests
     *
     * @return string
     */
    function processSupplement(): string
    {
        return include __DIR__ . DIRECTORY_SEPARATOR . 'SupplementTest.php';
    }
}
