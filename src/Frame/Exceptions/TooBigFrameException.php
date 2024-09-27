<?php



/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Kit\Websocket\Frame\Exceptions;

use Exception;

readonly class TooBigFrameException extends Exception
{
    /**
     * @param int $maxLength
     * @param string $message
     */
    public function __construct(
        public int $maxLength,
        public string $message = 'The frame is too big to be processed.'
    ) {
    }

}