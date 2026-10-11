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

use Microservices\App\Constant;
use Microservices\App\Env;
use Microservices\App\SessionHandler\Containers\SessionContainerInterface;
use Microservices\App\SessionHandler\Containers\SessionContainerHelper;

/**
 * Custom Session Handler using Cookie
 * php version 7
 *
 * @category  SessionHandlerContainer_Cookie
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class CookieBasedSessionContainer extends SessionContainerHelper implements
    SessionContainerInterface
{
    public $sessionDataCookieName = null;

    /**
     * Initialize
     *
     * @param string $sessionSavePath Session Save Path
     * @param string $sessionName     Session Name
     *
     * @return void
     */
    public function init($sessionSavePath, $sessionName): void
    {
        if (
            empty($this->Env->SESSION_ENCRYPTION_IV)
            || empty($this->Env->SESSION_ENCRYPTION_PASS_PHRASE)
        ) {
            die('Please set encryption details in Session.php');
        }
    }

    /**
     * For Custom Session Handler - Validate session ID
     *
     * @param string $sessionId Session ID
     *
     * @return bool|string
     */
    public function getSession($sessionId): bool|string
    {
        if (
            isset($_COOKIE[$this->sessionDataCookieName])
            && !empty($_COOKIE[$this->sessionDataCookieName])
        ) {
            $sessionData = $this->decryptData(
                cipherText: $_COOKIE[$this->sessionDataCookieName]
            );
            $sessionDataArr = unserialize(data: $sessionData);
            if (
                isset($sessionDataArr['_TS_'])
                && ($time = $sessionDataArr['_TS_'] + $this->sessionOptions['cookie_lifetime'])
                && $time > Env::$timestamp
            ) {
                return $sessionData;
            }
        }
        return Constant::$FALSE;
    }

    /**
     * For Custom Session Handler - Write session data
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function setSession($sessionId, $sessionData): bool|int
    {
        $sessionDataArr = unserialize(data: $sessionData);
        $sessionDataArr['_TS_'] = Env::$timestamp;
        $sessionData = serialize(value: $sessionDataArr);

        $cookieData = $this->encryptData(plainText: $sessionData);
        if (strlen(string: $cookieData) > 4096) {
            ob_end_clean();
            die(
                'Session data length exceeds max 4 kilobytes (KB)' .
                ' supported per Cookie'
            );
        }

        return setcookie(
            name: $this->sessionDataCookieName,
            value: $cookieData,
            expires_or_options: [
				'expires' => 0,
				'path' => $this->sessionOptions['cookie_path'],
				'secure' => $this->sessionOptions['cookie_secure'],
				'httponly' => $this->sessionOptions['cookie_httponly'],
				'samesite' => $this->sessionOptions['cookie_samesite']
            ]
        );
    }

    /**
     * For Custom Session Handler - Update session data
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool|int
     */
    public function updateSession($sessionId, $sessionData): bool|int
    {
        return $this->setSession(sessionId: $sessionId, sessionData: $sessionData);
    }

    /**
     * For Custom Session Handler - Update session timestamp
     *
     * @param string $sessionId   Session ID
     * @param string $sessionData Session Data
     *
     * @return bool
     */
    public function touchSession($sessionId, $sessionData): bool
    {
        $sessionDataArr = unserialize(data: $sessionData);
        $sessionDataArr['_TS_'] = Env::$timestamp;
        $sessionData = serialize(value: $sessionDataArr);

        $cookieData = $this->encryptData(plainText: $sessionData);
        if (strlen(string: $cookieData) > 4096) {
            ob_end_clean();
            die(
                'Session data length exceeds max 4 kilobytes (KB)' .
                ' supported per Cookie'
            );
        }

        return setcookie(
            name: $this->sessionDataCookieName,
            value: $cookieData,
            expires_or_options: [
				'expires' => 0,
				'path' => $this->sessionOptions['cookie_path'],
				'secure' => $this->sessionOptions['cookie_secure'],
				'httponly' => $this->sessionOptions['cookie_httponly'],
				'samesite' => $this->sessionOptions['cookie_samesite']
            ]
        );
    }

    /**
     * For Custom Session Handler - Cleanup old sessions
     *
     * @param integer $sessionMaxLifetime Session Max Lifetime
     *
     * @return bool
     */
    public function gcSession($sessionMaxLifetime): bool
    {
        return Constant::$TRUE;
    }

    /**
     * For Custom Session Handler - Destroy a session
     *
     * @param string $sessionId Session ID
     *
     * @return bool
     */
    public function deleteSession($sessionId): bool
    {
        if (isset($_COOKIE[$this->sessionDataCookieName])) {
			return setcookie(
				name: $this->sessionDataCookieName,
				value: '',
				expires_or_options: [
					'expires' => 0,
					'path' => $this->sessionOptions['cookie_path'],
					'secure' => $this->sessionOptions['cookie_secure'],
					'httponly' => $this->sessionOptions['cookie_httponly'],
					'samesite' => $this->sessionOptions['cookie_samesite']
				]
			);
        }
        return Constant::$TRUE;
    }

    /**
     * Close Container
     *
     * @return void
     */
    public function closeSession(): void
    {
    }
}
