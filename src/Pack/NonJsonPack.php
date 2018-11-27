<?php
namespace Air\Pack;

use Air\Tool\Arr;
use Air\Tool\Str;

class NonJsonPack implements IPack
{
    protected $lastData;
    protected $lastDataResult;

    /**
     * @param $data
     * @param null $topic
     * @return string
     * @throws \JsonException
     */
    public function pack($data, $topic = null)
    {
        if ($this->lastData != null && $this->lastDataResult == $data) {
            return $this->lastDataResult;
        }

        $this->lastData = $data;

        return $this->lastDataResult = Arr::toJsonStr($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $data
     * @return mixed
     * @throws \JsonException
     */
    public function unPack($data)
    {
        $value = Str::jsonToArr($data);

        if (empty($value)) {
            throw new \LogicException('json unPack fail');
        }

        return $value;
    }

    function encode($buffer)
    {
        return $buffer;
    }

    function decode($buffer)
    {
        return $buffer;
    }

    public function getProBufSet()
    {
        return null;
    }
}