<?php
namespace Apie\ServiceProviderGenerator;

use Apie\ServiceProviderGenerator\Events\SymfonyEventSubscriberAdapter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Event;
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
        foreach ($tags as $tag) {
            $tagName = is_array($tag) ? $tag['name'] ?? null : $tag;
            if ($tagName === 'kernel.event_subscriber') {
                $application->get('events')->subscribe(new SymfonyEventSubscriberAdapter($application, $serviceId));
            } 
        }
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