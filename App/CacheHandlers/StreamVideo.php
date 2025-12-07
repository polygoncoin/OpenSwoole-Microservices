<?php

/**
 * Stream Video
 * php version 8.3
 *
 * @category  StreamVideo
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\CacheHandlers;

use Microservices\App\Common;
use Microservices\App\HttpStatus;

/**
 * Stream Video
 * php version 8.3
 *
 * @category  StreamVideo
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class StreamVideo
{
    /**
     * File request details
     *
     * @var null|array
     */
    private $http = null;

    /**
     * Streamed Video cache duration.
     *
     * @var integer
     */
    private $cacheDuration = 7 * 24 * 3600; // 1 week

    /**
     * Streamed Video size for first request.
     *
     * @var integer
     */
    private $firstChunkSize = 128 * 1024; // 128 KB

    /**
     * Streamed Video size per request.
     *
     * @var integer
     */
    private $chunkSize = 4 * 1024 * 1024; // 4 MB

    /**
     * File details required in class.
     */
    public $file = '';
    public $name = '';
    public $mimeType = '';
    public $modifiedTimeStamp = 0;
    public $size = 0;
    public $streamFrom = 0;
    public $streamTill = 0;

    /**
     * Constructor
     */
    public function __construct(&$http)
    {
        $this->http = &$http;
    }

    /**
     * Initialize
     *
     * @param string $file File path
     *
     * @return bool|int
     */
    public function init($file): bool|int
    {
        // Check Range header
        if (
            !isset($this->http['header']['range']) && strpos(
                haystack: $this->http['header']['range'],
                needle: 'bytes='
            ) !== false
        ) {
            return HttpStatus::$BadRequest;
        }

        $this->file = $file;
        // Set buffer Range
        $range = explode(separator: '=', string: $this->http['header']['range'])[1];
        list($this->streamFrom, $this->streamTill) = explode(
            separator: '-',
            string: $range
        );

        //Set details of file to be served.
        // Set file name
        $this->name = basename(path: $this->file);
        // Get file mime
        $this->mimeType = mime_content_type($this->file);
        // Get file modified time
        $this->modifiedTimeStamp = filemtime(filename: $this->file);
        // Get file size
        $this->size = filesize(filename: $this->file);

        return $this->validateFile();
    }

    /**
     * Validate File related details
     *
     * @return bool|int
     */
    public function validateFile(): bool|int
    {
        if ($this->streamFrom >= $this->size) {
            return HttpStatus::$RangeNotSatisfiable;
        }

        return HttpStatus::$Ok;
    }

    /**
     * Set headers on successful validation
     *
     * @return array
     */
    public function setHeaders(): array
    {
        $headers = [];
        $status = HttpStatus::$Ok;

        $gmDate = gmdate(
            format: 'D, d M Y H:i:s',
            timestamp: Common::$timestamp + $this->cacheDuration
        );
        $headers['Content-Type'] = $this->mimeType;
        $headers['Cache-Control'] = 'max-age=' . $this->cacheDuration . ', public';
        $headers['Expires'] = "{$gmDate} GMT";
        $gmDate = gmdate(
            format: 'D, d M Y H:i:s',
            timestamp: $this->modifiedTimeStamp
        );
        $headers['Last-Modified'] = "{$gmDate} GMT";
        $headers['Accept-Ranges'] = '0-' . ($this->size - 1);
        if ($this->streamFrom == 0) {
            $this->chunkSize = $this->firstChunkSize;
        }
        if (
            $this->streamFrom == 0
            && in_array(
                needle: $this->streamTill,
                haystack: ['', '1']
            )
        ) {
            // Mac Safari does not support HTTP/1.1 206 response for first
            // request while fetching video content.
            // Regex pattern from https://regex101.com/r/gRLirS/1
            $safariBrowserPattern = '`(\s|^)AppleWebKit/[\d\.]+\s+\(.+\)\s+' .
                'Version/(1[0-9]|[2-9][0-9]|\d{3,})(\.|$|\s)`i';
            $safariBrowser = preg_match(
                pattern: $safariBrowserPattern,
                subject: $this->http['header']['user-agent']
            );
            if ($safariBrowser) {
                $this->streamTill = $this->size - 1;
                $headers['Content-Length'] = $this->size;
                return [$headers, $status];
            } else {
                $chunkSize = $this->size > $this->chunkSize ?
                    $this->chunkSize : $this->size;
                $this->streamTill = $chunkSize - 1;
                $streamSize = $this->streamTill - $this->streamFrom + 1;
            }
        } else {
            if ($this->size > ((int)$this->streamFrom + $this->chunkSize)) {
                $this->streamTill = $this->streamFrom + $this->chunkSize;
            } else {
                $this->streamTill = $this->size - 1;
            }
            $streamSize = $this->streamTill - $this->streamFrom + 1;
        }
        $status = HttpStatus::$PartialContent;
        $headers['Content-Length'] = $streamSize;
        $headers['Content-Range'] = 'bytes ' . $this->streamFrom . '-' .
            $this->streamTill . '/' . $this->size;

        return [$headers, $status];
    }

    /**
     * Serve video file content
     *
     * @return array
     */
    public function serveContent(): array
    {
        [$headers, $status] = $this->setHeaders();

        $totalBytes = $this->streamTill - $this->streamFrom + 1;
        $data = file_get_contents(
            $this->file,
            false,
            null,
            $this->streamFrom,
            $totalBytes
        );

        return [$headers, $data, $status];
    }
}
