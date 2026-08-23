<?php

declare(strict_types=1);

namespace Quillstack\Framework\Exceptions;

use Quillstack\Framework\QuillstackException;

/**
 * A route says only somebody may reach it, and the application has not said who anybody is.
 *
 * Left alone, that route would be open while reading as guarded — which is the one failure
 * this whole arrangement exists to prevent, so it is refused at boot rather than at runtime,
 * loudly rather than quietly, and before a single request is served.
 */
class NoIdentityProviderException extends QuillstackException
{
    //
}
