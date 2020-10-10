<?php

declare(strict_types=1);

namespace QuillStack\Framework\Interfaces;

use QuillStack\Http\Response\ResponseInterface;

interface ControllerInterface
{
    /**
     * @return ResponseInterface
     */
    public function handle(): ResponseInterface;
}
