<?php
namespace GianArb\PennyTest\Config;

use GianArb\Penny\Config\Loader;
use PHPUnit_Framework_TestCase;

class LoaderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        chdir(dirname(__DIR__."/../../"));
    }

    public function testLoadsByDefaultPath()
    {
        $config = Loader::load();
        $this->assertSame(1, $config["one"]);
        $this->assertInstanceOf("StdClass", $config["two"]["class"]);
        $this->assertFalse($config["three"]);
    }

    public function testLoadsOverride()
    {
        $config = Loader::load("./config/custom/{{*}}{{,*.local}}.php");
        $this->assertSame("override", $config["one"]);
    }

    public function testLoadsByCustomPath()
    {
        $config = Loader::load("./config/custom/{{*}}{{,*.local}}.php");
        $this->assertTrue($config["nine"]);
    }
}
