<?php
declare(strict_types=1);

namespace Minimal\Support;

/**
 * 字符串
 */
class Str
{
    /**
     * 获取指定长度的随机字母数字组合的字符串
     */
    public static function random(int $length = 6, int $type = null, string $addChars = '') : string
    {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书" . $addChars;
                break;
            default:
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($length > 10) {
            $chars = $type == 1 ? str_repeat($chars, $length) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = substr($chars, 0, $length);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $str .= mb_substr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
            }
        }
        return $str;
    }

    /**
     * 按指定字符进行切割成数组（不区分大小写）
     */
    public static function split(string $delimiter, string $string) : array
    {
        $array = [];
        $start = 0;
        $index = 0;
        $length = strlen($delimiter);
        while (false !== $index = stripos($string, $delimiter, $index)) {
            $array[] = substr($string, $start, $index);
            $start = $index;
            $index += $length;
        }
        $array[] = !$start ? $string : substr($string, $start + $length);
        return $array;
    }

    /**
     * 按指定字符切割后通过回调函数进行处理并再次返回拼接后的字符串
     */
    public static function map(string $string, callable $callback, string $delimiter = '') : string
    {
        $pieces = self::split($delimiter, $string);
        $pieces = Arr::each($callback, $pieces);
        return implode($delimiter, $pieces);
    }
}