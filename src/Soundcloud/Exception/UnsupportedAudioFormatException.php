<?php

namespace Soundcloud\Exception;

use \Exception;

/**
 * Soundcloud unsupported audio format exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class UnsupportedAudioFormatException extends Exception
{
    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'The given audio format is unsupported.';

}