<?php

/**
 * Customer side Dropbox Caching
 * php version 8.3
 *
 * @category  CustomerDropboxCache
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\DropboxHandler\StreamVideo;

/**
 * Customer side Caching via E-tags
 * php version 8.3
 *
 * @category  CustomerDropboxCache_Etag
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Dropbox
{
	/**
	 * HTTP request detail
	 *
	 * @var null|array
	 */
	private $httpReqData = null;

	/**
	 * HTTP object
	 *
	 * @var null|Http
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
	private $supportedVideoMimeArr = [
		'video/quicktime'
	];

	/**
	 * Dropbox Folder
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
	 * @param array $httpReqData HTTP request detail
	 * @param Http  $http
	 */
	public function __construct(&$httpReqData, &$http = null)
	{
		$this->httpReqData = &$httpReqData;
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
		if (!isset($this->httpReqData['get'][ROUTE_URL_PARAM])) {
			return false;
		}

		$this->modeDropBox = Constant::$DROP_BOX_DIR
			. DIRECTORY_SEPARATOR . $mode;

		$filePath = DIRECTORY_SEPARATOR . trim(
			string: str_replace(
				search: ['../', '..\\', '/', '\\'],
				replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
				subject: urldecode(string: $this->httpReqData['get'][ROUTE_URL_PARAM])
			),
			characters: './\\'
		);

		if (
			$this->http !== null
			&& $this->http->req !== null
			&& $this->http->req->isAuthRequest
			&& $mode === 'Closed'
		) {
			$this->modeDropBox .= DIRECTORY_SEPARATOR . $this->http->req->customerId;
			$this->validateFileRequest();
		}
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
		// check logic for user is allowed to access the file as per $this->http->req->s
		// $this->fileLocation;
	}

	/**
	 * Serve File content
	 *
	 * @return array
	 */
	public function process(): array
	{
		$headerArr = [];
		$status = HttpStatus::$Ok;
		$data = '';

		// Get the $fileLocation file mime
		$this->mimeType = mime_content_type($this->fileLocation);

		switch (true) {
			case in_array($this->mimeType, $this->supportedVideoMimeArr):
				// Serve Video
				$videoStream = new StreamVideo(httpReqData: $this->httpReqData);
				if (
					(
						$httpStatus = $videoStream->init(fileLocation: $this->fileLocation)
					) !== HttpStatus::$Ok
				) {
					$return = [$headerArr, $data, $httpStatus];
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
		$headerArr = [];
		$status = HttpStatus::$Ok;
		$data = '';

		// Let Etag be last modified timestamp of file
		$modifiedTime = filemtime(filename: $this->fileLocation);
		$eTag = "{$modifiedTime}";

		if (
			(isset($this->httpReqData['header']['HTTP_IF_NONE_MATCH'])
				&& strpos(
					haystack: $this->httpReqData['header']['HTTP_IF_NONE_MATCH'],
					needle: $eTag
				) !== false
			)
			|| (isset($this->httpReqData['header']['HTTP_IF_MODIFIED_SINCE'])
				&& @strtotime(
					datetime: $this->httpReqData['header']['HTTP_IF_MODIFIED_SINCE']
				) == $modifiedTime
			)
		) {
			$status = HttpStatus::$NotModified;
			return [$headerArr, $data, $status];
		}

		// Set header

		// File name requested for download
		// $fileName = basename(path: $this->fileLocation);
		// $headerArr['Content-Disposition'] = "attachment;filename='$fileName';";

		$headerArr['Cache-Control'] = 'max-age=0, must-revalidate';
		$headerArr['Last-Modified'] = gmdate(
			format: 'D, d M Y H:i:s',
			timestamp: $modifiedTime
		) . ' GMT';
		$headerArr['Etag'] = "\"{$eTag}\"";
		$headerArr['Expires'] = -1;
		$headerArr['Content-Type'] = "{$this->mimeType}";
		$headerArr['Content-Length'] = filesize(filename: $this->fileLocation);

		return [$headerArr, file_get_contents($this->fileLocation), $status];
	}
}
