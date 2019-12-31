<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\View\Renderer;

use ArrayObject;
use Laminas\View\Exception;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\JsonRenderer;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @group      Laminas_View
 */
class JsonRendererTest extends TestCase
{
    /**
     * @var JsonRenderer
     */
    protected $renderer;

    public function setUp()
    {
        $this->renderer = new JsonRenderer();
    }

    public function testRendersViewModelsWithoutChildren()
    {
        $model = new ViewModel(['foo' => 'bar']);
        $test  = $this->renderer->render($model);
        $this->assertEquals(json_encode(['foo' => 'bar']), $test);
    }

    public function testRendersViewModelsWithChildrenUsingCaptureToValue()
    {
        $root   = new ViewModel(['foo' => 'bar']);
        $child1 = new ViewModel(['foo' => 'bar']);
        $child2 = new ViewModel(['foo' => 'bar']);
        $child1->setCaptureTo('child1');
        $child2->setCaptureTo('child2');
        $root->addChild($child1)
             ->addChild($child2);

        $expected = [
            'foo' => 'bar',
            'child1' => [
                'foo' => 'bar',
            ],
            'child2' => [
                'foo' => 'bar',
            ],
        ];
        $test  = $this->renderer->render($root);
        $this->assertEquals(json_encode($expected), $test);
    }

    public function testThrowsAwayChildModelsWithoutCaptureToValueByDefault()
    {
        $root   = new ViewModel(['foo' => 'bar']);
        $child1 = new ViewModel(['foo' => 'baz']);
        $child2 = new ViewModel(['foo' => 'bar']);
        $child1->setCaptureTo(false);
        $child2->setCaptureTo('child2');
        $root->addChild($child1)
             ->addChild($child2);

        $expected = [
            'foo' => 'bar',
            'child2' => [
                'foo' => 'bar',
            ],
        ];
        $test  = $this->renderer->render($root);
        $this->assertEquals(json_encode($expected), $test);
    }

    public function testCanMergeChildModelsWithoutCaptureToValues()
    {
        $this->renderer->setMergeUnnamedChildren(true);
        $root   = new ViewModel(['foo' => 'bar']);
        $child1 = new ViewModel(['foo' => 'baz']);
        $child2 = new ViewModel(['foo' => 'bar']);
        $child1->setCaptureTo(false);
        $child2->setCaptureTo('child2');
        $root->addChild($child1)
             ->addChild($child2);

        $expected = [
            'foo' => 'baz',
            'child2' => [
                'foo' => 'bar',
            ],
        ];
        $test  = $this->renderer->render($root);
        $this->assertEquals(json_encode($expected), $test);
    }

    public function getNonObjectModels()
    {
        return [
            ['string'],
            [1],
            [1.0],
            [['foo', 'bar']],
            [['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider getNonObjectModels
     */
    public function testRendersNonObjectModelAsJson($model)
    {
        $expected = json_encode($model);
        $test     = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testRendersJsonSerializableModelsAsJson()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Can only test JsonSerializable models in PHP 5.4.0 and up');
        }
        $model        = new TestAsset\JsonModel;
        $model->value = ['foo' => 'bar'];
        $expected     = json_encode($model->value);
        $test         = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testRendersTraversableObjectsAsJsonObjects()
    {
        $model = new ArrayObject([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        $expected     = json_encode($model->getArrayCopy());
        $test         = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testRendersNonTraversableNonJsonSerializableObjectsAsJsonObjects()
    {
        $model      = new stdClass;
        $model->foo = 'bar';
        $model->bar = 'baz';
        $expected   = json_encode(get_object_vars($model));
        $test       = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testNonViewModelInitialArgumentWithValuesRaisesException()
    {
        $this->expectException(Exception\DomainException::class);
        $this->renderer->render('foo', ['bar' => 'baz']);
    }

    public function testRendersTreesOfViewModelsByDefault()
    {
        $this->assertTrue($this->renderer->canRenderTrees());
    }

    public function testSetHasJsonpCallback()
    {
        $this->assertFalse($this->renderer->hasJsonpCallback());
        $this->renderer->setJsonpCallback(0);
        $this->assertFalse($this->renderer->hasJsonpCallback());
        $this->renderer->setJsonpCallback('callback');
        $this->assertTrue($this->renderer->hasJsonpCallback());
    }

    public function testRendersViewModelsWithoutChildrenWithJsonpCallback()
    {
        $model = new ViewModel(['foo' => 'bar']);
        $this->renderer->setJsonpCallback('callback');
        $test = $this->renderer->render($model);
        $expected = 'callback(' . json_encode(['foo' => 'bar']) . ');';
        $this->assertEquals($expected, $test);
    }

    /**
     * @dataProvider getNonObjectModels
     */
    public function testRendersNonObjectModelAsJsonWithJsonpCallback($model)
    {
        $expected = 'callback(' . json_encode($model) . ');';
        $this->renderer->setJsonpCallback('callback');
        $test = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testRendersJsonSerializableModelsAsJsonWithJsonpCallback()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('Can only test JsonSerializable models in PHP 5.4.0 and up');
        }
        $model        = new TestAsset\JsonModel;
        $model->value = ['foo' => 'bar'];
        $expected     = 'callback(' . json_encode($model->value) . ');';
        $this->renderer->setJsonpCallback('callback');
        $test         = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testRendersTraversableObjectsAsJsonObjectsWithJsonpCallback()
    {
        $model = new ArrayObject([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        $expected     = 'callback(' . json_encode($model->getArrayCopy()) . ');';
        $this->renderer->setJsonpCallback('callback');
        $test         = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    public function testRendersNonTraversableNonJsonSerializableObjectsAsJsonObjectsWithJsonpCallback()
    {
        $model      = new stdClass;
        $model->foo = 'bar';
        $model->bar = 'baz';
        $expected   = 'callback(' . json_encode(get_object_vars($model)) . ');';
        $this->renderer->setJsonpCallback('callback');
        $test       = $this->renderer->render($model);
        $this->assertEquals($expected, $test);
    }

    /**
     * @group 2463
     */
    public function testRecursesJsonModelChildrenWhenRendering()
    {
        $root   = new JsonModel(['foo' => 'bar']);
        $child1 = new JsonModel(['foo' => 'bar']);
        $child2 = new JsonModel(['foo' => 'bar']);
        $child1->setCaptureTo('child1');
        $child2->setCaptureTo('child2');
        $root->addChild($child1)
             ->addChild($child2);

        $expected = [
            'foo' => 'bar',
            'child1' => [
                'foo' => 'bar',
            ],
            'child2' => [
                'foo' => 'bar',
            ],
        ];
        $test  = $this->renderer->render($root);
        $this->assertEquals(json_encode($expected), $test);
    }
}
