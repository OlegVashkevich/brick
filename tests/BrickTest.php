<?php
namespace OlegV\Tests;

use OlegV\Brick;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Тестовый компонент для проверки Brick
 */
class TestButton extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary'
    ) {
        parent::__construct();
    }
}

/**
 * Тестовый компонент без CSS/JS
 */
class TestSimpleComponent extends Brick
{
    public function __construct(
        public string $content = 'test'
    ) {
        parent::__construct();
    }
}

class BrickTest extends TestCase
{
    private string $testComponentsDir;

    protected function setUp(): void
    {
        parent::setUp();
        Brick::clear();

        // Создаем директорию для тестовых компонентов
        $this->testComponentsDir = __DIR__ . '/test_components';
        if (!is_dir($this->testComponentsDir)) {
            mkdir($this->testComponentsDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Удаляем тестовые директории
        if (is_dir($this->testComponentsDir)) {
            $this->removeDirectory($this->testComponentsDir);
        }
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createTestComponent(string $className, array $files = []): void
    {
        $componentDir = $this->testComponentsDir . '/' . $className;
        if (!is_dir($componentDir)) {
            mkdir($componentDir, 0777, true);
        }

        // Создаем PHP класс
        if (!empty($files['php'])) {
            file_put_contents($componentDir . '/' . $className . '.php', $files['php']);
        }

        // Создаем шаблон
        if (!empty($files['template'])) {
            file_put_contents($componentDir . '/template.php', $files['template']);
        }

        // Создаем CSS
        if (!empty($files['css'])) {
            file_put_contents($componentDir . '/style.css', $files['css']);
        }

        // Создаем JS
        if (!empty($files['js'])) {
            file_put_contents($componentDir . '/script.js', $files['js']);
        }
    }

    public function testComponentWithoutTemplateThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('требует template.php');

        eval('
            namespace OlegV\Tests;
            class InvalidComponent extends \OlegV\Brick {
                // Не будет файла template.php
            }
        ');

        $className = 'OlegV\Tests\InvalidComponent';
        new $className();
    }

    public function testComponentWithValidTemplate(): void
    {
        $this->createTestComponent('ValidComponent', [
            'php' => '<?php namespace OlegV\Tests; class ValidComponent extends \OlegV\Brick { public function __construct(public string $title = "test") { parent::__construct(); } }',
            'template' => '<div class="test"><?= $this->e($component->title) ?></div>'
        ]);

        // Подключаем файл с классом
        require_once $this->testComponentsDir . '/ValidComponent/ValidComponent.php';

        $component = new \OlegV\Tests\ValidComponent('Hello World');
        $result = $component->render();

        $this->assertEquals('<div class="test">Hello World</div>', $result);
    }

    public function testToStringMethod(): void
    {
        $this->createTestComponent('ToStringComponent', [
            'php' => '<?php namespace OlegV\Tests; class ToStringComponent extends \OlegV\Brick { public function __construct(public string $value = "test") { parent::__construct(); } }',
            'template' => '<span><?= $component->value ?></span>'
        ]);

        require_once $this->testComponentsDir . '/ToStringComponent/ToStringComponent.php';

        $component = new \OlegV\Tests\ToStringComponent('Test Value');

        $this->assertEquals('<span>Test Value</span>', (string)$component);
    }

    public function testEscapeMethod(): void
    {
        $this->createTestComponent('EscapeComponent', [
            'php' => '<?php namespace OlegV\Tests; class EscapeComponent extends \OlegV\Brick { 
                public function __construct(public string $content = "") { parent::__construct(); } 
                public function testEscape() { return $this->e($this->content); }
            }',
            'template' => '<div><?= $component->testEscape() ?></div>'
        ]);

        require_once $this->testComponentsDir . '/EscapeComponent/EscapeComponent.php';

        $component = new \OlegV\Tests\EscapeComponent('<script>alert("xss")</script>');
        $result = $component->render();

        $this->assertEquals('<div>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</div>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testClassListMethod(): void
    {
        $this->createTestComponent('ClassListComponent', [
            'php' => '<?php namespace OlegV\Tests; class ClassListComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
                public function testClassList() { 
                    return $this->classList(["btn", "primary", "", null, false ? "hidden" : "visible"]); 
                }
            }',
            'template' => '<div class="<?= $component->testClassList() ?>">test</div>'
        ]);

        require_once $this->testComponentsDir . '/ClassListComponent/ClassListComponent.php';

        $component = new \OlegV\Tests\ClassListComponent();
        $result = $component->render();

        $this->assertEquals('<div class="btn primary visible">test</div>', $result);
    }

    public function testCssAndJsAssets(): void
    {
        $this->createTestComponent('AssetComponent', [
            'php' => '<?php namespace OlegV\Tests; class AssetComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>test</div>',
            'css' => '.asset-component { color: red; }',
            'js' => 'console.log("AssetComponent loaded");'
        ]);

        require_once $this->testComponentsDir . '/AssetComponent/AssetComponent.php';

        // Создаем компонент для регистрации ассетов
        new \OlegV\Tests\AssetComponent();

        $css = Brick::renderCss();
        $js = Brick::renderJs();
        $assets = Brick::renderAssets();

        $this->assertStringContainsString('.asset-component { color: red; }', $css);
        $this->assertStringContainsString('console.log("AssetComponent loaded")', $js);
        $this->assertStringContainsString('<style>', $assets);
        $this->assertStringContainsString('<script>', $assets);
        $this->assertStringContainsString('asset-component', $assets);
    }

    public function testMultipleComponentsShareAssets(): void
    {
        // Создаем первый компонент
        $this->createTestComponent('ComponentA', [
            'php' => '<?php namespace OlegV\Tests; class ComponentA extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>A</div>',
            'css' => '.component-a { color: blue; }'
        ]);

        // Создаем второй компонент
        $this->createTestComponent('ComponentB', [
            'php' => '<?php namespace OlegV\Tests; class ComponentB extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>B</div>',
            'js' => 'console.log("ComponentB");'
        ]);

        require_once $this->testComponentsDir . '/ComponentA/ComponentA.php';
        require_once $this->testComponentsDir . '/ComponentB/ComponentB.php';

        // Создаем оба компонента
        new \OlegV\Tests\ComponentA();
        new \OlegV\Tests\ComponentB();

        $css = Brick::renderCss();
        $js = Brick::renderJs();

        $this->assertStringContainsString('.component-a', $css);
        $this->assertStringContainsString('ComponentB', $js);

        $stats = Brick::getCacheStats();
        $this->assertEquals(2, $stats['cached_classes']);
        $this->assertEquals(1, $stats['css_assets']);
        $this->assertEquals(1, $stats['js_assets']);
    }

    public function testClearMethod(): void
    {
        $this->createTestComponent('ClearTestComponent', [
            'php' => '<?php namespace OlegV\Tests; class ClearTestComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>test</div>',
            'css' => '.test { display: none; }'
        ]);

        require_once $this->testComponentsDir . '/ClearTestComponent/ClearTestComponent.php';

        new \OlegV\Tests\ClearTestComponent();

        $statsBefore = Brick::getCacheStats();
        $this->assertGreaterThan(0, $statsBefore['cached_classes']);

        Brick::clear();

        $statsAfter = Brick::getCacheStats();
        $this->assertEquals(0, $statsAfter['cached_classes']);
        $this->assertEquals(0, $statsAfter['css_assets']);
        $this->assertEquals(0, $statsAfter['js_assets']);
    }

    public function testTemplateWithException(): void
    {
        $this->createTestComponent('ErrorComponent', [
            'php' => '<?php namespace OlegV\Tests; class ErrorComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<?php throw new \Exception("Template error"); ?>'
        ]);

        require_once $this->testComponentsDir . '/ErrorComponent/ErrorComponent.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ошибка рендеринга компонента');

        $component = new \OlegV\Tests\ErrorComponent();
        $component->render();
    }

    public function testCacheReuse(): void
    {
        $this->createTestComponent('CachedComponent', [
            'php' => '<?php namespace OlegV\Tests; class CachedComponent extends \OlegV\Brick { 
                public function __construct(public int $id = 0) { parent::__construct(); } 
            }',
            'template' => '<div id="<?= $component->id ?>">cached</div>'
        ]);

        require_once $this->testComponentsDir . '/CachedComponent/CachedComponent.php';

        // Первый экземпляр
        $component1 = new \OlegV\Tests\CachedComponent(1);
        $result1 = $component1->render();

        // Второй экземпляр
        $component2 = new \OlegV\Tests\CachedComponent(2);
        $result2 = $component2->render();

        $this->assertEquals('<div id="1">cached</div>', $result1);
        $this->assertEquals('<div id="2">cached</div>', $result2);

        $stats = Brick::getCacheStats();
        $this->assertEquals(1, $stats['cached_classes']);
    }

    public function testEmptyAssetsRenderEmptyString(): void
    {
        Brick::clear();

        $this->assertEquals('', Brick::renderCss());
        $this->assertEquals('', Brick::renderJs());
        $this->assertEquals("\n", Brick::renderAssets()); // CSS + \n + JS
    }
}