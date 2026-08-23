<?php

declare(strict_types=1);

namespace Quillstack\Framework\Http\Responses;

use Quillstack\Response\Response;
use Quillstack\Serializer\Serializer;

/**
 * A response which carries an object rather than a written-out array.
 *
 * `send()` is a list of fields somebody typed, which makes it a place a field can be
 * forgotten: rename a property and the response still compiles, still answers, and quietly
 * stops carrying it. Handing over the object instead means the answer is decided by what the
 * object says may go over the wire, in one place, next to the field itself.
 */
abstract class SerializedResponse extends Response
{
    /**
     * @var object|iterable<int, object>|null
     */
    private object|iterable|null $data = null;

    /**
     * What goes back. One object, or a list of them.
     *
     * @param object|iterable<int, object> $data
     */
    public function with(object|iterable $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * The audiences this response serves; empty means the fields exposed to everybody.
     *
     * A response for administrators says so here, and every field marked for them appears
     * without anything else in the application having to know which those are.
     *
     * @return string[]
     */
    protected function groups(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    public function send(): array
    {
        if ($this->data === null) {
            return [];
        }

        // Built here rather than injected: it holds nothing but the list of groups, and a
        // response which cannot be made without a container is one you have to read the
        // container's documentation to write.
        $serializer = new Serializer($this->groups());

        return is_object($this->data)
            ? $serializer->toArray($this->data)
            : $serializer->toArrays($this->data);
    }
}
