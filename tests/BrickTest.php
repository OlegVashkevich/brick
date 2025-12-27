<?php

declare(strict_types=1);

namespace OlegV\Tests;

use OlegV\BrickManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;


class BrickTest extends TestCase
{
    private string $testComponentsDir;

    protected function setUp(): void
    {
        parent::setUp();
        BrickManager::getInstance()->clear();

        // Создаем директорию для тестовых компонентов
        $this->testComponentsDir = __DIR__.'/test_components';
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
        BrickManager::getInstance()->clear();
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * @param  string  $className
     * @param  array<string,string>  $files
     * @return void
     */
    private function createTestComponent(string $className, array $files = []): void
    {
        $componentDir = $this->testComponentsDir.'/'.$className;
        if (!is_dir($componentDir)) {
            mkdir($componentDir, 0777, true);
        }

        // Создаем PHP класс
        if (isset($files['php'])) {
            file_put_contents($componentDir.'/'.$className.'.php', $files['php']);
        }

        // Создаем шаблон
        if (isset($files['template'])) {
            file_put_contents($componentDir.'/template.php', $files['template']);
        }

        // Создаем CSS
        if (isset($files['css'])) {
            file_put_contents($componentDir.'/style.css', $files['css']);
        }

        // Создаем JS
        if (isset($files['js'])) {
            file_put_contents($componentDir.'/script.js', $files['js']);
        }
    }

    public function testComponentWithoutTemplateThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('template.php не найден');

        eval(
        '
            namespace OlegV\Tests;
            readonly class InvalidComponent extends \OlegV\Brick {
                // Не будет файла template.php
            }
        '
        );

        $className = 'OlegV\Tests\InvalidComponent';
        new $className();
    }

    public function testComponentWithValidTemplate(): void
    {
        $this->createTestComponent('ValidComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class ValidComponent extends \OlegV\Brick { public function __construct(public string $title = "test") { parent::__construct(); } }',
            'template' => '<div class="test"><?= $this->e($this->title) ?></div>',
        ]);

        // Подключаем файл с классом
        require_once $this->testComponentsDir.'/ValidComponent/ValidComponent.php';

        /** @noinspection PhpUndefinedClassInspection */
        $component = new ValidComponent('Hello World');
        $result = $component->render();

        $this->assertEquals('<div class="test">Hello World</div>', $result);
    }

    public function testToStringMethod(): void
    {
        $this->createTestComponent('ToStringComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class ToStringComponent extends \OlegV\Brick { public function __construct(public string $value = "test") { parent::__construct(); } }',
            'template' => '<span><?= $this->value ?></span>',
        ]);

        require_once $this->testComponentsDir.'/ToStringComponent/ToStringComponent.php';

        /** @noinspection PhpUndefinedClassInspection */
        $component = new ToStringComponent('Test Value');

        $this->assertEquals('<span>Test Value</span>', (string)$component);
    }

    public function testEscapeMethod(): void
    {
        $this->createTestComponent('EscapeComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class EscapeComponent extends \OlegV\Brick { 
                public function __construct(public string $content = "") { parent::__construct(); } 
                public function testEscape() { return $this->e($this->content); }
            }',
            'template' => '<div><?= $this->testEscape() ?></div>',
        ]);

        require_once $this->testComponentsDir.'/EscapeComponent/EscapeComponent.php';

        /** @noinspection PhpUndefinedClassInspection */
        $component = new EscapeComponent('<script>alert("xss")</script>');
        $result = $component->render();

        $this->assertEquals('<div>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</div>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testClassListMethod(): void
    {
        $this->createTestComponent('ClassListComponent', [
            'php' => '<?php namespace OlegV\Tests; use OlegV\Traits\WithHelpers;readonly class ClassListComponent extends \OlegV\Brick { 
                use WithHelpers;
                public function __construct() { parent::__construct(); } 
                public function testClassList() { 
                    return $this->classList(["btn", "primary", "", null, false ? "hidden" : "visible"]); 
                }
            }',
            'template' => '<div class="<?= $this->testClassList() ?>">test</div>',
        ]);

        require_once $this->testComponentsDir.'/ClassListComponent/ClassListComponent.php';

        /** @noinspection PhpUndefinedClassInspection */
        $component = new ClassListComponent();
        $result = $component->render();

        $this->assertEquals('<div class="btn primary visible">test</div>', $result);
    }

    public function testCssAndJsAssets(): void
    {
        $this->createTestComponent('AssetComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class AssetComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>test</div>',
            'css' => '.asset-component { color: red; }',
            'js' => 'console.log("AssetComponent loaded");',
        ]);

        require_once $this->testComponentsDir.'/AssetComponent/AssetComponent.php';

        // Создаем компонент для регистрации асетов
        /** @noinspection PhpUndefinedClassInspection */
        new AssetComponent();

        $css = BrickManager::getInstance()->renderCss();
        $js = BrickManager::getInstance()->renderJs();
        $assets = BrickManager::getInstance()->renderAssets();

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
            'php' => '<?php namespace OlegV\Tests; readonly class ComponentA extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>A</div>',
            'css' => '.component-a { color: blue; }',
        ]);

        // Создаем второй компонент
        $this->createTestComponent('ComponentB', [
            'php' => '<?php namespace OlegV\Tests; readonly class ComponentB extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>B</div>',
            'js' => 'console.log("ComponentB");',
        ]);

        require_once $this->testComponentsDir.'/ComponentA/ComponentA.php';
        require_once $this->testComponentsDir.'/ComponentB/ComponentB.php';

        // Создаем оба компонента
        /** @noinspection PhpUndefinedClassInspection */
        new ComponentA();
        /** @noinspection PhpUndefinedClassInspection */
        new ComponentB();

        $css = BrickManager::getInstance()->renderCss();
        $js = BrickManager::getInstance()->renderJs();

        $this->assertStringContainsString('.component-a', $css);
        $this->assertStringContainsString('ComponentB', $js);

        $stats = BrickManager::getInstance()->getStats();
        $this->assertEquals(2, $stats['cached_classes']);
        $this->assertEquals(1, $stats['css_assets']);
        $this->assertEquals(1, $stats['js_assets']);
    }

    public function testClearMethod(): void
    {
        $this->createTestComponent('ClearTestComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class ClearTestComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<div>test</div>',
            'css' => '.test { display: none; }',
        ]);

        require_once $this->testComponentsDir.'/ClearTestComponent/ClearTestComponent.php';

        /** @noinspection PhpUndefinedClassInspection */
        new ClearTestComponent();

        $statsBefore = BrickManager::getInstance()->getStats();
        $this->assertGreaterThan(0, $statsBefore['cached_classes']);

        BrickManager::getInstance()->clear();

        $statsAfter = BrickManager::getInstance()->getStats();
        $this->assertEquals(0, $statsAfter['cached_classes']);
        $this->assertEquals(0, $statsAfter['css_assets']);
        $this->assertEquals(0, $statsAfter['js_assets']);
    }

    public function testTemplateWithException(): void
    {
        $this->createTestComponent('ErrorComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class ErrorComponent extends \OlegV\Brick { 
                public function __construct() { parent::__construct(); } 
            }',
            'template' => '<?php throw new \Exception("Template error"); ?>',
        ]);

        require_once $this->testComponentsDir.'/ErrorComponent/ErrorComponent.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ошибка рендеринга компонента');

        /** @noinspection PhpUndefinedClassInspection */
        $component = new ErrorComponent();
        $component->render();
    }

    public function testCacheReuse(): void
    {
        $this->createTestComponent('CachedComponent', [
            'php' => '<?php namespace OlegV\Tests; readonly class CachedComponent extends \OlegV\Brick { 
                public function __construct(public int $id = 0) { parent::__construct(); } 
            }',
            'template' => '<div id="<?= $this->id ?>">cached</div>',
        ]);

        require_once $this->testComponentsDir.'/CachedComponent/CachedComponent.php';

        // Первый экземпляр
        /** @noinspection PhpUndefinedClassInspection */
        $component1 = new CachedComponent(1);
        $result1 = $component1->render();

        // Второй экземпляр
        /** @noinspection PhpUndefinedClassInspection */
        $component2 = new CachedComponent(2);
        $result2 = $component2->render();

        $this->assertEquals('<div id="1">cached</div>', $result1);
        $this->assertEquals('<div id="2">cached</div>', $result2);

        $stats = BrickManager::getInstance()->getStats();
        $this->assertEquals(1, $stats['cached_classes']);
    }

    public function testEmptyAssetsRenderEmptyString(): void
    {
        BrickManager::getInstance()->clear();

        $this->assertEquals('', BrickManager::getInstance()->renderCss());
        $this->assertEquals('', BrickManager::getInstance()->renderJs());
        $this->assertEquals("", BrickManager::getInstance()->renderAssets()); // CSS + \n + JS
    }
}