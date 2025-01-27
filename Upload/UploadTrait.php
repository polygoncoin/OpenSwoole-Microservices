<?php
namespace Microservices\Upload;

/**
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
     * @param string $srcFilePath
     * @param string $destFilePath
     * @return boolean
     */
    private function saveFile($srcFilePath, $destFilePath)
    {
        $src = fopen($srcFilePath, "rb");
        $dest = fopen($destFilePath, 'wb');

        stream_copy_to_stream($src, $dest);

        fclose($dest);
        fclose($src);

        return true;
    }
}
