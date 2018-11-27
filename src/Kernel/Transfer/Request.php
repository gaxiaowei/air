<?php
namespace Air\Kernel\Transfer;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * 方便以后扩展
 * Class Request
 * @package Air\Kernel\Transfer
 */
class Request extends SymfonyRequest
{
    /**
     * @return bool
     */
    public function expectsJson()
    {
        return $this->isXmlHttpRequest() || $this->wantsJson();
    }

    /**
     * @return bool
     */
    public function wantsJson()
    {
        $acceptable = $this->getAcceptableContentTypes();

        return isset($acceptable[0]) && strpos($acceptable[0], 'json');
    }
}