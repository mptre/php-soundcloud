<?php
class Services_Soundcloud_File_Format
{
    /**
     * Supported audio/image MIME types
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_mimeTypes = array(
        'audio' => array(
            'aac'  => 'video/mp4',
            'aiff' => 'audio/x-aiff',
            'flac' => 'audio/flac',
            'mp3'  => 'audio/mpeg',
            'ogg'  => 'audio/ogg',
            'wav'  => 'audio/x-wav'
        ),
        'image' => array(
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/png'
        )
    );

    /**
     * Get the corresponding MIME type for a given file extension
     *
     * @param string $extension Given extension
     * @param string $type File type (audio/image)
     *
     * @return string
     * @throws Services_Soundcloud_Unsupported_File_Format_Exception
     *
     * @access public
     * @static
     */
    static function getMimeType($extension, $type)
    {
        if (array_key_exists($type, self::$_mimeTypes)
        && array_key_exists($extension, self::$_mimeTypes[$type])) {
            return self::$_mimeTypes[$type][$extension];
        } else {
            throw new Services_Soundcloud_Unsupported_File_Format_Exception($type);
        }
    }
}
?>
