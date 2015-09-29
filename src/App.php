<?php

namespace GianArb\Penny;

use DI;
use Exception;
use GianArb\Penny\Config\Loader;
use GianArb\Penny\Event\HttpFlowEvent;
use ReflectionClass;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Interop\Container\ContainerInterface;

class App
{
    /**
     * Dependency Injection container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Representation of an outgoing, client-side request.
     *
     * @var mixed
     */
    private $request;

    /**
     * Representation of an outgoing, server-side response.
     *
     * @var mixed
     */
    private $response;

    /**
     * Application initialization.
     *
     * @param mixed              $router    Routing system.
     * @param ContainerInterface $container Dependency Injection container.
     *
     * @throws Exception If no router is defined.
     */
    public function __construct($router = null, ContainerInterface $container = null)
    {
        $this->container = $container ?: static::buildContainer(Loader::load());
        $container = &$this->container;

        $this->response = new Response();
        $this->request = ServerRequestFactory::fromGlobals();

        if ($router == null && $container->has('router') == false) {
            throw new Exception('Define router config');
        }

        if ($container->has('router') == false) {
            $container->set('router', $router);
        }

        $container->set('di', $container);
    }

    /**
     * Container compilation.
     *
     * @param mixed $config Configuration file/array.
     *
     * @link http://php-di.org/doc/php-definitions.html
     *
     * @return ContainerInterface
     */
    public static function buildContainer($config = [])
    {
        $builder = new DI\ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addDefinitions([
            "event_manager" =>  DI\object('Zend\EventManager\EventManager'),
            "dispatcher" => DI\object('GianArb\Penny\Dispatcher')
                ->constructor(DI\get('router')),
        ]);
        $builder->addDefinitions($config);

        return $builder->build();
    }

    /**
     * Container getter.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Application execution.
     *
     * @param mixed|null  $request  Representation of an outgoing, client-side request.
     * @param mixed|null $response Representation of an outgoing, server-side response.
     *
     * @return mixed
     */
    public function run($request = null, $response = null)
    {
        ($request != null) ?: $request = $this->request;
        ($response != null) ?: $response = $this->response;
        $event = new HttpFlowEvent('bootstrap', $request, $response);

        $container = $this->getContainer();
        $dispatcher = $container->get('dispatcher');
        $httpFlow = $container->get('event_manager');

        try {
            $routerInfo = $dispatcher->dispatch($request);
        } catch (Exception $exception) {
            $event->setName('ERROR_DISPATCH');
            $event->setException($exception);
            $httpFlow->trigger($event);

            return $event->getResponse();
        }

        $controller = $container->get($routerInfo[1][0]);
        $method = $routerInfo[1][1];
        $function = (new ReflectionClass($controller))->getShortName();

        $eventName = sprintf('%s.%s', strtolower($function), $method);
        $event->setName($eventName);
        $event->setRouteInfo($routerInfo);

        $httpFlow->attach($eventName, function ($event) use ($controller, $method) {
            $event->setResponse(call_user_func_array(
                [$controller, $method],
                [$event->getRequest(), $event->getResponse()] + $event->getRouteInfo()[2]
            ));
        }, 0);

        try {
            $httpFlow->trigger($event);
        } catch (Exception $exception) {
            $event->setName($eventName.'_error');
            $event->setException($exception);
            $httpFlow->trigger($event);
        }

        return $event->getResponse();
    }
}
