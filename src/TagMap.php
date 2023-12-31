<?php
namespace Apie\ServiceProviderGenerator;

use Apie\ServiceProviderGenerator\Events\SymfonyEventSubscriberAdapter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
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

    /**
     * Needs to be called in Laravel in the boot() method to avoid out of order of execution issues
     * with integration tests.
     */
    public static function registerEvents(Container $application): void
    {
        $hash = spl_object_hash($application);
        foreach (self::$mapping[$hash] ?? [] as $serviceId => $tagData) {
            foreach ($tagData as $tag) {
                $tagName = is_array($tag) ? $tag['name'] ?? null : $tag;
            
                if ($tagName === 'kernel.event_subscriber') {
                    $application->extend('events', function (Dispatcher $dispatcher, $app) use ($serviceId) {
                        $dispatcher->subscribe(new SymfonyEventSubscriberAdapter($app, $serviceId));
                        return $dispatcher;
                    });
                } 
            }
        }
    }

    public static function register(Container $application, string $serviceId, array $tags)
    {
        $hash = spl_object_hash($application);
        self::$mapping[$hash][$serviceId] = $tags;
    }

    /**
     * @return string[]
     */
    public static function getServiceIdsWithTag(Container $application, string $tagName): array
    {
        $hash = spl_object_hash($application);
        $serviceIds = [];
        foreach (self::$mapping[$hash] ?? [] as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if ($tag === $tagName || (($tag['name'] ?? null) === $tagName)) {
                    $serviceIds[] = $serviceId;
                }
            }
        }
        return $serviceIds;
    }

    public static function createServiceLocator(Container $application, string $tagName): ServiceLocator
    {
        $hash = spl_object_hash($application);
        $serviceMap = [];
        foreach (self::$mapping[$hash] ?? [] as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if ($tag === $tagName || (($tag['name'] ?? null) === $tagName)) {
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