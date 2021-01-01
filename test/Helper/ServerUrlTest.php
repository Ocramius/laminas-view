<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\View\Helper;

use Laminas\View\Helper;
use PHPUnit\Framework\TestCase;

/**
 * Tests Laminas_View_Helper_ServerUrl
 *
 * @group      Laminas_View
 * @group      Laminas_View_Helper
 */
class ServerUrlTest extends TestCase
{
    /**
     * Back up of $_SERVER
     *
     * @var array
     */
    protected $serverBackup;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        unset($_SERVER['HTTPS']);
        unset($_SERVER['SERVER_PORT']);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testConstructorWithOnlyHost()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com', $url->__invoke());
    }

    public function testConstructorWithOnlyHostIncludingPort()
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8000';

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com:8000', $url->__invoke());
    }

    public function testConstructorWithHostAndHttpsOn()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTPS']     = 'on';

        $url = new Helper\ServerUrl();
        $this->assertEquals('https://example.com', $url->__invoke());
    }

    public function testConstructorWithHostAndHttpsTrue()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTPS'] = true;

        $url = new Helper\ServerUrl();
        $this->assertEquals('https://example.com', $url->__invoke());
    }

    public function testConstructorWithHostIncludingPortAndHttpsTrue()
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8181';
        $_SERVER['HTTPS'] = true;

        $url = new Helper\ServerUrl();
        $this->assertEquals('https://example.com:8181', $url->__invoke());
    }

    public function testConstructorWithHostReversedProxyHttpsTrue()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['SERVER_PORT'] = 80;

        $url = new Helper\ServerUrl();
        $this->assertEquals('https://example.com', $url->__invoke());
    }

    public function testConstructorWithHttpHostIncludingPortAndPortSet()
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8181';
        $_SERVER['SERVER_PORT'] = 8181;

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com:8181', $url->__invoke());
    }

    public function testConstructorWithHttpHostAndServerNameAndPortSet()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = 8080;

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com:8080', $url->__invoke());
    }

    public function testConstructorWithNoHttpHostButServerNameAndPortSet()
    {
        unset($_SERVER['HTTP_HOST']);
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = 8080;

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.org:8080', $url->__invoke());
    }

    public function testServerUrlWithTrueParam()
    {
        $_SERVER['HTTPS']       = 'off';
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/foo.html';

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com/foo.html', $url->__invoke(true));
    }

    public function testServerUrlWithInteger()
    {
        $_SERVER['HTTPS']     = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/foo.html';

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com', $url->__invoke(1337));
    }

    public function testServerUrlWithObject()
    {
        $_SERVER['HTTPS']     = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/foo.html';

        $url = new Helper\ServerUrl();
        $this->assertEquals('http://example.com', $url->__invoke(new \stdClass()));
    }

    /**
     * @group Laminas-9919
     */
    public function testServerUrlWithScheme()
    {
        $_SERVER['HTTP_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $url = new Helper\ServerUrl();
        $this->assertEquals('https://example.com', $url->__invoke());
    }

    /**
     * @group Laminas-9919
     */
    public function testServerUrlWithPort()
    {
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['HTTP_HOST'] = 'example.com';
        $url = new Helper\ServerUrl();
        $this->assertEquals('https://example.com', $url->__invoke());
    }

    /**
     * @group Laminas-508
     */
    public function testServerUrlWithProxy()
    {
        $_SERVER['HTTP_HOST'] = 'proxyserver.com';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'www.firsthost.org';
        $url = new Helper\ServerUrl();
        $url->setUseProxy(true);
        $this->assertEquals('http://www.firsthost.org', $url->__invoke());
    }

    /**
     * @group Laminas-508
     */
    public function testServerUrlWithMultipleProxies()
    {
        $_SERVER['HTTP_HOST'] = 'proxyserver.com';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'www.firsthost.org, www.secondhost.org';
        $url = new Helper\ServerUrl();
        $url->setUseProxy(true);
        $this->assertEquals('http://www.secondhost.org', $url->__invoke());
    }

    public function testDoesNotUseProxyByDefault()
    {
        $_SERVER['HTTP_HOST'] = 'proxyserver.com';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'www.firsthost.org, www.secondhost.org';
        $url = new Helper\ServerUrl();
        $this->assertEquals('http://proxyserver.com', $url->__invoke());
    }

    public function testCanUseXForwardedPortIfProvided()
    {
        $_SERVER['HTTP_HOST'] = 'proxyserver.com';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'www.firsthost.org, www.secondhost.org';
        $_SERVER['HTTP_X_FORWARDED_PORT'] = '8888';
        $url = new Helper\ServerUrl();
        $url->setUseProxy(true);
        $this->assertEquals('http://www.secondhost.org:8888', $url->__invoke());
    }

    public function testUsesHostHeaderWhenPortForwardingDetected()
    {
        $_SERVER['HTTP_HOST'] = 'localhost:10088';
        $_SERVER['SERVER_PORT'] = 10081;
        $url = new Helper\ServerUrl();
        $this->assertEquals('http://localhost:10088', $url->__invoke());
    }
}
