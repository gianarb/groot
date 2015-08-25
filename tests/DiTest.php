<?php
namespace GianArb\PennyTest;

use DI\ContainerBuilder;
use FastRoute\RouteCollector;
use FastRoute\simpleDispatcher;
use GianArb\Penny\App;

class DiTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $router;

    public function setUp()
    {
        $this->router = simpleDispatcher(function (RouteCollector $router) {
            $router->addRoute('GET', '/', ['TestApp\Controller\Index', 'diTest']);
        });


        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $dic = $builder->build();

        $this->container = $dic;

    }

    public function testInjectionHttpFlow()
    {
        $this->container->set("troyan", "call me");
        $app = new App($this->router, $this->container);

        $request = (new Request())
        ->withUri(new Uri('/'))
        ->withMethod("GET");
        $response = new Response();

        $response = $app->run($request, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("call me", $response->getBody()->__toString());
    }
}
