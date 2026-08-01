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
	private $httpObject = null;

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
	private $supportedVideoMimeArray = [
		'video/quicktime'
	];

	/**
	 * Dropbox Folder
	 * 
	 * @var string
	 */
	private $DROPBOX_DIRECTORY = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(&$httpObject = null)
	{
		$this->httpObject = &$httpObject;
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		if ($this->httpObject->httpRequestObject->isPrivateRequest) {
			$this->DROPBOX_DIRECTORY = Constant::$DROPBOX_PRIVATE_DIRECTORY;
		} else {
			$this->DROPBOX_DIRECTORY = Constant::$DROPBOX_PUBLIC_DIRECTORY;
		}

		$configuredRoute = str_replace(
			'/dropbox/cdn',
			'',
			$this->httpObject->httpRequestObject->routeParserObject->configuredRoute
		);

		$filePath = DIRECTORY_SEPARATOR . trim(
			string: str_replace(
				search: ['../', '..\\', '/', '\\'],
				replace: ['', '', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
				subject: urldecode(
					string: $configuredRoute
				)
			),
			characters: './\\'
		);

		if (
			$this->httpObject !== Constant::$NULL
			&& $this->httpObject->httpRequestObject !== Constant::$NULL
			&& $this->httpObject->httpRequestObject->isPrivateRequest
		) {
			$this->DROPBOX_DIRECTORY .= DIRECTORY_SEPARATOR . $this->httpObject->httpRequestObject->customerId;
			$this->validateFileRequest();
		}
		$this->fileLocation = $this->DROPBOX_DIRECTORY . $filePath;

		return (
			is_file(
				filename: $this->fileLocation
			)
			&& file_exists(
				filename: $this->fileLocation
			)
		);
	}

	/**
	 * Checks whether access to file is allowed
	 * 
	 * @return void
	 */
	public function validateFileRequest(): void
	{
		// check logic for user is allowed to access the file as per $this->httpObject->httpRequestObject->activeRequestData
		// $this->fileLocation;
	}

	/**
	 * Serve File content
	 * 
	 * @return mixed
	 */
	public function process(): mixed
	{
		$headerArray = [];
		$status = HttpStatus::$Ok;
		$data = '';

		// Get the $fileLocation file mime
		$this->mimeType = mime_content_type($this->fileLocation);

		switch (true) {
			case in_array(
				needle: $this->mimeType,
				haystack: $this->supportedVideoMimeArray,
				strict: Constant::$TRUE
			):
				// Serve Video
				$videoStream = new StreamVideo(
					httpReqData: $this->httpObject->httpReqData
				);
				if (
					(
						$httpStatus = $videoStream->init(
							fileLocation: $this->fileLocation
						)
					) !== HttpStatus::$Ok
				) {
					$return = [$headerArray, $data, $httpStatus];
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
		$headerArray = [];
		$status = HttpStatus::$Ok;
		$data = '';

		// Let Etag be last modified timestamp of file
		$modifiedTime = filemtime(
			filename: $this->fileLocation
		);
		$eTag = "{$modifiedTime}";

		if (
			(isset($this->httpObject->httpReqData['header']['HTTP_IF_NONE_MATCH'])
				&& strpos(
					haystack: $this->httpObject->httpReqData['header']['HTTP_IF_NONE_MATCH'],
					needle: $eTag
				) !== Constant::$FALSE
			)
			|| (isset($this->httpObject->httpReqData['header']['HTTP_IF_MODIFIED_SINCE'])
				&& @strtotime(
					datetime: $this->httpObject->httpReqData['header']['HTTP_IF_MODIFIED_SINCE']
				) == $modifiedTime
			)
		) {
			$status = HttpStatus::$NotModified;
			return [$headerArray, $data, $status];
		}

		// Set header

		// File name requested for download
		// $fileName = basename(path: $this->fileLocation);
		// $headerArray['Content-Disposition'] = "attachment;filename='$fileName';";

		$headerArray['Cache-Control'] = 'max-age=0, must-revalidate';
		$headerArray['Last-Modified'] = gmdate(
			format: 'D, d M Y H:i:s',
			timestamp: $modifiedTime
		) . ' GMT';
		$headerArray['Etag'] = "\"{$eTag}\"";
		$headerArray['Expires'] = -1;
		$headerArray['Content-Type'] = "{$this->mimeType}";
		$headerArray['Content-Length'] = filesize(
			filename: $this->fileLocation
		);

		return [$headerArray, file_get_contents($this->fileLocation), $status];
	}
}
