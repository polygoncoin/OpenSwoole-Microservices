<?php
namespace Microservices\Upload;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

/**
 * Class is used for file uploads
 *
 * This class supports POST & PUT HTTP request
 *
 * @category   Upload Trait
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
trait UploadTrait
{
    /**
     * Saves file as stream
     *
     * @param string $absFilePath Absolute file path
     * @return boolean
     */
    private function saveFile($absFilePath)
    {
        $src = fopen("php://conditions", "rb");
        $dest = fopen($absFilePath, 'w+b');

        stream_copy_to_stream($src, $dest);

        fclose($dest);
        fclose($src);

        return true;
    }
}
