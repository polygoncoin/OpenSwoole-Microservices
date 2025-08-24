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
    private $_fileLocation;

    /**
     * Cache Folder
     *
     * The folder location outside docroot
     * without a slash at the end
     *
     * @var string
     */
    private $_cacheLocation = DIRECTORY_SEPARATOR . 'Dropbox';

    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
    }

    /**
     * Initialize check and serve file
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->_cacheLocation = Constants::$DOC_ROOT . $this->_cacheLocation;
        $this->filePath = DIRECTORY_SEPARATOR . trim(
            string: str_replace(
                search: ['../', '..\\', '/', '\\'],
                replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                subject: urldecode(string: $this->_c->req->ROUTE)
            ),
            characters: './\\'
        );
        $this->validateFileRequest();
        $this->_fileLocation = $this->_cacheLocation . $this->filePath;

        return true;
    }

    /**
     * Checks whether access to file is allowed
     *
     * @return void
     */
    public function validateFileRequest(): void
    {
        // check logic for user is allowed to access the file as per $this->_c->req->session
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
        $fileName = basename(path: $this->_fileLocation);

        // Get the $fileLocation file mime
        $fileInfo = finfo_open(flags: FILEINFO_MIME_TYPE);
        $mime = finfo_file(finfo: $fileInfo, filename: $fileLocation);
        finfo_close(finfo: $fileInfo);

        // Let Etag be last modified timestamp of file
        $modifiedTime = filemtime(filename: $this->_fileLocation);
        $eTag = "{$modifiedTime}";

        if ((isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && strpos(
                haystack: $_SERVER['HTTP_IF_NONE_MATCH'],
                needle: $eTag
            ) !== false)
            || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            && @strtotime(
                datetime: $_SERVER['HTTP_IF_MODIFIED_SINCE']
            ) == $modifiedTime)
        ) {
            header('HTTP/1.1 304 Not Modified');
            return true;
        }

        // send the headers
        //header("Content-Disposition: attachment;filename='$fileName';");
        header('Cache-Control: max-age=0, must-revalidate');
        header("Last-Modified: ".gmdate(
            format: "D, d M Y H:i:s",
            timestamp: $modifiedTime)." GMT"
        );
        header("Etag:\"{$eTag}\"");
        header('Expires: -1');
        header("Content-Type: $mime");
        header('Content-Length: ' . filesize(filename: $fileLocation));

        // Send file content as stream
        $fp = fopen(filename: $fileLocation, mode: 'rb');
        fpassthru(stream: $fp);

        return true;
    }
}
