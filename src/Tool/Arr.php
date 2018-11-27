<?php
namespace Air\Tool;

use Air\Exception\ParamsException;
use JsonException;

class Arr
{
    /**
     * 从数组内复制出想要的keys 形成新的数组 并返回
     * @param array $keys
     * @param array $sourceArray
     * @return array
     */
    public static function copyKeysValue(array $keys = [], array $sourceArray = []) : array
    {
        $arr = [];

        foreach ($keys as $key) {
            if ($sourceArray !== null && array_key_exists($key, $sourceArray)) {
                $arr[$key] = '';

                if (!is_null($sourceArray[$key])) {
                    $arr[$key] = $sourceArray[$key];
                }
            }
        }

        return $arr;
    }

    /**
     * 检查数组中是否存在指定的所有key
     * @param array $keys
     * @param array $array
     * @return array
     * @throws ParamsException
     */
    public static function checkKeysExist(array $keys, array $array = []) : array
    {
        $diff = array_diff($keys, array_keys($array));

        if (count($diff) > 0) {
            throw new ParamsException('['.implode(',', $diff).']'.' these keys don\'t exist');
        }
        unset($diff);

        $arr = [];
        foreach ($keys as $param) {
            if ($array[$param] === null) {
                $array[$param] = '';
            }

            $response[$param] = $array[$param];
        }

        return $arr;
    }

    /**
     * 将数组的每一项value转换为字符串类型
     * @param array $data
     * @param array $except
     * @return array
     * @throws JsonException
     */
    public static function itemValToStr(array $data = [], array $except = []) : array
    {
        foreach ($data as $key => &$v) {
            if (array_key_exists($key, $except)) {
                if (is_callable($except[$key]) || function_exists($except[$key])) {
                    $v = $except[$key]($v);

                    continue;
                }
            }

            switch (gettype($v)) {
                case 'array' :
                    $v = self::itemValToStr($v, $except);
                    break;

                case 'object' :
                    $v = (object)self::itemValToStr(Str::jsonToArr(self::toJsonStr($v)), $except);
                    break;

                case 'double' :
                case 'integer' :
                    $v = strval($v);
                    break;

                case 'boolean' :
                    $v = (true === $v ? 'true' : 'false');
                    break;
            }

            unset($v);
        }

        return $data;
    }

    /**
     * 将数组转换为Json字符串
     * @param $data
     * @param int $options
     * @return string
     * @throws JsonException
     */
    public static function toJsonStr($data, $options = 0) : string
    {
        $str = json_encode($data, $options);

        if (json_last_error()) {
            throw new JsonException('json encode fail the error is '.json_last_error_msg(), json_last_error());
        }

        return $str;
    }
}