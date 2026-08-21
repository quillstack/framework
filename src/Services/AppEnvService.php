<?php

declare(strict_types=1);

namespace Quillstack\Framework\Services;

class AppEnvService
{
    public const ENV_PRODUCTION = 'production';
    public const ENV_DEVELOP = 'develop';
    public const ENV_TESTING = 'testing';

    /**
     * Which environment the application is running in. An application which never said
     * counts as production: not saying so must not be what turns the internals on.
     */
    public function env(): string
    {
        $env = env('APP_ENV', self::ENV_PRODUCTION);

        return is_string($env) ? $env : self::ENV_PRODUCTION;
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

    /**
     * @param string|string[] $env
     */
    public function isEnv(string|array $env): bool
    {
        if (is_string($env)) {
            return $this->env() === $env;
        }

        return in_array($this->env(), $env, true);
    }
}
