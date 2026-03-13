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

use Microservices\App\Http;
use Microservices\App\Constant;
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
	 * Http Request Details
	 *
	 * @var null|array
	 */
	private $iConfig = null;

	/**
	 * Http Object
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
	private $supportedVideoMimes = [
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
	 * @param array $iConfig Http Request Details
	 * @param Http  $http
	 */
	public function __construct(&$iConfig, &$http = null)
	{
		$this->iConfig = &$iConfig;
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
		if (!isset($this->iConfig['get'][ROUTE_URL_PARAM])) {
			return false;
		}

		$this->modeDropBox = Constant::$DROP_BOX_DIR
			. DIRECTORY_SEPARATOR . $mode;

		$filePath = DIRECTORY_SEPARATOR . trim(
			string: str_replace(
				search: ['../', '..\\', '/', '\\'],
				replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
				subject: urldecode(string: $this->iConfig['get'][ROUTE_URL_PARAM])
			),
			characters: './\\'
		);

		if ($mode === 'Closed') {
			$this->modeDropBox .= DIRECTORY_SEPARATOR . $this->http->req->cId;
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
		$headers = [];
		$status = HttpStatus::$Ok;
		$data = '';

		// Get the $fileLocation file mime
		$this->mimeType = mime_content_type($this->fileLocation);

		switch (true) {
			case in_array($this->mimeType, $this->supportedVideoMimes):
				// Serve Video
				$videoStream = new StreamVideo(iConfig: $this->iConfig);
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
			(isset($this->iConfig['header']['HTTP_IF_NONE_MATCH'])
				&& strpos(
					haystack: $this->iConfig['header']['HTTP_IF_NONE_MATCH'],
					needle: $eTag
				) !== false
			)
			|| (isset($this->iConfig['header']['HTTP_IF_MODIFIED_SINCE'])
				&& @strtotime(
					datetime: $this->iConfig['header']['HTTP_IF_MODIFIED_SINCE']
				) == $modifiedTime
			)
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
