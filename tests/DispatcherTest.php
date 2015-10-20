<?php

namespace PennyTest;

use FastRoute;
use Penny\Dispatcher;
use PHPUnit_Framework_TestCase;
use Zend\Diactoros\Request;
use Zend\Diactoros\Uri;
use Penny\Container\PHPDiFactory;

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    private $router;

    public function setUp()
    {
        $this->router = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $manager) {
            $manager->addRoute('GET', '/', ['TestApp\Controller\IndexController', 'index'], [
                'name' => 'index',
            ]);
            $manager->addRoute('GET', '/fail', ['TestApp\Controller\IndexController', 'none'], [
                'name' => 'fail',
            ]);
            $manager->addRoute('GET', '/dummy', ['TestApp\Controller\IndexController', 'dummy'], [
                'name' => 'dummy',
            ]);
        });
    }

    public function testSetRouter()
    {
        $dispatcher = new Dispatcher($this->router, PHPDiFactory::buildContainer());
        $this->assertInstanceof("FastRoute\Dispatcher\GroupCountBased", \PHPUnit_Framework_Assert::readAttribute($dispatcher, 'router'));
    }

    public function testDispatchRouteNotFoundRequest()
    {
        $this->setExpectedException('Penny\Exception\RouteNotFoundException');
        $request = (new Request())
        ->withUri(new Uri('/doh'))
        ->withMethod('GET');

        $dispatcher = new Dispatcher($this->router, PHPDiFactory::buildContainer());
        $dispatcher($request);
    }

    public function testDispatchMethodNotAllowedRequest()
    {
        $this->setExpectedException('Penny\Exception\MethodNotAllowedException');
        $request = (new Request())
        ->withUri(new Uri('/'))
        ->withMethod('POST');

        $dispatcher = new Dispatcher($this->router, PHPDiFactory::buildContainer());
        $dispatcher($request);
    }

    public function testDispatchGot500Exception()
    {
        $this->setExpectedException('Exception');

        $router = $this->prophesize('FastRoute\Dispatcher', PHPDiFactory::buildContainer());
        $container = $this->prophesize('Interop\Container\ContainerInterface', PHPDiFactory::buildContainer());
        $request = (new Request())
        ->withUri(new Uri('/'))
        ->withMethod('POST');

        $router->dispatch('POST', '/')->willReturn([
            0 => 3,
        ])->shouldBeCalled();

        $dispatcher = new Dispatcher($router->reveal(), $container->reveal());
        $dispatcher($request);
    }
}
