<?php

namespace Tests\OlegV\Assets;

use InvalidArgumentException;
use OlegV\Assets\AbstractAssetRenderer;
use PHPUnit\Framework\TestCase;

class TestAssetRenderer extends AbstractAssetRenderer
{
    public function renderCss(array $cssAssets): string
    {
        return '';
    }

    public function renderJs(array $jsAssets): string
    {
        return '';
    }

    // Публичные методы для тестирования protected методов
    public function testProcessCssAssets(array $cssAssets): array
    {
        return $this->processCssAssets($cssAssets);
    }

    public function testProcessJsAssets(array $jsAssets): array
    {
        return $this->processJsAssets($jsAssets);
    }

    public function testGetComponentId(string $className): string
    {
        return $this->getComponentId($className);
    }
}

class AbstractAssetRendererTest extends TestCase
{
    private TestAssetRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new TestAssetRenderer();
    }

    public function testProcessCssAssetsWithModeMultiple(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $cssAssets = [
            'App\Components\PrimaryButton' => '.btn-primary { color: red; }',
            'App\Components\SecondaryButton' => '.btn-secondary { color: blue; }',
            'App\Components\EmptyComponent' => '', // Пустой CSS
        ];

        $result = $this->renderer->testProcessCssAssets($cssAssets);

        $this->assertCount(2, $result); // Должны быть только непустые компоненты
        $this->assertArrayHasKey('primary-button', $result);
        $this->assertArrayHasKey('secondary-button', $result);
        $this->assertArrayNotHasKey('empty-component', $result);

        $this->assertEquals('.btn-primary { color: red; }', $result['primary-button']);
        $this->assertEquals('.btn-secondary { color: blue; }', $result['secondary-button']);
    }

    public function testProcessCssAssetsWithModeMultipleAndMinify(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);
        $this->renderer->setMinify(true);

        $cssAssets = [
            'App\Components\TestComponent' => "
                /* Комментарий */
                .test {
                    color: red;
                    margin: 10px;
                }
            ",
        ];

        $result = $this->renderer->testProcessCssAssets($cssAssets);

        $this->assertArrayHasKey('test-component', $result);
        $this->assertEquals('.test{color:red;margin:10px}', $result['test-component']);
    }

    public function testProcessJsAssetsWithModeMultiple(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $jsAssets = [
            'App\Components\ModalWindow' => "console.log('Modal');",
            'App\Components\Tooltip' => "console.log('Tooltip');",
            'App\Components\EmptyComponent' => '', // Пустой JS
        ];

        $result = $this->renderer->testProcessJsAssets($jsAssets);

        $this->assertCount(2, $result); // Должны быть только непустые компоненты
        $this->assertArrayHasKey('modal-window', $result);
        $this->assertArrayHasKey('tooltip', $result);
        $this->assertArrayNotHasKey('empty-component', $result);

        $this->assertEquals("console.log('Modal');", $result['modal-window']);
        $this->assertEquals("console.log('Tooltip');", $result['tooltip']);
    }

    public function testProcessJsAssetsWithModeMultipleAndMinify(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);
        $this->renderer->setMinify(true);

        $jsAssets = [
            'App\Components\TestComponent' => "
                // Однострочный комментарий
                /* 
                   Многострочный
                   комментарий 
                */
                function test() {
                    console.log('test');
                }
            ",
        ];

        $result = $this->renderer->testProcessJsAssets($jsAssets);

        $this->assertArrayHasKey('test-component', $result);
        $this->assertEquals("function test(){console.log('test');}", trim($result['test-component']));
    }

    public function testGetComponentId(): void
    {
        $testCases = [
            ['App\Components\PrimaryButton', 'primary-button'],
            ['App\Components\SecondaryButton', 'secondary-button'],
            ['App\UI\ModalWindow', 'modal-window'],
            ['App\UI\CustomModal', 'custom-modal'],
            ['SingleComponent', 'single-component'],
            ['TestComponent', 'test-component'],
            ['ABCComponent', 'a-b-c-component'], // Множественные заглавные буквы
        ];

        foreach ($testCases as [$className, $expectedId]) {
            $this->assertEquals($expectedId, $this->renderer->testGetComponentId($className));
        }
    }

    public function testSetMode(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);
        $this->assertEquals(AbstractAssetRenderer::MODE_MULTIPLE, $this->renderer->getMode());

        $this->renderer->setMode(AbstractAssetRenderer::MODE_SINGLE);
        $this->assertEquals(AbstractAssetRenderer::MODE_SINGLE, $this->renderer->getMode());
    }

    public function testSetInvalidModeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Неизвестный режим: invalid_mode');

        $this->renderer->setMode('invalid_mode');
    }

    public function testSetMinify(): void
    {
        $this->renderer->setMinify(true);
        $this->assertTrue($this->renderer->isMinify());

        $this->renderer->setMinify(false);
        $this->assertFalse($this->renderer->isMinify());
    }

    public function testGetMode(): void
    {
        // По умолчанию должен быть SINGLE
        $this->assertEquals(AbstractAssetRenderer::MODE_SINGLE, $this->renderer->getMode());

        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);
        $this->assertEquals(AbstractAssetRenderer::MODE_MULTIPLE, $this->renderer->getMode());
    }

    public function testIsMinify(): void
    {
        // По умолчанию должен быть false
        $this->assertFalse($this->renderer->isMinify());

        $this->renderer->setMinify(true);
        $this->assertTrue($this->renderer->isMinify());

        $this->renderer->setMinify(false);
        $this->assertFalse($this->renderer->isMinify());
    }

    public function testGetAvailableModes(): void
    {
        $availableModes = AbstractAssetRenderer::getAvailableModes();

        $this->assertArrayHasKey(AbstractAssetRenderer::MODE_SINGLE, $availableModes);
        $this->assertArrayHasKey(AbstractAssetRenderer::MODE_MULTIPLE, $availableModes);

        $this->assertEquals('Все компоненты в один файл', $availableModes[AbstractAssetRenderer::MODE_SINGLE]);
        $this->assertEquals('Каждый компонент в отдельный файл', $availableModes[AbstractAssetRenderer::MODE_MULTIPLE]);
    }

    public function testProcessCssAssetsWithEmptyArray(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $result = $this->renderer->testProcessCssAssets([]);

        $this->assertEmpty($result);
    }

    public function testProcessJsAssetsWithEmptyArray(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $result = $this->renderer->testProcessJsAssets([]);

        $this->assertEmpty($result);
    }

    public function testProcessCssAssetsSkipsEmptyContent(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $cssAssets = [
            'App\Components\Component1' => '   ', // Пробелы
            'App\Components\Component2' => "\n\t", // Переносы строк
            'App\Components\Component3' => '', // Пустая строка
            'App\Components\Component4' => '.valid { color: red; }',
        ];

        $result = $this->renderer->testProcessCssAssets($cssAssets);
        print_r($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('component4', $result);
        $this->assertArrayNotHasKey('component1', $result);
        $this->assertArrayNotHasKey('component2', $result);
        $this->assertArrayNotHasKey('component3', $result);
    }

    public function testProcessJsAssetsSkipsEmptyContent(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $jsAssets = [
            'App\Components\Component1' => '   ', // Пробелы
            'App\Components\Component2' => "\n\t", // Переносы строк
            'App\Components\Component3' => '', // Пустая строка
            'App\Components\Component4' => 'console.log("valid");',
        ];

        $result = $this->renderer->testProcessJsAssets($jsAssets);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('component4', $result);
        $this->assertArrayNotHasKey('component1', $result);
        $this->assertArrayNotHasKey('component2', $result);
        $this->assertArrayNotHasKey('component3', $result);
    }

    public function testMinifyFlagAffectsProcessing(): void
    {
        $this->renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);

        $css = "
            .test {
                color: red;
                /* комментарий */
            }
        ";

        // Без минификации
        $this->renderer->setMinify(false);
        $resultNoMinify = $this->renderer->testProcessCssAssets(['Test' => $css]);

        // С минификацией
        $this->renderer->setMinify(true);
        $resultMinify = $this->renderer->testProcessCssAssets(['Test' => $css]);

        $this->assertNotEquals($resultNoMinify['test'], $resultMinify['test']);
        $this->assertStringNotContainsString('комментарий', $resultMinify['test']);
        $this->assertStringContainsString('комментарий', $resultNoMinify['test']);
    }
}