<?php

declare(strict_types=1);

namespace OlegV\Exceptions;

use OlegV\BrickManager;
use RuntimeException;

class ComponentNotFoundException extends RuntimeException
{
    /**
     * Преобразует исключение в HTML для отображения
     */
    public function toHtml(): string
    {
        if (BrickManager::isDebug()) {
            return $this->renderDebug();
        }

        return $this->renderProduction();
    }

    private function renderDebug(): string
    {
        return sprintf(
            '<div style="%s">'
            .'<strong style="%s">🚨 Brick Component Not Found</strong>'
            .'<div style="%s">%s</div>'
            .'<div style="%s">Template file not found</div>'
            .'</div>',
            'border:2px solid #ffc107;background:#fff3cd;padding:15px;margin:10px;border-radius:5px;',
            'color:#856404;display:block;margin-bottom:10px;',
            'color:#856404;font-family:monospace;font-weight:bold;',
            htmlspecialchars($this->getMessage()),
            'color:#856404;margin-top:5px;',
        );
    }

    private function renderProduction(): string
    {
        error_log('[Brick] Component not found: '.$this->getMessage());
        return '<!-- Brick component not found -->';
    }
}