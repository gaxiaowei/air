<?php
namespace Air\Tool;

use JsonException;
use Swoole\Coroutine;

/**
 * Class Str
 * @package Air\Tool
 */
class Str
{
    /**
     * 将Json字符串转换为数组
     * @param string $str
     * @param bool $assoc
     * @return mixed
     * @throws JsonException
     */
    public static function jsonToArr(string $str = '', $assoc = true)
    {
        $arr = json_decode($str, $assoc);

        if (json_last_error()) {
            throw new JsonException('json decode fail the error is '.json_last_error_msg(), json_last_error());
        }

        return $arr;
    }

    /**
     * 將表情符號替換成文本
     * @param string $text
     * @param string $replace
     * @return string
     * @throws JsonException
     */
    public static function emoji(string $text, string $replace = '[符號]') : string
    {
        $text = Arr::toJsonStr($text);

        preg_match_all("/(\\\\ud83c\\\\u[0-9a-f]{4})|(\\\\ud83d\\\u[0-9a-f]{4})|(\\\\u[0-9a-f]{4})/", $text, $matches);
        if (!isset($matches[0][0])) {
            return self::jsonToArr($text);
        }

        $emoji = $matches[0];
        foreach ($emoji as $ec) {
            $hex = substr($ec, -4);

            if (self::isEmoji($hex)) {
                $text = strtr($ec, $replace, $text);
            }

            if (strlen($ec) == 6) {
                if ($hex >= '2600' and $hex <= '27ff') {
                    $text = strtr($ec, $replace, $text);
                }
            } else {
                if ($hex >= 'dc00' and $hex <= 'dfff') {
                    $text = strtr($ec, $replace, $text);
                }
            }
        }

        return self::jsonToArr($text);
    }

    /**
     * 获取Sw协程Id
     * @return string
     */
    public static function getSwCoroutineId() : string
    {
        if (class_exists(Coroutine::class, false) && (($coroutineId = Coroutine::getuid()) > 0)) {
            return strval($coroutineId);
        }

        return '';
    }

    /**
     * 判斷是否爲表情
     * @param $hex
     * @return bool
     */
    private static function isEmoji($hex) : bool
    {
        return ($hex == 0x0);
    }
}