<?php

/**
 * Render a view template with optional layout
 */
function view(string $template, array $data = [], ?string $layout = null): void
{
    extract($data);

    if ($layout) {
        ob_start();
        require BASE_PATH . '/views/' . str_replace('.', '/', $template) . '.php';
        $content = ob_get_clean();
        require BASE_PATH . '/views/layouts/' . $layout . '.php';
    } else {
        require BASE_PATH . '/views/' . str_replace('.', '/', $template) . '.php';
    }
    exit;
}

/**
 * Render a partial view
 */
function partial(string $name, array $data = []): void
{
    extract($data);
    require BASE_PATH . '/views/partials/' . $name . '.php';
}
