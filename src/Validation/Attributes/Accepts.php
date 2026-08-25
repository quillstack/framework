<?php

declare(strict_types=1);

namespace Quillstack\Framework\Validation\Attributes;

use Attribute;

/**
 * What a request may hold, said on the method that handles it.
 *
 * The same rules written inside `handle()` are invisible to anything that is not running the
 * application, so an OpenAPI document describing the request body has to be written a second
 * time and kept in step by hand — which is to say, not kept in step.
 *
 * Said here they are read by the validator, so the rules that are documented are the rules
 * that ran. There is one list, and it is the one that decides.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Accepts
{
    /**
     * @param array<string, string[]> $rules the same shape `Validator::check()` takes
     */
    public function __construct(public readonly array $rules)
    {
        //
    }
}
