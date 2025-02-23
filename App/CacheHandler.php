<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

/**
 * Class to reduce cache hits and save on bandwidth using ETags and cache headers.
 *
 * HTTP etags header helps reduce the cache hits
 * Helps browser avoid unwanted hits to un-modified content on the server
 * which are cached on client browser.
 * The headers in class helps fetch only the modified content.
 *
 * @category   PHP File Cache handler
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
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
    private $cacheLocation = DIRECTORY_SEPARATOR . 'Dropbox';

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common
     */
    public function __construct(&$common)
    {
        $this->c = &$common;
    }

    /**
     * Initalise check and serve file
     *
     * @return boolean
     */
    public function init()
    {
        $this->cacheLocation = Constants::$DOC_ROOT . $this->cacheLocation;
        $this->filePath = DIRECTORY_SEPARATOR . trim(str_replace(['../', '..\\', '/', '\\'],['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],urldecode($this->c->httpRequest->ROUTE)), './\\');
        $this->validateFileRequest();
        $this->fileLocation = $this->cacheLocation . $this->filePath;

        return true;
    }

    /**
     * Checks whether access to file is allowed.
     *
     * @return void
     */
    public function validateFileRequest()
    {
        // check logic for user is allowed to access the file as per $this->c->httpRequest->session
        // $this->filePath;
    }

    /**
     * Serve File content
     *
     * @return void
     */
    public function process()
    {
        // File name requested for download
        $fileName = basename($this->fileLocation);

        // Get the $fileLocation file mime
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fileInfo, $fileLocation);
        finfo_close($fileInfo);

        // Let Etag be last modified timestamp of file.
        $modifiedTime = filemtime($this->fileLocation);
        $eTag = "{$modifiedTime}";

        if (
            (
                isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
                strpos($_SERVER['HTTP_IF_NONE_MATCH'], $eTag) !== false
            ) ||
            (
                isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
                @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $modifiedTime
            )
        ) {
            header('HTTP/1.1 304 Not Modified');
            return true;
        }

        // send the headers
        //header("Content-Disposition: attachment;filename='$fileName';");
        header('Cache-Control: max-age=0, must-revalidate');
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $modifiedTime)." GMT");
        header("Etag:\"{$eTag}\"");
        header('Expires: -1');
        header("Content-Type: $mime");
        header('Content-Length: ' . filesize($fileLocation));

        // Send file content as stream
        $fp = fopen($fileLocation, 'rb');
        fpassthru($fp);

        return true;
    }
}
