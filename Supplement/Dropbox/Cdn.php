<?php

/**
 * DropboxCacheAPI
 * php version 8.3
 *
 * @category  DropboxCacheAPI
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Dropbox;

use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\Supplement\Dropbox\DropboxInterface;
use Microservices\Supplement\Dropbox\CacheTrait;

/**
 * DropboxCacheAPI Category
 * php version 8.3
 *
 * @category  DropboxCacheAPI_Category
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Cdn implements DropboxInterface
{
	use CacheTrait;

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
	 * @var string
	 */
	private $DROPBOX_DIR = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(&$http = null)
	{
		$this->http = &$http;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		if ($this->http->req->isPrivateRequest) {
			$this->DROPBOX_DIR = Constant::$DROPBOX_PRIVATE_DIR;
		} else {
			$this->DROPBOX_DIR = Constant::$DROPBOX_PUBLIC_DIR;
		}

		$configuredRoute = str_replace(
			'/dropbox/cdn',
			'',
			$this->http->req->rParser->configuredRoute
		);

		$filePath = DIRECTORY_SEPARATOR . trim(
			string: str_replace(
				search: ['../', '..\\', '/', '\\'],
				replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
				subject: urldecode(string: $configuredRoute)
			),
			characters: './\\'
		);

		if (
			$this->http !== null
			&& $this->http->req !== null
			&& $this->http->req->isPrivateRequest
		) {
			$this->DROPBOX_DIR .= DIRECTORY_SEPARATOR . $this->http->req->customerId;
			$this->validateFileRequest();
		}
		$this->fileLocation = $this->DROPBOX_DIR . $filePath;

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
	 * @return mixed
	 */
	public function process(): mixed
	{
		$headerArr = [];
		$status = HttpStatus::$Ok;
		$data = '';

		// Get the $fileLocation file mime
		$this->mimeType = mime_content_type($this->fileLocation);

		switch (true) {
			case in_array($this->mimeType, $this->supportedVideoMimeArr):
				// Serve Video
				$videoStream = new StreamVideo(httpReqData: $this->http->httpReqData);
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
			(isset($this->http->httpReqData['header']['HTTP_IF_NONE_MATCH'])
				&& strpos(
					haystack: $this->http->httpReqData['header']['HTTP_IF_NONE_MATCH'],
					needle: $eTag
				) !== false
			)
			|| (isset($this->http->httpReqData['header']['HTTP_IF_MODIFIED_SINCE'])
				&& @strtotime(
					datetime: $this->http->httpReqData['header']['HTTP_IF_MODIFIED_SINCE']
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
