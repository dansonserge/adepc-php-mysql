<?php
declare(strict_types=1);

use Adepc\Admin\A;
use Adepc\Admin\Auth;

function at(string $key, array $vars = []): string
{
    return A::t($key, $vars);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Auth::csrf()) . '">';
}

/** Admin button styles, from the site's own button component. */
function abtn(string $variant = 'ink', string $extra = ''): string
{
    return button_class($variant, 'min-h-11 px-5 ' . $extra);
}

function partial_admin(string $name, array $vars = []): string
{
    return \Adepc\View::render('admin/' . $name, $vars);
}
