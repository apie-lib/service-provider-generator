<?php
namespace Apie\ServiceProviderGenerator;

use Illuminate\Support\Env;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\DependencyInjection\Argument\ServiceLocator;

/**
 * @property-read Container $app
 */
trait UseGeneratedMethods
{
    protected function parseArgument(string $argument): mixed {
        if (preg_match('/^%[^%]+%$/', $argument)) {
            $configKey = substr(substr($argument, 1), 0, -1);
            return $this->app->make('config')->get($configKey);
        }
        return preg_replace_callback('/%([^%]+)?%/', function (array $match) {
            if (empty($match[1])) {
                return '%';
            }
            if (preg_match('/env\(([^\)])\)/', $match[1], $matches)) {
                return Env::get(
                    $this->parseArgument('%' . $matches[1] . '%'),
                    $this->app->make('config')->get($match[1])
                );
            }
            return $this->app->make('config')->get($match[1]);
        }, $argument);
    }

    protected function getTaggedServicesServiceLocator(string $tag): ServiceLocator {
        return TagMap::createServiceLocator($app, $tag);
    }

    protected function getTaggedServicesIterator(string $tag): array {
        return iterator_to_array($this->app->tagged($tag));
    }
}