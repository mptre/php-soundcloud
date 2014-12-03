<?php
/**
 * SoundCloud uploadable file representation class. Supports the CURLFile
 * class with graceful fallback.
 */
    class Services_Soundcloud_File
    {
        protected static function getPostField($path, $mimeType, $name)
        {
            if (function_exists('curl_file_create')) {
                return curl_file_create($path, $mimeType, $name);
            }

            return '@'.$path;
        }

        public static function create($path, $type)
        {
            if (strpos($path, '@') === 0) {
                $path = substr($path, 1);
            }
            
            $info = pathinfo($path);

            $mimeType = Services_Soundcloud_File_Format::getMimeType(
                $info['extension'],
                $type
            );

            $name = $info['basename'];

            return static::getPostField($path, $mimeType, $name);
        }
    }
?>
