<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandlerContainer
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\SessionHandler\Containers;

use Microservices\App\Environment;
use Microservices\App\Http;

/**
 * Custom Session Handler Helper
 * php version 7
 *
 * @category  SessionHandlerContainer_Helper
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class SessionContainerHelper
{
	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	public $httpObject = null;

	/**
	 * Environment data
	 * 
	 * @var null|Environment
	 */
	public $Env = null;

	// Bitwise disjunction of the flags OPENSSL_RAW_DATA,
	// and OPENSSL_ZERO_PADDING or OPENSSL_DON'T_ZERO_PAD_KEY */
	private $encryptionOption = OPENSSL_RAW_DATA;

	// Usually 256-bit SESSION_ENCRYPTION_PASS_PHRASE
	public $SESSION_ENCRYPTION_PASS_PHRASE = null;

	// Usually 128-bit SESSION_ENCRYPTION_IV
	public $SESSION_ENCRYPTION_IV = null;

	// Session Start $options param
	public $sessionOptions = null;

	// Session cookie name
	public $sessionName = null;

	/**
	 * Customer Session Id
	 * 
	 * @param string $sessionId
	 * 
	 * @return string
	 */
	protected function getCustomerSessionId(
		$sessionId
	): string {
		return "{$this->httpObject->httpReqData['active']['customerId']}.";
	}

	/**
	 * Encryption
	 * 
	 * @param string $plainText Plain Text
	 * 
	 * @return string
	 */
	protected function encryptData(
		$plainText
	): string {
		if (
			!empty($this->SESSION_ENCRYPTION_PASS_PHRASE)
			&& !empty($this->SESSION_ENCRYPTION_IV)
		) {
			return base64_encode(
				string: openssl_encrypt(
					data: $plainText,
					cipher_algo: 'aes-256-cbc',
					passphrase: $this->SESSION_ENCRYPTION_PASS_PHRASE,
					options: $this->encryptionOption,
					iv: $this->SESSION_ENCRYPTION_IV
				)
			);
		}
		return $plainText;
	}

	/**
	 * Decryption
	 * 
	 * @param string $cipherText Cipher Text
	 * 
	 * @return bool|string
	 */
	protected function decryptData(
		$cipherText
	): bool|string {
		if (
			!empty($this->SESSION_ENCRYPTION_PASS_PHRASE)
			&& !empty($this->SESSION_ENCRYPTION_IV)
		) {
			return openssl_decrypt(
				data: base64_decode(
					string: $cipherText
				),
				cipher_algo: 'aes-256-cbc',
				passphrase: $this->SESSION_ENCRYPTION_PASS_PHRASE,
				options: $this->encryptionOption,
				iv: $this->SESSION_ENCRYPTION_IV
			);
		}
		return $cipherText;
	}
}
