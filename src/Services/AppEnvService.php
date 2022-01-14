<?php

declare(strict_types=1);

namespace QuillStack\Framework\Services;

use QuillStack\Framework\Exceptions\ArgumentTypeNotAllowedException;

final class AppEnvService
{
    public const ENV_PRODUCTION = 'production';
    public const ENV_DEVELOP = 'develop';
    public const ENV_TESTING = 'testing';

    /**
     * @return string
     */
    public function env(): string
    {
        return env('APP_ENV');
    }

    /**
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->isEnv(self::ENV_PRODUCTION);
    }

    /**
     * @return bool
     */
    public function isDevelop(): bool
    {
        return $this->isEnv(self::ENV_DEVELOP);
    }

    /**
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->isEnv(self::ENV_TESTING);
    }

    /**
     * @param $env
     *
     * @return bool
     */
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
