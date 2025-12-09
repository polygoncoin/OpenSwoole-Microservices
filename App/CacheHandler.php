<?php

/**
 * Cache Handler
 * php version 8.3
 *
 * @category  Cache Handler
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\HttpStatus;
use Microservices\App\CacheHandlers\StreamVideo;

/**
 * Client side Caching via E-tags
 * php version 8.3
 *
 * @category  Cache Handler
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
     * File request details
     *
     * @var null|array
     */
    private $http = null;

    /**
     * File Location
     *
     * @var string
     */
    private $fileLocation;

    /**
     * File mime type
     *
     * @var null|string
     */
    private $mimeType = null;

    /**
     * Supported Video mime types
     *
     * @var array
     */
    private $supportedVideoMimes = [
        'video/quicktime'
    ];

    /**
     * Cache Folder
     *
     * The folder location outside docroot
     * without a slash at the end
     *
     * @var string
     */
    private $modeDropBox = null;

    /**
     * Constructor
     *
     * @param array $http HTTP request details
     */
    public function __construct(&$http)
    {
        $this->http = &$http;
    }

    /**
     * Initialize check and serve file
     *
     * @param string $mode Open (Public access) / Closed (Requires Auth)
     *
     * @return bool
     */
    public function init($mode): bool
    {
        if (!isset($this->http['get'][ROUTE_URL_PARAM])) {
            return false;
        }

        $this->modeDropBox = Constants::$DROP_BOX_DIR .
            DIRECTORY_SEPARATOR . $mode;

        $filePath = DIRECTORY_SEPARATOR . trim(
            string: str_replace(
                search: ['../', '..\\', '/', '\\'],
                replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                subject: urldecode(string: $this->http['get'][ROUTE_URL_PARAM])
            ),
            characters: './\\'
        );
        $this->validateFileRequest();
        $this->fileLocation = $this->modeDropBox . $filePath;

        return (
            is_file(filename: $this->fileLocation)
            && file_exists(filename: $this->fileLocation)
        );
    }

    /**
     * Checks whether access to file is allowed
     *
     * @return void
     */
    public function validateFileRequest(): void
    {
        // check logic for user is allowed to access the file as per $this->api->req->s
        // $this->fileLocation;
    }

    /**
     * Serve File content
     *
     * @return array
     */
    public function process(): array
    {
        $headers = [];
        $status = HttpStatus::$Ok;
        $data = '';

        // Get the $fileLocation file mime
        $this->mimeType = mime_content_type($this->fileLocation);

        switch (true) {
            case in_array($this->mimeType, $this->supportedVideoMimes):
                // Serve Video
                $videoStream = new StreamVideo($this->http);
                if (
                    (
                        $httpStatus = $videoStream->init($this->fileLocation)
                    ) !== HttpStatus::$Ok
                ) {
                    $return = [$headers, $data, $httpStatus];
                } else {
                    $return = $videoStream->serveContent();
                }
                break;
            default:
                $return = $this->serveDefault();
        }

        return $return;
    }

    /**
     * Serve default
     *
     * @return array
     */
    public function serveDefault(): array
    {
        $headers = [];
        $status = HttpStatus::$Ok;
        $data = '';

        // Let Etag be last modified timestamp of file
        $modifiedTime = filemtime(filename: $this->fileLocation);
        $eTag = "{$modifiedTime}";

        if (
            (isset($this->http['header']['HTTP_IF_NONE_MATCH'])
                && strpos(
                    haystack: $this->http['header']['HTTP_IF_NONE_MATCH'],
                    needle: $eTag
                ) !== false
            )
            || (isset($this->http['header']['HTTP_IF_MODIFIED_SINCE'])
            && @strtotime(
                datetime: $this->http['header']['HTTP_IF_MODIFIED_SINCE']
            ) == $modifiedTime)
        ) {
            $status = HttpStatus::$NotModified;
            return [$headers, $data, $status];
        }

        // Set headers

        // File name requested for download
        // $fileName = basename(path: $this->fileLocation);
        // $headers['Content-Disposition'] = "attachment;filename='$fileName';";

        $headers['Cache-Control'] = 'max-age=0, must-revalidate';
        $headers['Last-Modified'] = gmdate(
            format: 'D, d M Y H:i:s',
            timestamp: $modifiedTime
        ) . ' GMT';
        $headers['Etag'] = "\"{$eTag}\"";
        $headers['Expires'] = -1;
        $headers['Content-Type'] = "{$this->mimeType}";
        $headers['Content-Length'] = filesize(filename: $this->fileLocation);

        return [$headers, file_get_contents($this->fileLocation), $status];
    }
}
