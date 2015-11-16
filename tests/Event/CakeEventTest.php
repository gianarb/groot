<?php

namespace PennyTest\Event;

use Exception;
use Penny\Event\CakeEvent;
use Penny\Route\RouteInfoInterface;
use PHPUnit_Framework_TestCase;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class CakeEventTest extends PHPUnit_Framework_TestCase
{
    /** @var CakeEvent */
    protected $event;

    protected function setUp()
    {
        $this->event = new CakeEvent('foo');
    }

    public function testGetName()
    {
        $this->assertEquals('foo', $this->event->getName());
    }

    public function testGetResponse()
    {
        $response = new Response();
        $this->event->setResponse($response);

        $this->assertInstanceOf(Response::class, $this->event->getResponse());
    }

    public function testGetRequest()
    {
        $request = (new Request())
        ->withUri(new Uri('/'))
        ->withMethod('GET');
        $this->event->setRequest($request);

        $this->assertInstanceOf(Request::class, $this->event->getRequest());
    }

    public function testSetGetRouteInfo()
    {
        $routeInfo = $this->prophesize(RouteInfoInterface::class);
        $this->event->setRouteInfo($routeInfo->reveal());

        $this->assertInstanceOf(RouteInfoInterface::class, $this->event->getRouteInfo());
    }

    public function testSetGetException()
    {
        $exception = new Exception();
        $this->event->setException($exception);
        $this->assertSame($exception, $this->event->getException());
    }

    public function testStopPropagation()
    {
        $this->event->stopPropagation(false);
        $this->assertFalse($this->event->isStopped());
    }
}
