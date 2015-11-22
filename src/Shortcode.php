<?php
namespace Shortcode;

class Shortcode
{
    private $opening = '[';
    private $closing = ']';
    private $codes = [];

    public function __construct($open=null, $close=null)
    {
        $open = $open ?: $this->opening;
        $close = $close ?: $this->closing;
        $this->setDelimiters($open, $close);
    }

    public function setDelimiters($open, $close=null)
    {
        $this->opening = $open;
        $this->closing = $close ?: $open;
    }

    public function process()
    {
        $args = func_get_args();
        $text = array_shift($args);
        while(true) {
            $sc = $this->getShortCodes($text);
            if (empty($sc)) {
                break;
            }
            $text = $this->processCodes($text, $sc, $args);
        }
        return $text;
    }

    public function register($code, callable $func)
    {
        $this->codes[$code] = $func;
    }

    public function registerAlias($alias, $code)
    {
        $this->codes[$alias] = "_$code";
    }

    public function unregister($code = null)
    {
        if ($code === null) {
            $this->codes = [];
            return;
        }

        if (array_key_exists($code, $this->codes)) {
            unset($this->codes[$code]);
        }
    }

    protected function getShortCodes($text)
    {
        $opening = preg_quote($this->opening, '~');
        $closing = preg_quote($this->closing, '~');
        $shortcodeRegex = "~$opening([\w-]*)?((?:\s(?:[\w-]*)(?:=(?:\".*?\"|\S+))?)*)$closing(?:(.*?)$opening\/\\1$closing)?~";
        $optionsRegex = "~(?:\s([\w-]*)(?:=(?:\"(.*?)\"|(\S+)))?)~";

        if (!preg_match_all($shortcodeRegex, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $codes = [];
        foreach($matches as $match) {
            if ($this->getCodeCallable($match[1][0]) === null) {
                continue;
            }
            preg_match_all($optionsRegex, $match[2][0], $matches2, PREG_SET_ORDER);

            $options = [
                '_raw' => $match[0][0],
                '_code' => $match[1][0],
                '_offset' => $match[0][1],
                '_length' => mb_strlen($match[0][0]),
                '_content' => isset($match[3]) ? $match[3][0] : '',
            ];
            foreach($matches2 as $option) {
                if (count($option) === 2) {
                    $options[$option[1]] = true;
                } else{
                    $options[$option[1]] = $option[2] ?: $option[3];
                }
            }
            $codes []= $options;
        }
        return $codes;
    }

    protected function processCodes($text, $codes, $args)
    {
        $offsetCorrection = 0;
        foreach($codes as $code) {
            $sc = $code['_code'];
            $code['_offset'] += $offsetCorrection;
            $func = $this->getCodeCallable($sc);
            if ($func === null) {
                continue;
            }

            $newargs = [$code];
            foreach ($args as $arg) {
                $newargs []= $arg;
            }
            $replacement = call_user_func_array($func, $newargs);
            if ($replacement !== null) {
                $text = mb_substr($text, 0, $code['_offset']).$replacement.mb_substr($text, $code['_offset']+$code['_length']);
                $offsetCorrection += mb_strlen($replacement) - mb_strlen($code['_raw']);
            }
        }
        return $text;
    }

    protected function getCodeCallable($code)
    {
        if (!array_key_exists($code, $this->codes)) {
            return null;
        }

        $func = $this->codes[$code];
        if (is_string($func)) {
            $aliased = substr($func, 1);
            return $this->getCodeCallable($aliased);
        }
        return $func;
    }
}
