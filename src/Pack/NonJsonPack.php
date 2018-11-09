<?php
namespace Air\Pack;

class NonJsonPack implements IPack
{
    protected $lastData;
    protected $lastDataResult;

    public function pack($data, $topic = null)
    {
        if ($this->lastData != null && $this->lastDataResult == $data) {
            return $this->lastDataResult;
        }

        $this->lastData = $data;

        return $this->lastDataResult = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function unPack($data)
    {
        $value = json_decode($data);

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