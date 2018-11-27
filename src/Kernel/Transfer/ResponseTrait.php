<?php
namespace Air\Kernel\Transfer;

trait ResponseTrait
{
    /**
     * @var \Exception|null
     */
    public $exception;

    /**
     * @param \Exception $e
     * @return $this
     */
    public function withException(\Exception $e)
    {
        $this->exception = $e;

        return $this;
    }
}