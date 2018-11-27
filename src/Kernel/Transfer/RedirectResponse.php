<?php
namespace Air\Kernel\Transfer;

use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * 方便日后扩展
 * Class RedirectResponse
 * @package Air\Kernel\Transfer
 */
class RedirectResponse extends SymfonyRedirectResponse
{
    use ResponseTrait;
}