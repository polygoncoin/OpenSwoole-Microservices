<?php
/**
 * UploadAPI
 * php version 8.3
 *
 * @category  UploadAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Supplement\Upload;

/**
 * UploadAPI Trait
 * php version 8.3
 *
 * @category  UploadAPI_Trait
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
trait UploadTrait
{
    /**
     * Saves file as stream
     *
     * @param string $absFilePath Absolute file path
     *
     * @return bool
     */
    private function _saveFile($absFilePath): bool
    {
        $src = fopen(filename: "php://input", mode: "rb");
        $dest = fopen(filename: $absFilePath, mode: 'wb');

        stream_copy_to_stream(from: $src, to: $dest);

        fclose(stream: $dest);
        fclose(stream: $src);

        return true;
    }
}
