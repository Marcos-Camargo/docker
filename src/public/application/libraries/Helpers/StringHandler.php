<?php

namespace libraries\Helpers;

class StringHandler
{

    public static function toASCII($string)
    {
        setlocale(LC_CTYPE, 'pt_BR');
        return iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            //transliterator_transliterate('Any-Latin; Latin-ASCII', $string)
            $string
        );
    }

    public static function slugify($string, string $delimiter = '-'): string
    {
        return strtolower(
            trim(
                preg_replace('/[\s-]+/', $delimiter,
                    preg_replace('/[^A-Za-z0-9-]+/', $delimiter,
                        preg_replace('/[&]/', 'e',
                            preg_replace('/[\']/', '',
                                StringHandler::toASCII($string)
                            )
                        )
                    )
                ), $delimiter)
        );
    }

    public static function camelCaseSlug(string $string, string $separator = '_'): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode($separator, $ret);
    }

    public static function handleStringToJS($string)
    {
        return preg_replace(
            [
                '/[\']/', '/[\n]/', '/[\t]/'
            ],
            [
                "\\'", '', ''
            ],
            $string
        );
    }

    public static function encodeUTF8(string $string)
    {
        switch (true) {
            case (substr($string, 0, 3) == "\xef\xbb\xbf") :
                return substr($string, 3);
            case (substr($string, 0, 2) == "\xfe\xff") :
                return mb_convert_encoding(substr($string, 2), "UTF-8", "UTF-16BE");
            case (substr($string, 0, 2) == "\xff\xfe") :
                return mb_convert_encoding(substr($string, 2), "UTF-8", "UTF-16LE");
            case (substr($string, 0, 4) == "\x00\x00\xfe\xff") :
                return mb_convert_encoding(substr($string, 4), "UTF-8", "UTF-32BE");
            case (substr($string, 0, 4) == "\xff\xfe\x00\x00") :
                return mb_convert_encoding(substr($string, 4), "UTF-8", "UTF-32LE");
            default:
                return iconv(mb_detect_encoding($string, mb_detect_order(), true), "UTF-8", $string);
        }
    }

    public static function strContainsStr(string $firstStr, string $secondStr): bool
    {
        if (strlen($firstStr) >= strlen($secondStr))
            return strpos(strtolower($firstStr), strtolower($secondStr)) !== false;
        return strpos(strtolower($secondStr), strtolower($firstStr)) !== false;
    }
}