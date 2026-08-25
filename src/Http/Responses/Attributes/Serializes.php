<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses\Attributes;

use Attribute;

/**
 * What a response carries.
 *
 * A `SerializedResponse` learns which object it is answering with when somebody hands it one,
 * which is too late for anything that wants to describe the API without running it. Saying so
 * on the class is what lets the OpenAPI document be generated from the code rather than
 * written beside it and forgotten.
 *
 * It is enforced rather than merely read: `with()` refuses an object of any other type. A
 * declaration which only documents drifts from the code the first time somebody changes one
 * and not the other, and then it is worse than nothing, because it is believed.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Serializes
{
    /**
     * @param class-string $class
     */
    public function __construct(public readonly string $class)
    {
        //
    }
}
