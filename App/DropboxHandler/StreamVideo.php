<?php

/**
 * Stream Video
 * php version 8.3
 *
 * @category  StreamVideo
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DropboxHandler;

use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * Stream Video
 * php version 8.3
 *
 * @category  StreamVideo
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class StreamVideo
{
	/**
	 * HTTP request detail
	 *
	 * @var null|array
	 */
	private $httpReqData = null;

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
	 * File detail required in class.
	 */
	public $fileLocation = '';
	public $name = '';
	public $mimeType = '';
	public $modifiedTimeStamp = 0;
	public $size = 0;
	public $streamFrom = 0;
	public $streamTill = 0;

	/**
	 * Constructor
	 *
	 * @param array $httpReqData Http request detail
	 */
	public function __construct(&$httpReqData)
	{
		$this->httpReqData = &$httpReqData;
	}

	/**
	 * Initialize
	 *
	 * @param string $fileLocation File Location
	 *
	 * @return bool|int
	 */
	public function init($fileLocation): bool|int
	{
		// Check Range header
		if (
			!isset($this->httpReqData['header']['range'])
			&& strpos(
				haystack: $this->httpReqData['header']['range'],
				needle: 'bytes='
				!== false
			)
		) {
			return HttpStatus::$BadRequest;
		}

		$this->fileLocation = $fileLocation;
		// Set buffer Range
		$range = explode(separator: '=', string: $this->httpReqData['header']['range'])[1];
		list($this->streamFrom, $this->streamTill) = explode(
			separator: '-',
			string: $range
		);

		//Set detail of file to be served.
		// Set file name
		$this->name = basename(path: $this->fileLocation);
		// Get file mime
		$this->mimeType = mime_content_type($this->fileLocation);
		// Get file modified time
		$this->modifiedTimeStamp = filemtime(filename: $this->fileLocation);
		// Get file size
		$this->size = filesize(filename: $this->fileLocation);

		return $this->validateFile();
	}

	/**
	 * Validate File related detail
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
	 * Set header on successful validation
	 *
	 * @return array
	 */
	public function setHeaders(): array
	{
		$headerArr = [];
		$status = HttpStatus::$Ok;

		$gmDate = gmdate(
			format: 'D, d M Y H:i:s',
			timestamp: Env::$timestamp + $this->cacheDuration
		);
		$headerArr['Content-Type'] = $this->mimeType;
		$headerArr['Cache-Control'] = 'max-age=' . $this->cacheDuration . ', public';
		$headerArr['Expires'] = "{$gmDate} GMT";
		$gmDate = gmdate(
			format: 'D, d M Y H:i:s',
			timestamp: $this->modifiedTimeStamp
		);
		$headerArr['Last-Modified'] = "{$gmDate} GMT";
		$headerArr['Accept-Ranges'] = '0-' . ($this->size - 1);
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
			$safariBrowserPattern = '`(\s|^)AppleWebKit/[\d\.]+\s+\(.+\)\s+'
				. 'Version/(1[0-9]|[2-9][0-9]|\d{3,})(\.|$|\s)`i';
			$safariBrowser = preg_match(
				pattern: $safariBrowserPattern,
				subject: $this->httpReqData['header']['userAgent']
			);
			if ($safariBrowser) {
				$this->streamTill = $this->size - 1;
				$headerArr['Content-Length'] = $this->size;
				return [$headerArr, $status];
			} else {
				$chunkSize = $this->size > $this->chunkSize
					? $this->chunkSize : $this->size;
				$this->streamTill = $chunkSize - 1;
				$streamSize = $this->streamTill - $this->streamFrom + 1;
			}
		} else {
			if ($this->size > ($this->streamFrom + $this->chunkSize)) {
				$this->streamTill = $this->streamFrom + $this->chunkSize;
			} else {
				$this->streamTill = $this->size - 1;
			}
			$streamSize = $this->streamTill - $this->streamFrom + 1;
		}
		$status = HttpStatus::$PartialContent;
		$headerArr['Content-Length'] = $streamSize;
		$headerArr['Content-Range'] = 'bytes ' . $this->streamFrom . '-'
			. $this->streamTill . '/' . $this->size;

		return [$headerArr, $status];
	}

	/**
	 * Serve video file content
	 *
	 * @return array
	 */
	public function serveContent(): array
	{
		[$headerArr, $status] = $this->setHeaders();

		$totalBytes = $this->streamTill - $this->streamFrom + 1;
		$data = file_get_contents(
			$this->fileLocation,
			false,
			null,
			$this->streamFrom,
			$totalBytes
		);

		return [$headerArr, $data, $status];
	}
}
