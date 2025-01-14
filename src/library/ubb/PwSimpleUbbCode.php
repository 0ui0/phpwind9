<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('LIB:ubb.config.PwUbbCodeConvertConfig');
Wind::import('SRV:credit.bo.PwCreditBo');

/**
 * ubb转换.
 *
 * @author Jianmin Chen <sky_hold@163.com>
 * @copyright ©2003-2103 phpwind.com
 * @license https://www.phpwind.com
 *
 * @version $Id: PwSimpleUbbCode.php 24383 2013-01-29 10:09:39Z jieyin $
 */
class PwSimpleUbbCode
{
    protected static $_code = array();
    protected static $_isSubstr = false;
    protected static $_hide = false;
    protected static $_emotion = null;

    /**
     * 转换段落.
     *
     * @param string $message 源内容
     *
     * @return string 转化后的内容
     */
    public static function convertParagraph($message)
    {
        if (($pos = strpos($message, '[paragraph]')) !== false && $pos < 10) {
            $message = str_replace('[paragraph]', '', $message);
        }
        return $message;
    }

    /**
     * 转换同名ubb标签到html.
     *
     * @param string $message 源内容
     * @param mixed $tag 要转化的标签 <例: 1.单个 string u/b/ 2.多个 array('u','b')>
     *
     * @return string 转化后的内容
     */
    public static function convertTag($message, $tag)
    {
        is_array($tag) || $tag = array($tag);
        foreach ($tag as $v) {
            $message = str_replace(array("[$v]", "[/$v]"), '', $message);
        }
        return $message;
    }

    /**
     * 转化hr标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertHr($message)
    {
        return str_replace('[hr]', '', $message);
    }

    /**
     * 转化list标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertList($message)
    {
        $message = preg_replace('/\[list=([aA1]?)\](.+?)\[\/list\]/is', '', $message);
        return str_replace(
            array('[list]', '[li]', '[/li]', '[/list]'),
            '',
            $message
        );
    }

    /**
     * 转化font标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertFont($message)
    {
        $message = preg_replace_callback("/\[font=([^\[\(&\\;]+?)\]/is", 'self::filterUbb', $message);//过滤ubb属性中的注入敏感词
        $message = preg_replace("/\[font=([^\[\(&\\;]+?)\]/is", '', $message);
        return str_replace('[/font]', '', $message);
    }

    /**
     * 转化color标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertColor($message)
    {
        $message = preg_replace_callback("/\[color=([#0-9a-z]{1,15})\]/is", 'self::filterUbb', $message);//过滤ubb属性中的注入敏感词
        $message = preg_replace("/\[color=([#0-9a-z]{1,15})\]/is", '', $message);
        return str_replace('[/color]', '', $message);
    }

    /**
     * 转化backcolor标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertBackColor($message)
    {
        $message = preg_replace_callback("/\[backcolor=([#0-9a-z]{1,10})\]/is", 'self::filterUbb', $message);//过滤ubb属性中的注入敏感词
        $message = preg_replace("/\[backcolor=([#0-9a-z]{1,10})\]/is", '', $message);
        return str_replace('[/backcolor]', '', $message);
    }

    /**
     * 转化size标签.
     *
     * @param string $message 内容
     * @param int $maxSize 最大字体限制 <0.不限制>
     *
     * @return string
     */
    public static function convertSize($message, $maxSize = 0)
    {
        $message = preg_replace("/\[size=(\d+)\]/is", '', $message);
        return str_replace('[/size]', '', $message);
    }

    /**
     * 转化email标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertEmail($message)
    {
        $message = preg_replace_callback("/\[email=([^\[]*)\][^\[]*\[\/email\]/is", 'self::filterUbb', $message);//过滤ubb属性中的注入敏感词
        $message = preg_replace_callback("/\[email\]([^\[]*)\[\/email\]/is", 'self::filterUbb', $message);//过滤ubb属性中的注入敏感词
        return preg_replace(
            array("/\[email=([^\[]*)\]([^\[]*)\[\/email\]/is", "/\[email\]([^\[]*)\[\/email\]/is"),
            array('<a href="mailto:\\1 ">\\2</a>', '<a href="mailto:\\1 ">\\1</a>'),
            $message
        );
    }

    /**
     * 转化align标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertAlign($message)
    {
        $message = preg_replace("/\[align=(left|center|right|justify)\]/is", '', $message);
        return str_replace('[/align]', '', $message);
    }

    /**
     * 转化glow标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function convertGlow($message)
    {
        $message = preg_replace_callback("/\[glow=(\d+)\,([0-9a-zA-Z]+?)\,(\d+)\].+?\[\/glow\]/is", 'self::filterUbb', $message);//过滤ubb属性中的注入敏感词
        return preg_replace("/\[glow=(\d+)\,([0-9a-zA-Z]+?)\,(\d+)\](.+?)\[\/glow\]/is", '\\4', $message);
    }

    /**
     * 转化table标签.
     *
     * @param string $message 内容
     * @param int $max 嵌套时，最大解析层级
     *
     * @return string
     */
    public static function convertTable($message, $max = 0)
    {
        $t = 0;
        while (self::hasTag($message, 'table')) {
            $message = preg_replace_callback("/\[table(?:=(\d{1,4}(?:%|px)?)(?:,(#\w{6})?)?(?:,(#\w{6})?)?(?:,(\d+))?(?:,(\d+))?(?:,(left|center|right))?)?\](?!.*(\[table))(.*?)\[\/table\]/is", function ($m) {
                return PwSimpleUbbCode::_pushCode('createTable', $m[8], $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
            }, $message);
            if (++$t > $max) {
                break;
            }
        }
        return $message;
    }

    /**
     * 解析表情.
     *
     * @param string $message
     *
     * @return string
     */
    public static function parseEmotion($message)
    {
        $message = preg_replace_callback("/\[s:(.+?)\]/is", function ($m) {
            return PwSimpleUbbCode::_pushCode('createEmotion', $m[1]);
        }, $message);
        return $message;
    }

    /**
     * 解析附件.
     *
     * @param string $message
     *
     * @return string
     */
    public static function parseAttachment($message, $config)
    {
        preg_match_all('/\[(attachment|p_w_upload|p_w_picpath)=(\d+)\]/is', $message, $matchs);
        if ($matchs[2]) {
            $config->removeAttach($matchs[2]);
            foreach ($matchs[2] as $key => $value) {
                $message = str_replace($matchs[0][$key], self::_pushCode('createAttachment', $value, $config), $message);
            }
        }
        return $message;
    }

    /**
     * 转化img标签.
     *
     * @param string $message 内容
     * @param int $maxWidth 最大宽度限制
     * @param int $maxHeight 最大高度限制
     *
     * @return string
     */
    public static function parseImg($message, $maxWidth = 0, $maxHeight = 0)
    {
        return preg_replace_callback("/\[img\]([^\<\r\n\"']+?)\[\/img\]/is", function ($m) use ($maxWidth, $maxHeight) {
            return PwSimpleUbbCode::_pushCode('createImg', $m[1], $maxWidth, $maxHeight);
        }, $message);
    }

    /**
     * 转化url标签.
     *
     * @param string $message 内容
     * @param int $checkurl
     *
     * @return string
     */
    public static function parseUrl($message, $checkurl = 0)
    {
        $message = preg_replace_callback("/\[url=((https?|ftp|gopher|news|telnet|mms|rtsp|thunder)?[^\[\s]+?)(\,(1)\/?)?\](.+?)\[\/url\]/is", function ($m) use ($checkurl) {
            return PwSimpleUbbCode::_pushCode('createUrl', $m[1], $m[5], $m[2], $m[4], $checkurl);
        }, $message);
        $message = preg_replace_callback("/\[url\]((https?|ftp|gopher|news|telnet|mms|rtsp|thunder)?[^\[\s]+?)\[\/url\]/is", function ($m) use ($checkurl) {
            return PwSimpleUbbCode::_pushCode('createUrl', $m[1], $m[1], $m[2], '0', $checkurl);
        }, $message);
        return $message;
    }

    /**
     * 转化code标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function parseCode($message)
    {
        return preg_replace_callback("/\[code(\sbrush\:(.+?)\;toolbar\:(true|false)\;)?\](.+?)\[\/code\]/is", function ($m) {
            return PwSimpleUbbCode::_pushCode('createCode', $m[4], $m[2], $m[3]);
        }, $message);
    }

    /**
     * 转化post标签.
     *
     * @param string $message 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function parsePost($message, $config)
    {
        return preg_replace_callback("/\[post\](.+?)\[\/post\]/is", function ($m) use ($config) {
            return PwSimpleUbbCode::_pushCode('createPost', $m[1], $config);
        }, $message);
    }

    /**
     * 转化hide标签.
     *
     * @param string $message 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function parseHide($message, $config)
    {
        return preg_replace_callback("/\[hide=(.+?)\](.+?)\[\/hide\]/is", function ($m) use ($config) {
            return PwSimpleUbbCode::_pushCode('createHide', $m[1], $m[2], $config);
        }, $message);
    }

    /**
     * 转化sell标签.
     *
     * @param string $message 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function parseSell($message, $config)
    {
        return preg_replace_callback("/\[sell=(.+?)\](.+?)\[\/sell\]/is", function ($m) use ($config) {
            return PwSimpleUbbCode::_pushCode('createSell', $m[1], $m[2], $config);
        }, $message);
    }

    /**
     * 转化quote标签.
     *
     * @param string $message 内容
     *
     * @return string
     */
    public static function parseQuote($message)
    {
        return preg_replace_callback("/\[quote(=(.+?)\,\d+)?\](.*?)\[\/quote\]/is", function ($m) {
            return PwSimpleUbbCode::_pushCode('createQoute', $m[3], $m[2]);
        }, $message);
    }

    /**
     * 转化flash标签.
     *
     * @param string $message 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function parseFlash($message, $config)
    {
        if ($config->isConvertFlash) {
            return preg_replace_callback("/\[flash(=(\d+?)\,(\d+?)(\,(0|1))?)?\]([^\[\<\r\n\"']+?)\[\/flash\]/is", function ($m) {
                return PwSimpleUbbCode::_pushCode('createPlayer', $m[6], $m[2], $m[3], $m[5], 'video');
            }, $message);
        }
        return preg_replace_callback("/\[flash(=(\d+?)\,(\d+?)(\,(0|1))?)?\]([^\[\<\r\n\"']+?)\[\/flash\]/is", function ($m) {
            return PwSimpleUbbCode::_pushCode('createFlashLink', $m[6]);
        }, $message);
    }

    /**
     * 转化 wmv|mp3|rm 等视频媒体标签.
     *
     * @param string $message 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function parseMedia($message, $config)
    {
        if ($config->isConvertMedia == 2) {
            $message = preg_replace_callback("/\[(wmv|mp3)(=(0|1))?\]([^\<\r\n\"']+?)\[\/\\1\]/is", function ($m) {
                return PwSimpleUbbCode::_pushCode('createPlayer', $m[4], '314', '53', $m[3], 'audio');
            }, $message);
            $message = preg_replace_callback("/\[(wmv|rm)(=([0-9]{1,3})\,([0-9]{1,3})\,(0|1))?\]([^\<\r\n\"']+?)\[\/\\1\]/is", function ($m) {
                return PwSimpleUbbCode::_pushCode('createPlayer', $m[6], $m[3], $m[4], $m[5], 'video');
            }, $message);
            return $message;
        }
        return preg_replace_callback(array(
            "/\[(mp3|wmv)(?:=[01]{1})?\]([^\<\r\n\"']+?)\[\/\\1\]/is",
            "/\[(wmv|rm)(?:=[0-9]{1,3}\,[0-9]{1,3}\,[01]{1})?\]([^\<\r\n\"']+?)\[\/\\1\]/is",
        ), function ($m) {
            return PwSimpleUbbCode::_pushCode('createMediaLink', $m[2]);
        }, $message);
    }

    public static function parseRemind($message, $remindUser)
    {
        return preg_replace_callback(
            '/@([\x7f-\xff\dA-Za-z\.\_]+)(?=\s?)/i',
            function ($m) use ($remindUser) {
                return PwUbbCode::createRemind($m[1], $remindUser);
            },
            $message
        );
    }

    /**
     * 转化iframe标签.
     *
     * @param string $message 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function parseIframe($message, $config)
    {
        return preg_replace_callback("/\[iframe\]([^\[\<\r\n\"']+?)\[\/iframe\]/is", function ($m) use ($config) {
            return PwSimpleUbbCode::_pushCode('createIframe', $m[1], $config);
        }, $message);
    }

    protected static function _init()
    {
        self::$_code = array();
        self::$_isSubstr = false;
        self::$_hide = false;
    }

    protected static function _pushCode()
    {
        $args = func_get_args();
        $length = array_push(self::$_code, $args);
        return "<\twind_code_" . ($length - 1) . "\t>";
    }

    /**
     * 检测内容中是否包含标签.
     *
     * @param string $message 内容
     * @param string $tag 标签
     *
     * @return bool
     */
    public static function hasTag($message, $tag)
    {
        $startTag = '[' . $tag;
        $endTag = '[/' . $tag . ']';
        if (strpos($message, $startTag) !== false && strpos($message, $endTag) !== false) {
            return true;
        }
        return false;
    }


    /**
     * 过滤ubb标签属性防止xss注入.
     */
    public static function filterUbb($matches)
    {
        $fillter_reg = '/javascript|onafterprint|onbeforeprint|onbeforeunload|onerror|onhaschange|onload|onmessage|onoffline|ononline|onpagehide|onpageshow|onpopstate|onredo|onresize|onstorage|onundo|onunload|onblur|onchange|oncontextmenu|onfocus|onformchange|onforminput|oninput|oninvalid|onreset|onselect|onsubmit|onkeydown|onkeypress|onkeyup|onclick|ondblclick|ondrag|ondragend|ondragenter|ondragleave|ondragover|ondragstart|ondrop|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onmousewheel|onscroll|onabort|oncanplay|oncanplaythrough|ondurationchange|onemptied|onended|onerror|onloadeddata|onloadedmetadata|onloadstart|onpause|onplay|onplaying|onprogress|onratechange|onreadystatechange|onseeked|onseeking|onstalled|onsuspend|ontimeupdate|onvolumechange|onwaiting|expression/is';
        foreach ($matches as $k => $v) {
            if ($k > 0) {
                $newstr = preg_replace($fillter_reg, '_', $v);
                $matches[0] = str_replace($v, $newstr, $matches[0]);
            }
        }

        return $matches[0];
    }

    /**
     * 转化ubb标签.
     *
     * @param string $message
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function convert($message, $length, PwUbbCodeConvertConfig $config = null)
    {
        is_null($config) && $config = new PwUbbCodeConvertConfig();
        self::_init();
        //需要单独对ubb属性做xss过滤
        $specialFilterArray = array(
            "/\[code(\s*brush\:(.+?)\;toolbar\:(true|false)\;)?\].+?\[\/code\]/is",
            "/\[list=([aA1]?)\].+?\[\/list\]/is",
            "/\[img\]([^\<\(\r\n\"']+?)\[\/img\]/is",
            "/\[url=((https?|ftp|gopher|news|telnet|mms|rtsp|thunder|ed2k)?[^\[\s]+?)(\,(1)\/?)?\].+?\[\/url\]/is",
            "/\[url\]((https?|ftp|gopher|news|telnet|mms|rtsp|thunder|ed2k)?[^\[\s]+?)\[\/url\]/is",
            "/\[flash(=(\d+?)\,(\d+?)(\,(0|1))?)?\]([^\[\<\(\r\n\"']+?)\[\/flash\]/is",
            "/\[(wmv|mp3)(=(0|1))?\]([^\<\(\r\n\"']+?)\[\/\\1\]/is",
            "/\[(wmv|rm)(=([0-9]{1,3})\,([0-9]{1,3})\,(0|1))?\]([^\<\(\r\n\"']+?)\[\/\\1\]/is",
            "/\[iframe\]([^\[\<\(\r\n\"']+?)\[\/iframe\]/is",
            "/\[hide=(.+?)\].+?\[\/hide\]/is",//加密内容
            "/\[quote(=(.+?)\,(\d+))?\].*?\[\/quote\]/is",
        );
        foreach ($specialFilterArray as $key => $reg) {
            $message = preg_replace_callback($reg, 'self::filterUbb', $message);
        }
        self::hasTag($message, 'code') && $message = self::parseCode($message);
        $message = self::convertTag($message, array('u', 'b', 'i', 'sub', 'sup', 'strike', 'blockquote'));
        $message = self::convertHr($message);
        $message = self::convertList($message);
        $message = self::convertFont($message);
        $message = self::convertColor($message);
        $message = self::convertBackColor($message);
        $message = self::convertSize($message);
        $message = self::convertEmail($message);
        $message = self::convertAlign($message);
        $message = self::convertGlow($message);

        strpos($message, '[s:') !== false && $message = self::parseEmotion($message);
        $message = self::parseAttachment($message, $config);
        self::hasTag($message, 'img') && $message = self::parseImg($message, 700, 700);
        self::hasTag($message, 'url') && $message = self::parseUrl($message);
        self::hasTag($message, 'flash') && $message = self::parseFlash($message, $config);
        $config->remindUser && $message = self::parseRemind($message, $config->remindUser);
        $config->isConvertMedia && $message = self::parseMedia($message, $config);
        $config->isConvertIframe && self::hasTag($message, 'iframe') && $message = self::parseIframe($message, $config);
        $config->isConvertPost && self::hasTag($message, 'post') && $message = self::parsePost($message, $config);
        $config->isConvertHide && self::hasTag($message, 'hide') && $message = self::parseHide($message, $config);
        $config->isConvertSell && self::hasTag($message, 'sell') && $message = self::parseSell($message, $config);
        self::hasTag($message, 'quote') && $message = self::parseQuote($message);
        $config->isConvertTable && $message = self::convertTable($message, $config->isConvertTable);
        $message = self::convertParagraph($message);
        list($message) = self::_subConvert($message, $length);

        return $message;
    }

    public static function isSubstr()
    {
        return self::$_isSubstr || self::$_hide;
    }

    protected static function _subConvert($message, $maxlen)
    {
        $str = '';
        $length = 0;
        $array = preg_split('/<\twind_code_(\d+)\t>/is', $message, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($array as $key => $value) {
            if ($key % 2 == 0) {
                list($value, $strlen) = self::_substrs($value, $maxlen);
            } else {
                $args = self::$_code[$value];
                $method = array_shift($args);
                array_unshift($args, $maxlen);
                list($value, $strlen) = call_user_func_array(array('self', $method), $args);
            }
            $str .= $value;
            $maxlen -= $strlen;
            $length += $strlen;
            if ($maxlen <= 0 || self::$_isSubstr) {
                break;
            }
        }
        return array($str, $length);
    }

    protected static function _substrs($message, $length)
    {
        $strlen = Pw::strlen($message);
        if ($strlen > $length) {
            $message = Pw::substrs($message, $length);
            $strlen = $length;
            self::$_isSubstr = true;
        }
        return array($message, $strlen);
    }

    /**
     * 生成表情html标签.
     *
     * @param int $key 表情序号
     *
     * @return string 表情html
     */
    public static function createEmotion($length, $key)
    {
        is_null(self::$_emotion) && self::$_emotion = Wekit::cache()->get('all_emotions');
        isset(self::$_emotion['name'][$key]) && $key = self::$_emotion['name'][$key];
        $emotion = isset(self::$_emotion['emotion'][$key]) ? self::$_emotion['emotion'][$key] : current(self::$_emotion['emotion']);
        $html = '<img src="' . Wekit::url()->images . '/emotion/' . $emotion['emotion_folder'] . '/' . $emotion['emotion_icon'] . '" />';
        return array($html, 1);
    }

    /**
     * 生成附件html标签.
     *
     * @param int $aid 附件id
     *
     * @return string 附件html
     */
    public static function createAttachment($length, $aid, $config)
    {
        return array($config->getAttachHtml($aid), 4);
    }

    /**
     * 生成img标签.
     *
     * @param string $path 图片地址
     * @param int $maxWidth 最大宽度限制
     * @param int $maxHeight 最大高度限制
     * @param string $original 原图地址
     *
     * @return string 图片html
     */
    public static function createImg($length, $path, $maxWidth = 0, $maxHeight = 0, $original = '')
    {
        return self::_substrs('[图片]', $length);
    }

    /**
     * 生成a标签.
     *
     * @param string $url 链接地址
     * @param string $name 链接内容
     * @param string $protocol 链接协议头
     * @param int $isdownload 链接是否为下载样式
     * @param int $checkurl
     *
     * @return string
     */
    public static function createUrl($length, $url, $name, $protocol, $isdownload = 0, $checkurl = 0)
    {
        list($name, $strlen) = self::_subConvert($name, $length);
        !$protocol && $url = '//' . $url;
        $attributes = '';
        $isdownload && $attributes .= ' class="down"';
        $html = "<a href=\"$url\" target=\"_blank\"{$attributes}>$name</a>";
        return array($html, $strlen);
    }

    /**
     * 生成code标签内容.
     *
     * @param string $str 内容
     * @param string $brush 代码语法
     * @param string $toolbar 是否有工具栏
     *
     * @return string
     */
    public static function createCode($length, $str, $brush, $toolbar)
    {
        $str = str_replace(array('&amp;lt;', '&amp;gt;'), array('&lt;', '&gt;'), $str);
        return self::_substrs($str, $length);
    }

    /**
     * 生成post标签内容.
     *
     * @param stirng $str 内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function createPost($length, $str, $config)
    {
        self::$_hide = true;
        return array('<span>[此处内容回复后可见]</span>', 9);
    }

    /**
     * 生成hide标签内容.
     *
     * @param int $cost 需要的积分
     * @param stirng $str 隐藏的内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function createHide($length, $cost, $str, $config)
    {
        self::$_hide = true;
        return array('<span>[此处内容加密]</span>', 6);
    }

    /**
     * 生成sell标签内容.
     *
     * @param int $cost 需要的积分
     * @param stirng $str 隐藏的内容
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function createSell($length, $cost, $str, $config)
    {
        self::$_hide = true;
        list($cost, $credit) = explode(',', $cost);
        $creditBo = PwCreditBo::getInstance();
        $cname = isset($creditBo->cType[$credit]) ? $creditBo->cType[$credit] : current($creditBo->cType);
        return array('<span>[以下帖子售价 ' . $cost . ' ' . $cname . '，购买后显示内容]</span>', 16);
    }

    /**
     * 生成quote标签内容.
     *
     * @param stirng $str 隐藏的内容
     *
     * @return string
     */
    public static function createQoute($length, $str, $username)
    {
        if ($username) {
            return self::_substrs('', $length);
        }
        return self::_subConvert($str, $length);
    }

    /**
     * 生成播放器.
     *
     * @param stirng $url url地址
     * @param int $width 宽度
     * @param int $height 高度
     * @param int $auto 是否为自动播放<1.是 2.否>
     * @param string $type 播放器类型 <可选: audio|video>
     *
     * @return string
     */
    public static function createPlayer($length, $url, $width = 0, $height = 0, $auto = 0, $type = 'video')
    {
        return self::_substrs($type == 'audio' ? '[音乐]' : '[视频]', $length);
    }

    /**
     * 生成 flash 链接.
     *
     * @param string $url
     *
     * @return string
     */
    public static function createFlashLink($length, $url)
    {
        return self::_substrs('[视频]', $length);
    }

    /**
     * 生成视频链接.
     *
     * @param string $url
     *
     * @return string
     */
    public static function createMediaLink($length, $url)
    {
        return self::_substrs('[视频]', $length);
    }

    public static function createRemind($length, $username, $uArray)
    {
        list($html, $strlen) = self::_substrs('@' . $username, $length);
        isset($uArray[$username]) && $html = '<a href="' . WindUrlHelper::createUrl('space/index/run', array('uid' => $uArray[$username])) . '">@' . $username . '</a>';
        return array($html, $strlen);
    }

    /**
     * 生成iframe标签内容.
     *
     * @param string $url
     * @param object $config ubb转换配置
     *
     * @return string
     */
    public static function createIframe($length, $url, $config)
    {
        list($name, $strlen) = self::_substrs($url, $length);
        return array("<a target=\"_blank\" href=\"$url \">$name</a>", $strlen);
    }

    /**
     * 生成table标签内容.
     *
     * @param string $text
     * @param int $width 宽度
     * @param string $bgColor 背景色
     * @param string $borderColor 边框色
     * @param int $borderWidth 边框大小
     *
     * @return string
     */
    public static function createTable($length, $text, $width = '', $bgColor = '', $borderColor = '', $borderWidth = '', $align = '')
    {
        return self::_substrs('[表格]', $length);
        //不显示表格内容
        $text = trim(str_replace(array('\\"', '<br />'), array('"', "\n"), $text));
        $text = preg_replace("/(\[\/td\]\s*)?\[\/tr\]\s*/is", array(
            '<br />',
            "<tr><td{$tdStyle}>"
        ), $text);
        $text = preg_replace_callback("/\[(tr|\/td)\]\s*\[td(=(\d{1,2}),(\d{1,2})(,(\d{1,3}(\.\d{1,2})?(%|px)?))?)?\]/is", function ($m) use ($tdStyle) {
            return PwSimpleUbbCode::_pushCode($m[1], $m[3], $m[4], $m[6], $tdStyle);
        }, $text);
        $text = str_replace('[tr]', '', $text);
        $text = str_replace("\n", '<br />', $text);

        return self::_substrs($text, $length);
    }

    /**
     * 生成td标签.
     *
     * @param string $tag 标签 <tr|td>
     * @param int $col 多列
     * @param int $row 多行
     * @param int $width 宽度
     * @param string $tdStyle 样式
     *
     * @return string
     */
    public static function createTd($tag, $col, $row, $width, $tdStyle = '')
    {
        return $tag == 'tr' ? '' : ' ';
    }
}
