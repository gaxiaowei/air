<?php
namespace Air\Pack;

use Air\Tool\Arr;
use Air\Tool\Str;

class LenJsonPack implements IPack
{
    private $packageLengthType = 'N';
    private $packageLengthTypeLength = 4;

    private $packageLengthOffset = 0;
    private $packageBodyOffset = 0;

    private $lastData = null;
    private $lastDataResult = null;

    /**
     * 数据包编码
     * @param $buffer
     * @return string
     */
    public function encode($buffer)
    {
        $totalLength = $this->packageLengthOffset + strlen($buffer) - $this->packageBodyOffset;

        return pack($this->packageLengthType, $totalLength) . $buffer;
    }

    /**
     * @param $buffer
     * @return string
     */
    public function decode($buffer)
    {
        return substr($buffer, $this->packageLengthTypeLength);
    }

    /**
     * @param $data
     * @param null $topic
     * @return string|null
     * @throws \JsonException
     */
    public function pack($data, $topic = null)
    {
        if ($this->lastData != null && $this->lastDataResult == $data) {
            return $this->lastDataResult;
        }

        $this->lastDataResult = $data;

        return $this->lastDataResult = $this->encode(Arr::toJsonStr($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param $data
     * @return mixed
     * @throws \JsonException
     */
    public function unPack($data)
    {
        return Str::jsonToArr($this->decode($data));
    }

    public function getProBufSet()
    {
        return [
            'open_length_check' => true,
            'package_length_type' => $this->packageLengthType,
            'package_length_offset' => $this->packageLengthOffset,  //第N个字节是包长度的值
            'package_body_offset' => $this->packageBodyOffset,      //第几个字节开始计算长度
            'package_max_length' => 1024 * 1024 * 20,               //协议最大长度
        ];
    }
}