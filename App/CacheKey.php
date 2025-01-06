<?php
namespace Microservices\App;

/**
 * Cache Key
 *
 * Generates Cache Key
 *
 * @category   Cache Key
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class CacheKey
{
    /**
     * Get Client Key
     *
     * @param string $hostname
     * @return string
     */
    static public function Client($hostname)
    {
        return "c:{$hostname}";
    }

    /**
     * Get Client User Key
     *
     * @param integer $clientId
     * @param string  $username
     * @return string
     */
    static public function ClientUser($clientId, $username)
    {
        return "cu:{$clientId}:u:{$username}";
    }

    /**
     * Get Group Key
     *
     * @param integer $clientId
     * @return string
     */
    static public function Group($groupId)
    {
        return "g:{$groupId}";
    }

    /**
     * Get Group CIDR Key
     *
     * @param integer $groupId
     * @return string
     */
    static public function CIDR($groupId)
    {
        return "cidr:{$groupId}";
    }

    /**
     * Get Token Key
     *
     * @param string $token
     * @return string
     */
    static public function Token($token)
    {
        return "t:{$token}";
    }

    /**
     * Get User Token Key
     *
     * @param integer $userId
     * @return string
     */
    static public function UserToken($userId)
    {
        return "ut:{$userId}";
    }
}
