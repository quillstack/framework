<?php

declare(strict_types=1);

namespace Quillstack\Framework\Console\Commands;

use Psr\Container\ContainerInterface;
use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\Input;
use Quillstack\Framework\Interfaces\RouteProviderInterface;
use Quillstack\Framework\OpenApi\Generator;
use Quillstack\Output\OutputInterface;
use Quillstack\Router\Router;

/**
 * Writes the OpenAPI document for the application's routes.
 *
 * Nothing is described here that the application does not already say somewhere it runs, so
 * this can be run on every deploy and the answer is the API as it is rather than as it was
 * when somebody last remembered to write it down.
 */
class OpenApiCommand implements CommandInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'openapi:generate';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Writes the OpenAPI document for the routes there are';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        $router = new Router();

        /** @var RouteProviderInterface $routes */
        $routes = $this->container->get(RouteProviderInterface::class);
        $routes->setRoutes($router);

        $server = $input->getOption('server');

        $document = (new Generator(
            $router,
            (string) $input->getOption('title', 'API'),
            (string) $input->getOption('api-version', '1.0.0'),
            is_string($server) && $server !== '' ? [$server] : []
        ))->generate();

        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!is_string($json)) {
            $output->writeln('<red>The document could not be written as JSON.</red>');

            return 1;
        }

        $out = $input->getOption('out');
        $path = is_string($out) ? $out : '';

        if ($path === '') {
            $output->writeln($json);

            return 0;
        }

        if (file_put_contents($path, $json . "\n") === false) {
            $output->writeln("<red>Could not write to {$path}</red>");

            return 1;
        }

        $paths = is_array($document['paths']) ? count($document['paths']) : 0;
        $output->writeln("Wrote <yellow>{$paths}</yellow> paths to <yellow>{$path}</yellow>");

        return 0;
    }
}
