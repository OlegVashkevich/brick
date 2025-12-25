<?php

namespace OlegV\Tests\Assets;

use OlegV\Assets\FileAssetRenderer;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FileAssetRendererTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем виртуальную файловую систему для тестов
        $this->root = vfsStream::setup('testDir');
        $this->outputDir = vfsStream::url('testDir/assets');
    }

    public function testRenderCssCreatesFile(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/');

        $cssAssets = [
            'Component1' => '.component1 { color: red; }',
            'Component2' => '.component2 { background: blue; }'
        ];

        $result = $renderer->renderCss($cssAssets);

        // Проверяем что создалась директория
        $this->assertDirectoryExists($this->outputDir);

        // Проверяем что файл создался
        $files = scandir($this->outputDir);
        $cssFiles = array_filter($files, fn($file) => str_contains($file, '.css'));

        $this->assertNotEmpty($cssFiles);

        // Проверяем результат рендеринга
        $this->assertStringContainsString('<link rel="stylesheet" href="/assets/', $result);
        $this->assertStringContainsString('.css', $result);

        // Проверяем содержимое файла
        $cssFile = $this->outputDir . '/' . current($cssFiles);
        $fileContent = file_get_contents($cssFile);

        $this->assertStringContainsString('.component1 { color: red; }', $fileContent);
        $this->assertStringContainsString('.component2 { background: blue; }', $fileContent);
    }

    public function testRenderJsCreatesFile(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/');

        $jsAssets = [
            'Component1' => 'console.log("Component1 loaded");',
            'Component2' => 'console.log("Component2 loaded");'
        ];

        $result = $renderer->renderJs($jsAssets);

        $this->assertDirectoryExists($this->outputDir);

        $files = scandir($this->outputDir);
        $jsFiles = array_filter($files, fn($file) => str_contains($file, '.js'));

        $this->assertNotEmpty($jsFiles);

        $this->assertStringContainsString('<script src="/assets/', $result);
        $this->assertStringContainsString('.js', $result);

        $jsFile = $this->outputDir . '/' . current($jsFiles);
        $fileContent = file_get_contents($jsFile);

        $this->assertStringContainsString('console.log("Component1 loaded");', $fileContent);
        $this->assertStringContainsString('console.log("Component2 loaded");', $fileContent);
    }

    public function testRenderEmptyCssReturnsEmptyString(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/');

        $result = $renderer->renderCss([]);

        $this->assertEquals('', $result);
    }

    public function testRenderEmptyJsReturnsEmptyString(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/');

        $result = $renderer->renderJs([]);

        $this->assertEquals('', $result);
    }

    public function testFileReuse(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/');

        $cssAssets = ['.test { color: red; }'];

        // Первый вызов - создает файл
        $result1 = $renderer->renderCss($cssAssets);

        // Получаем имя созданного файла
        preg_match('/href="\/assets\/([^"]+)"/', $result1, $matches);
        $filename = $matches[1];
        $filepath = $this->outputDir . '/' . $filename;

        // Запоминаем время модификации
        $mtime1 = filemtime($filepath);

        // Ждем секунду чтобы время точно изменилось
        sleep(1);

        // Второй вызов с теми же данными - должен вернуть тот же файл
        $result2 = $renderer->renderCss($cssAssets);

        $mtime2 = filemtime($filepath);

        // Файл не должен был перезаписываться
        $this->assertEquals($mtime1, $mtime2);
        $this->assertStringContainsString($filename, $result2);
    }

    public function testMinifyCss(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/', true);

        $cssAssets = [
            'Test' => ".test {\n    color: red;\n    /* Комментарий */\n    background: blue;\n}"
        ];

        $result = $renderer->renderCss($cssAssets);

        $files = scandir($this->outputDir);
        $cssFiles = array_filter($files, fn($file) => str_contains($file, '.css'));
        $cssFile = $this->outputDir . '/' . current($cssFiles);
        $fileContent = file_get_contents($cssFile);

        // Проверяем что CSS минифицирован
        $this->assertStringNotContainsString('/* Комментарий */', $fileContent);
        $this->assertStringNotContainsString("\n", $fileContent);
        $this->assertStringNotContainsString('    ', $fileContent);
        $this->assertStringContainsString('.test{color:red;background:blue}', trim($fileContent));
    }

    public function testMinifyJs(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/', true);

        $jsAssets = [
            'Test' => "// Однострочный комментарий\nconsole.log('test');\n/* Многострочный\nкомментарий */\nfunction test() {\n    return true;\n}"
        ];

        $result = $renderer->renderJs($jsAssets);

        $files = scandir($this->outputDir);
        $jsFiles = array_filter($files, fn($file) => str_contains($file, '.js'));
        $jsFile = $this->outputDir . '/' . current($jsFiles);
        $fileContent = file_get_contents($jsFile);

        // Проверяем что JS минифицирован
        $this->assertStringNotContainsString('// Однострочный комментарий', $fileContent);
        $this->assertStringNotContainsString('/* Многострочный', $fileContent);
        $this->assertLessThan(54, strlen($fileContent)); // Минифицированная версия должна быть короткой
    }

    public function testDifferentContentCreatesDifferentFiles(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/assets/');

        // Первый CSS
        $css1 = '.test1 { color: red; }';
        $result1 = $renderer->renderCss(['Test1' => $css1]);

        // Второй CSS (другой контент)
        $css2 = '.test2 { color: blue; }';
        $result2 = $renderer->renderCss(['Test2' => $css2]);

        // Извлекаем имена файлов
        preg_match('/href="\/assets\/([^"]+)"/', $result1, $matches1);
        preg_match('/href="\/assets\/([^"]+)"/', $result2, $matches2);

        // Файлы должны быть разными
        $this->assertNotEquals($matches1[1], $matches2[1]);

        // Оба файла должны существовать
        $this->assertFileExists($this->outputDir . '/' . $matches1[1]);
        $this->assertFileExists($this->outputDir . '/' . $matches2[1]);
    }

    public function testCustomPublicUrl(): void
    {
        $renderer = new FileAssetRenderer($this->outputDir, '/custom/assets/');

        $cssAssets = ['.test { color: red; }'];
        $result = $renderer->renderCss($cssAssets);

        $this->assertStringContainsString('href="/custom/assets/', $result);
    }

    public function testOutputDirAutoCreation(): void
    {
        $nonExistentDir = vfsStream::url('testDir/non/existent/dir');

        // Директории не должно существовать
        $this->assertDirectoryDoesNotExist($nonExistentDir);

        $renderer = new FileAssetRenderer($nonExistentDir, '/assets/');

        $cssAssets = ['.test { color: red; }'];
        $result = $renderer->renderCss($cssAssets);

        // Теперь директория должна существовать
        $this->assertDirectoryExists($nonExistentDir);
    }
}