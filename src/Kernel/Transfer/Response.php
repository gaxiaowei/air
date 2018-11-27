<?php
namespace Air\Kernel\Transfer;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 方便以后扩展
 * Class Response
 * @package Air\Kernel\Transfer
 */
class Response extends SymfonyResponse
{
    use ResponseTrait;
}