<?php

/**
 * Client side Cache
 * php version 8.3
 *
 * @category  ClientCache
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;

/**
 * Client side Caching via E-tags
 * php version 8.3
 *
 * @category  ClientCache_Etag
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class CacheHandler
{
    /**
     * File Location
     *
     * @var string
     */
    private $fileLocation;

    /**
     * Cache Folder
     *
     * The folder location outside docroot
     * without a slash at the end
     *
     * @var string
     */
    private $cacheLocation = DIRECTORY_SEPARATOR . 'Files' .
        DIRECTORY_SEPARATOR . 'Dropbox';

    /**
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize check and serve file
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->cacheLocation = Constants::$DOC_ROOT . $this->cacheLocation;
        $this->filePath = DIRECTORY_SEPARATOR . trim(
            string: str_replace(
                search: ['../', '..\\', '/', '\\'],
                replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                subject: urldecode(string: $this->c->req->ROUTE)
            ),
            characters: './\\'
        );
        $this->validateFileRequest();
        $this->fileLocation = $this->cacheLocation . $this->filePath;

        return true;
    }

    /**
     * Checks whether access to file is allowed
     *
     * @return void
     */
    public function validateFileRequest(): void
    {
        // check logic for user is allowed to access the file as per $this->c->req->s
        // $this->filePath;
    }

    /**
     * Serve File content
     *
     * @return bool
     */
    public function process(): bool
    {
        // File name requested for download
        $fileName = basename(path: $this->fileLocation);

        // Get the $fileLocation file mime
        $fileInfo = finfo_open(flags: FILEINFO_MIME_TYPE);
        $mime = finfo_file(finfo: $fileInfo, filename: $this->fileLocation);
        finfo_close(finfo: $fileInfo);

        // Let Etag be last modified timestamp of file
        $modifiedTime = filemtime(filename: $this->fileLocation);
        $eTag = "{$modifiedTime}";

        if (
            (isset($_SERVER['HTTP_IF_NONE_MATCH'])
                && strpos(
                    haystack: $_SERVER['HTTP_IF_NONE_MATCH'],
                    needle: $eTag
                ) !== false
            )
            || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            && @strtotime(
                datetime: $_SERVER['HTTP_IF_MODIFIED_SINCE']
            ) == $modifiedTime)
        ) {
            header(header: 'HTTP/1.1 304 Not Modified');
            return true;
        }

        // send the headers
        //header("Content-Disposition: attachment;filename='$fileName';");
        header(header: 'Cache-Control: max-age=0, must-revalidate');
        header(
            header: 'Last-Modified: ' . gmdate(
                format: 'D, d M Y H:i:s',
                timestamp: $modifiedTime
            ) . ' GMT'
        );
        header(header: "Etag:\"{$eTag}\"");
        header(header: 'Expires: -1');
        header(header: "Content-Type: {$mime}");
        header(header: 'Content-Length: ' . filesize(filename: $this->fileLocation));

        // Send file content as stream
        $fp = fopen(filename: $this->fileLocation, mode: 'rb');
        fpassthru(stream: $fp);

        return true;
    }
}
