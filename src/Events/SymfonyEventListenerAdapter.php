<?php
namespace Apie\ServiceProviderGenerator\Events;

use Illuminate\Events\Dispatcher;
use Psr\Container\ContainerInterface;

class SymfonyEventListenerAdapter
{
    public function __construct(
        private readonly object $service,
        private readonly array|string $callback
    ) {
    }

    public function __invoke(object $event): mixed
    {
        $callback = $this->callback;
        if (is_string($callback)) {
            return $this->service->$callback($event);
        }
        if (count($callback) === 2) {
            return call_user_func($callback, $event);
        }
        throw new \LogicException('I do not understand ' . var_export($event, true));
    }
}