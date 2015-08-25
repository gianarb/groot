<?php

namespace GianArb\Penny;

use DI\ContainerBuilder;
use GianArb\Penny\Config\Loader;
use GianArb\Penny\Event\HttpFlowEvent;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

class App
{
    /**
     * [$container description]
     *
     * @var [type]
     */
    private $container;

    /**
     * [$request description]
     *
     * @var [type]
     */
    private $request;

    /**
     * [$response description]
     *
     * @var [type]
     */
    private $response;

    /**
     * [__construct description]
     *
     * @param [type] $router    [description]
     * @param [type] $container [description]
     */
    public function __construct($router = null, $container = null)
    {
        $this->container = $container;

        $this->response = new \Zend\Diactoros\Response();

        $this->request = \Zend\Diactoros\ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );

        if ($this->container == null) {
            $config = Loader::load();
            $container = $this->buildContainer($config);
        }

        if ($router == null && $container->has("router") == false) {
            throw new \Exception("Define router config");
        } elseif ($container->has("router") == false) {
            $container->set("router", $router);
        }

        $container->set("http.flow", \DI\object('Zend\EventManager\EventManager'));
        $container->set(
            'dispatcher',
            \DI\object('GianArb\Penny\Dispatcher')->constructor($container->get("router"))
        );
        $container->set('di', $container);
        $this->container = $container;
    }

    /**
     * [buildContainer description]
     *
     * @param [type] $config [description]
     *
     * @return [type] [description]
     */
    private function buildContainer($config)
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addDefinitions($config);

        return $builder->build();
    }

    /**
     * [getContainer description]
     *
     * @return [type] [description]
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * [run description]
     *
     * @param RequestInterface|null  $request  [description]
     * @param ResponseInterface|null $response [description]
     *
     * @return [type] [description]
     */
    public function run(RequestInterface $request = null, ResponseInterface $response = null)
    {
        ($request != null) ?: $request = $this->request;
        ($response != null) ?: $response = $this->response;
        $event = new HttpFlowEvent("bootstrap", $request, $response);

        try {
            $routerInfo = $this->getContainer()->get("dispatcher")
                ->dispatch($request);
        } catch (\Exception $e) {
            $event->setName("ERROR_DISPATCH");
            $event->setException($e);
            $this->getContainer()->get("http.flow")->trigger($event);

            return $event->getResponse();
        }

        $controller = $this->getContainer()->get($routerInfo[1][0]);
        $method = $routerInfo[1][1];
        $function = new \ReflectionClass($controller);
        $name = strtolower($function->getShortName());

        $eventName = "{$name}.{$method}";
        $event->setName($eventName);
        $event->setRouteInfo($routerInfo);

        $this->getContainer()->get("http.flow")->attach($eventName, function ($event) use ($controller, $method) {
            $args = [
                $event->getRequest(),
                $event->getResponse(),
            ];

            foreach ($event->getRouteInfo()[2] as $v) {
                $args[] = $v;
            }

            $response = call_user_func_array([$controller, $method], $args);
            $event->setResponse($response);
        }, 0);

        try {
            $this->getContainer()->get("http.flow")->trigger($event);
        } catch (\Exception $exception) {
            $event->setName($eventName."_error");
            $event->setException($exception);
            $this->getContainer()->get("http.flow")->trigger($event);
        }

        return $event->getResponse();
    }
}
