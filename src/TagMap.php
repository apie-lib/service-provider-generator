<?php
namespace Apie\ServiceProviderGenerator;

use Illuminate\Contracts\Console\Application;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceLocator;

final class TagMap
{
    private static array $mapping = [];

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function unregister(Application&ContainerInterface $application)
    {
        $hash = spl_object_hash($application);
        unset(self::$mapping[$hash]);
    }

    public static function register(Application&ContainerInterface $application, string $serviceId, array $tags)
    {
        $hash = spl_object_hash($application);
        self::$mapping[$hash][$serviceId] = $tags;
    }

    public static function createServiceLocator(Application&ContainerInterface $application, string $tagName): ServiceLocator
    {
        $hash = spl_object_hash($application);
        $serviceTypes = [];
        foreach (self::$mapping[$hash] ?? [] as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if ($tag === $tagName || ($tag['name'] ?? null === $tagName)) {
                    $serviceTypes[] = $serviceId;
                }
            }
        }
        return new ServiceLocator(function (string $serviceId) use ($application) {
            return $application->get($serviceId);
        }, [], $serviceTypes);
    }
}