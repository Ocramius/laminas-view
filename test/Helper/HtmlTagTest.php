<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\View\Helper;

use Laminas\View\Helper\HtmlTag;
use Laminas\View\Renderer\PhpRenderer as View;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_View
 * @group      Laminas_View_Helper
 */
class HtmlTagTest extends TestCase
{
    /**
     * @var HtmlTag
     */
    public $helper;

    protected function setUp(): void
    {
        $this->view   = new View();
        $this->helper = new HtmlTag();
        $this->helper->setView($this->view);
    }

    protected function tearDown(): void
    {
        unset($this->helper);
    }

    protected function assertAttribute($name, $value = null)
    {
        $attributes = $this->helper->getAttributes();
        $this->assertArrayHasKey($name, $attributes);
        if ($value) {
            $this->assertEquals($value, $attributes[$name]);
        }
    }

    public function testSettingSingleAttribute()
    {
        $this->helper->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        $this->assertAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
    }

    public function testAddingMultipleAttributes()
    {
        $attribs = [
            'xmlns' => 'http://www.w3.org/1999/xhtml',
            'prefix' => 'og: http://ogp.me/ns#',
        ];
        $this->helper->setAttributes($attribs);

        foreach ($attribs as $name => $value) {
            $this->assertAttribute($name, $value);
        }
    }

    public function testSettingMultipleAttributesOverwritesExisting()
    {
        $this->helper->setAttribute('prefix', 'foobar');

        $attribs = [
            'xmlns' => 'http://www.w3.org/1999/xhtml',
            'prefix' => 'og: http://ogp.me/ns#',
        ];
        $this->helper->setAttributes($attribs);

        $this->assertCount(2, $this->helper->getAttributes());
        foreach ($attribs as $name => $value) {
            $this->assertAttribute($name, $value);
        }
    }

    public function testRenderingOpenTagWithNoAttributes()
    {
        $this->assertEquals('<html>', $this->helper->openTag());
    }

    public function testRenderingOpenTagWithAttributes()
    {
        $attribs = [
            'xmlns' => 'http://www.w3.org/1999/xhtml',
            'xmlns:og' => 'http://ogp.me/ns#',
        ];

        $this->helper->setAttributes($attribs);

        $tag = $this->helper->openTag();

        $this->assertStringStartsWith('<html', $tag);

        $escape = $this->view->plugin('escapehtmlattr');
        foreach ($attribs as $name => $value) {
            $this->assertStringContainsString(sprintf('%s="%s"', $name, $escape($value)), $tag);
        }
    }

    public function testRenderingCloseTag()
    {
        $this->assertEquals('</html>', $this->helper->closeTag());
    }

    public function testUseNamespacesSetter()
    {
        $this->helper->setUseNamespaces(true);
        $this->assertTrue($this->helper->getUseNamespaces());
    }

    public function testAppropriateNamespaceAttributesAreSetIfFlagIsOn()
    {
        $this->view->plugin('doctype')->setDoctype('xhtml');

        $attribs = [
            'prefix' => 'og: http://ogp.me/ns#',
        ];

        $this->helper->setUseNamespaces(true)->setAttributes($attribs);

        $tag = $this->helper->openTag();

        $escape = $this->view->plugin('escapehtmlattr');

        $this->assertStringContainsString(sprintf('%s="%s"', 'xmlns', $escape('http://www.w3.org/1999/xhtml')), $tag);
        foreach ($attribs as $name => $value) {
            $this->assertStringContainsString(sprintf('%s="%s"', $name, $escape($value)), $tag);
        }
    }
}
