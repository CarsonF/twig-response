<?php declare(strict_types=1);

namespace Gmo\Web\Tests\Routing;

use Gmo\Web\Routing\UrlMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class UrlMatcherTest extends TestCase
{
    /** @var RouteCollection */
    protected $routes;
    /** @var UrlMatcher */
    protected $matcher;

    protected function setUp()
    {
        $this->routes = new RouteCollection();
        $this->matcher = new UrlMatcher($this->routes, new RequestContext());
    }

    public function testMatch()
    {
        $this->routes->add('foo', new Route('/foo'));
        $this->routes->add('bar', new Route('/bar/'));

        $this->assertMatch('foo', '/foo');
        $this->assertMatch('foo', '/foo/');
        $this->assertMatch('bar', '/bar');
        $this->assertMatch('bar', '/bar/');
    }

    public function testMatchFailure()
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/derp".');
        $this->matcher->match('/derp');
    }

    public function testMatchFailureTrailingSlash()
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/derp".');
        $this->matcher->match('/derp/');
    }

    public function testTrailingSlashWithOptionalVariable()
    {
        $this->routes->add('foo', new Route('/foo/{bar}', [], ['bar' => '\d*']));

        $this->assertMatch('foo', '/foo', ['bar' => '']);
        $this->assertMatch('foo', '/foo/', ['bar' => '']);
        $this->assertMatch('foo', '/foo/123', ['bar' => '123']);
    }

    public function testSchemeRequirement()
    {
        $this->routes->add('foo', new Route('/foo', [], [], [], '', ['https']));
        $this->assertRedirect('https://localhost/foo', '/foo');
        $this->assertRedirect('https://localhost/foo', '/foo/');
    }

    public function testPrefixedRouteVariableOverridesBoundName()
    {
        $this->routes->add('asdf', new Route('/foo', ['_prefixed_route' => 'foo']));

        $this->assertMatch('foo', '/foo');
    }

    protected function assertMatch(string $route, string $path, array $variables = [])
    {
        $matched = $this->matcher->match($path);
        $this->assertInternalType('array', $matched);
        $this->assertEquals($route, $matched['_route']);
        unset($matched['_route']);
        $this->assertEquals($variables, $matched);
    }

    protected function assertRedirect(string $url, string $path)
    {
        $matched = $this->matcher->match($path);
        $this->assertNull($matched['_route']);
        $this->assertEquals($url, $matched['url']);
    }
}
