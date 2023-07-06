<?php
namespace Apie\ServiceProviderGenerator;

use Illuminate\Contracts\Container\Container;
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

    public static function unregister(Container $application)
    {
        $hash = spl_object_hash($application);
        unset(self::$mapping[$hash]);
    }

    public static function register(Container $application, string $serviceId, array $tags)
    {
        $hash = spl_object_hash($application);
        self::$mapping[$hash][$serviceId] = $tags;
    }

    public static function createServiceLocator(Container $application, string $tagName): ServiceLocator
    {
        $hash = spl_object_hash($application);
        $serviceMap = [];
        foreach (self::$mapping[$hash] ?? [] as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if ($tag === $tagName || ($tag['name'] ?? null === $tagName)) {
                    $serviceMap[$serviceId] = [$serviceId, $application];
                }
            }
        }
        return new ServiceLocator(
            function (string $serviceId, Container $application) {
                return $application->get($serviceId);
            },
            $serviceMap
        );
    }
}