<?php
namespace Apie\ServiceProviderGenerator;

use Illuminate\Support\Env;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\DependencyInjection\Argument\ServiceLocator;
use UnexpectedValueException;

/**
 * @property-read Container $app
 */
trait UseGeneratedMethods
{
    private function getConfigKey(string $input): mixed
    {
        if (str_starts_with($input, 'kernel.')) {
            return $this->getKernelParam($input);
        }
        return $this->app->make('config')->get($input);
    }

    private function handleDefault(mixed $default, ?string $class, ?int $argumentNumber): mixed
    {
        if ($default !== null || $class === null || $argumentNumber === null) {
            return $default;
        }
        
        if (class_exists($class) && method_exists($class, '__construct')) {
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            if ($constructor && $constructor->getNumberOfParameters() > $argumentNumber) {
                $parameter = $constructor->getParameters()[$argumentNumber];
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
            }
        }

        return $default;
    }

    protected function parseArgument(string $argument, ?string $class, ?int $argumentNumber): mixed {
        if (preg_match('/^%[^%]+%$/', $argument)) {
            return $this->handleDefault(
                $this->getConfigKey(substr(substr($argument, 1), 0, -1)),
                $class,
                $argumentNumber
            );
        }
        return $this->handleDefault(
            preg_replace_callback('/%([^%]+)?%/', function (array $match) {
                if (empty($match[1])) {
                    return '%';
                }
                if (preg_match('/env\(([^\)])\)/', $match[1], $matches)) {
                    return Env::get(
                        $this->parseArgument('%' . $matches[1] . '%'),
                        $this->getConfigKey($match[1])
                    );
                }
                return $this->getConfigKey($match[1]);
            }, $argument),
            $class,
            $argumentNumber
        );
    }

    protected function getKernelParam(string $kernelParam): mixed {
        return match ($kernelParam) {
            'kernel.cache_dir' => storage_path('cache'),
            'kernel.debug' => (bool) ($this->app->make('config')->get('app.debug')),
            default => throw new UnexpectedValueException('Unexpected value: "' . $kernelParam . '"')
        };
    }

    protected function getTaggedServicesServiceLocator(string $tag): ServiceLocator {
        return TagMap::createServiceLocator($this->app, $tag);
    }

    protected function getTaggedServicesIterator(string $tag): array {
        $list = $this->app->tagged($tag);

        return is_array($list) ? $list : iterator_to_array($list);
    }
}