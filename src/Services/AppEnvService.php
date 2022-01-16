<?php

declare(strict_types=1);

namespace Quillstack\Framework\Services;

use Quillstack\Framework\Exceptions\ArgumentTypeNotAllowedException;

class AppEnvService
{
    public const ENV_PRODUCTION = 'production';
    public const ENV_DEVELOP = 'develop';
    public const ENV_TESTING = 'testing';

    public function env(): string
    {
        return env('APP_ENV');
    }

    public function isProduction(): bool
    {
        return $this->isEnv(self::ENV_PRODUCTION);
    }

    public function isDevelop(): bool
    {
        return $this->isEnv(self::ENV_DEVELOP);
    }

    public function isTesting(): bool
    {
        return $this->isEnv(self::ENV_TESTING);
    }

    public function isEnv($env): bool
    {
        if (is_string($env)) {
            return $this->env() === $env;
        }

        if (is_array($env)) {
            return in_array($this->env(), $env, true);
        }

        throw new ArgumentTypeNotAllowedException('Parameter must be string or array');
    }
}
