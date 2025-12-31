<?php

declare(strict_types=1);

namespace OlegV\Exceptions;

use OlegV\BrickManager;
use RuntimeException;

class RenderException extends RuntimeException
{
    /**
     * Преобразует исключение в HTML для отображения
     * В зависимости от режима отладки
     */
    public function toHtml(): string
    {
        if (BrickManager::isDebug()) {
            return $this->renderDebug();
        }

        return $this->renderProduction();
    }

    /**
     * Рендеринг для режима отладки
     */
    private function renderDebug(): string
    {
        return sprintf(
            '<div style="%s">'
            .'<strong style="%s">🚨 Brick Render Error</strong>'
            .'<div style="%s">%s</div>'
            .'</div>',
            'border:2px solid #dc3545;background:#f8d7da;padding:15px;margin:10px;border-radius:5px;',
            'color:#721c24;display:block;margin-bottom:10px;',
            'color:#721c24;font-family:monospace;',
            htmlspecialchars($this->getMessage()),
        );
    }

    /**
     * Рендеринг для production
     */
    private function renderProduction(): string
    {
        // Логируем ошибку
        error_log('[Brick] '.$this->getMessage());

        // Возвращаем пустой блок или fallback
        return '<!-- Brick render error -->';
    }
}