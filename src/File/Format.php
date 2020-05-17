<?php

namespace SoundCloud\File;

use SoundCloud\UnsupportedAudioFormatException;

class Format
{
    /**
     * Supported audio MIME types
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_audioMimeTypes = array(
        'aac'  => 'video/mp4',
        'aiff' => 'audio/x-aiff',
        'flac' => 'audio/flac',
        'mp3'  => 'audio/mpeg',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/x-wav'
    );

    /**
     * Get the corresponding MIME type for a given file extension
     *
     * @param string $extension Given extension
     *
     * @return string
     * @throws Services_Soundcloud_Unsupported_Audio_Format_Exception
     *
     * @access public
     * @static
     */
    public static function getMimeType($extension)
    {
        if (array_key_exists($extension, self::$_audioMimeTypes)) {
            return self::$_audioMimeTypes[$extension];
        } else {
            throw new UnsupportedAudioFormatException();
        }
    }
}
