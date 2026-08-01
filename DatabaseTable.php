<?php

/**
 * Database Table Details
 * php version 8.3
 * 
 * @category  DatabaseTable
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices;

/**
 * Database Table Details
 * php version 8.3
 * 
 * @category  DatabaseTable
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DatabaseTable
{
	// Global Primary Key Column
	public static $globalCounterPrimaryKey 		= 'id';
	public static $requestPrimaryKey     		= 'request_id';
	public static $errorLogPrimaryKey      		= 'error_id';
	public static $debugLogPrimaryKey       	= 'debug_id';
	public static $superAdminPrimaryKey     	= 'super_admin_id';
	public static $superAdminContactPrimaryKey  = 'super_admin_contact_id';
	public static $superAdminGroupPrimaryKey    = 'super_admin_group_id';
	public static $customerPrimaryKey    		= 'customer_id';
	public static $customerContactPrimaryKey    = 'customer_contact_id';

	// Customer Master Primary Key Column
	public static $customerUserGroupPrimaryKey	= 'customer_user_group_id';
	public static $customerUserPrimaryKey    	= 'customer_user_id';
	public static $importFileDetailPrimaryKey   = 'id';
	public static $addressPrimaryKey    		= 'id';
	public static $categoryPrimaryKey   		= 'id';
}
