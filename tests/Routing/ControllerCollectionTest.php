<?php declare(strict_types=1);

namespace Gmo\Web\Tests\Routing;

use Gmo\Web\Routing\ControllerCollection;
use PHPUnit\Framework\TestCase;
use Silex\Controller;
use Silex\Exception\ControllerFrozenException;
use Silex\Route;

class ControllerCollectionTest extends TestCase
{
    public function testGetRouteCollectionWithNoRoutes()
    {
        $controllers = $this->createCollection();
        $routes = $controllers->flush();
        $this->assertEquals(0, count($routes->all()));
    }

    public function testGetRouteCollectionWithRoutes()
    {
        $controllers = $this->createCollection();
        $controllers->match('/foo', function () {});
        $controllers->match('/bar', function () {});

        $routes = $controllers->flush();
        $this->assertEquals(2, count($routes->all()));
    }

    public function testControllerFreezing()
    {
        $controllers = $this->createCollection();

        $fooController = $controllers->match('/foo', function () {})->bind('foo');
        $barController = $controllers->match('/bar', function () {})->bind('bar');

        $controllers->flush();

        $e = null;
        try {
            $fooController->bind('foo2');
            $this->fail();
        } catch (ControllerFrozenException $e) {
        }
        $this->assertNotNull($e);

        try {
            $barController->bind('bar2');
            $this->fail();
        } catch (ControllerFrozenException $e) {
        }
        $this->assertNotNull($e);
    }

    public function testConflictingRouteNames()
    {
        $controllers = $this->createCollection();

        $mountedRootController = $controllers->match('/', function () {});

        $mainRootController = new Controller(new Route('/'));
        $mainRootController->bind($mainRootController->generateRouteName('main_'));

        $controllers->flush();

        $this->assertNotEquals($mainRootController->getRouteName(), $mountedRootController->getRouteName());
    }

    public function testUniqueGeneratedRouteNames()
    {
        $controllers = $this->createCollection();

        $controllers->match('/a-a', function () {});
        $controllers->match('/a_a', function () {});

        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(['_a_a', '_a_a_'], array_keys($routes->all()));
    }

    public function testUniqueGeneratedRouteNamesAmongMounts()
    {
        $controllers = $this->createCollection();

        $controllers->mount('/root-a', $rootA = $this->createCollection());
        $controllers->mount('/root_a', $rootB = $this->createCollection());

        $rootA->match('/leaf', function () {});
        $rootB->match('/leaf', function () {});

        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(['_root_a_leaf', '_root_a_leaf_'], array_keys($routes->all()));
    }

    public function testUniqueGeneratedRouteNamesAmongNestedMounts()
    {
        $controllers = $this->createCollection();

        $controllers->mount('/root-a', $rootA = $this->createCollection());
        $controllers->mount('/root_a', $rootB = $this->createCollection());

        $rootA->mount('/tree', $treeA = $this->createCollection());
        $rootB->mount('/tree', $treeB = $this->createCollection());

        $treeA->match('/leaf', function () {});
        $treeB->match('/leaf', function () {});

        $routes = $controllers->flush();

        $this->assertCount(2, $routes->all());
        $this->assertEquals(['_root_a_tree_leaf', '_root_a_tree_leaf_'], array_keys($routes->all()));
    }

    public function testAssert()
    {
        $controllers = $this->createCollection();
        $controllers->assert('id', '\d+');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->assert('name', '\w+')->assert('extra', '.*');
        $controllers->assert('extra', '\w+');

        $this->assertEquals('\d+', $controller->getRoute()->getRequirement('id'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('name'));
        $this->assertEquals('\w+', $controller->getRoute()->getRequirement('extra'));
    }

    public function testValue()
    {
        $controllers = $this->createCollection();
        $controllers->value('id', '1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->value('name', 'Fabien')->value('extra', 'Symfony');
        $controllers->value('extra', 'Twig');

        $this->assertEquals('1', $controller->getRoute()->getDefault('id'));
        $this->assertEquals('Fabien', $controller->getRoute()->getDefault('name'));
        $this->assertEquals('Twig', $controller->getRoute()->getDefault('extra'));
    }

    public function testConvert()
    {
        $controllers = $this->createCollection();
        $controllers->convert('id', '1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->convert('name', 'Fabien')->convert('extra', 'Symfony');
        $controllers->convert('extra', 'Twig');

        $this->assertEquals(['id' => '1', 'name' => 'Fabien', 'extra' => 'Twig'], $controller->getRoute()->getOption('_converters'));
    }

    public function testRequireHttp()
    {
        $controllers = $this->createCollection();
        $controllers->requireHttp();
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->requireHttps();

        $this->assertEquals(['https'], $controller->getRoute()->getSchemes());

        $controllers->requireHttp();

        $this->assertEquals(['http'], $controller->getRoute()->getSchemes());
    }

    public function testBefore()
    {
        $controllers = $this->createCollection();
        $controllers->before('mid1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->before('mid2');
        $controllers->before('mid3');

        $this->assertEquals(['mid1', 'mid2', 'mid3'], $controller->getRoute()->getOption('_before_middlewares'));
    }

    public function testAfter()
    {
        $controllers = $this->createCollection();
        $controllers->after('mid1');
        $controller = $controllers->match('/{id}/{name}/{extra}', function () {})->after('mid2');
        $controllers->after('mid3');

        $this->assertEquals(['mid1', 'mid2', 'mid3'], $controller->getRoute()->getOption('_after_middlewares'));
    }

    public function testRouteExtension()
    {
        $route = new MyRoute1();

        $controller = $this->createCollection($route);
        $controller->foo('foo');

        $this->assertEquals('foo', $route->foo);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testRouteMethodDoesNotExist()
    {
        $route = new MyRoute1();

        $controller = $this->createCollection($route);
        $controller->bar();
    }

    protected function createCollection($route = null)
    {
        return new ControllerCollection($route ?: new Route());
    }
}

class MyRoute1 extends Route
{
    public $foo;

    public function foo($value)
    {
        $this->foo = $value;
    }
}
