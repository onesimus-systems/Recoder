<?php
namespace Shortcode;

class ShortcodeTests extends \PHPUnit_Framework_TestCase
{
    public static function getShortcodeProcessor()
    {
        $sc = new Shortcode();
        $sc->register('name', function($options) {
            return $options['_code'];
        });
        $sc->register('content', function($options) {
            return $options['_content'];
        });
        $sc->register('reverse', function($options) {
            return strrev($options['_content']);
        });
        $sc->register('options', function($options) {
            $r = [];
            foreach($options as $option => $value) {
                if ($option[0] !== '_') {
                    $r []= "$option:$value";
                }
            }
            return implode('&', $r);
        });
        $sc->register('url', function($options) {
            if ($options['_content'] !== '') {
                return '<a href="'.$options['_content'].'">'.$options['_content'].'</a>';
            }
            return '<a href="'.$options['url'].'">'.$options['url'].'</a>';
        });
        $sc->registerAlias('c', 'content');
        $sc->registerAlias('n', 'name');
        return $sc;
    }

    public function caseProvider()
    {
        return [
            ['[name]', 'name'],
            ['[content]random[/content]', 'random'],
            ['[content]象形字[/content]', '象形字'],
            ['xxx [content]象形字[/content] yyy', 'xxx 象形字 yyy'],
            ['xxx [content]ąćęłńóśżź ąćęłńóśżź[/content] yyy', 'xxx ąćęłńóśżź ąćęłńóśżź yyy'],
            ['[name]random[/other]', 'namerandom[/other]'],
            ['[name][other]random[/other]', 'name[other]random[/other]'],
            ['[content]random-[name]-random[/content]', 'random-name-random'],
            ['random [content]other[/content] various', 'random other various'],
            ['x [content]a-[name]-b[/content] y', 'x a-name-b y'],
            ['x [c]a-[n][/n]-b[/c] y', 'x a-n-b y'],
            ['x [content]a-[c]v[/c]-b[/content] y', 'x a-v-b y'],
            ['x [html]bold[/html] z', 'x [html]bold[/html] z'],
            ['x [reverse]abc xyz[/reverse] z', 'x zyx cba z'],
            ['x [i /][i]i[/i][i /][i]i[/i][i /] z', 'x [i /][i]i[/i][i /][i]i[/i][i /] z'],
            ['x [url url="http://giggle.com/search"] z', 'x <a href="http://giggle.com/search">http://giggle.com/search</a> z'],
            ['x [url="http://giggle.com/search"] z', 'x [url="http://giggle.com/search"] z'],
            ['x [url]http://giggle.com/search[/url] z', 'x <a href="http://giggle.com/search">http://giggle.com/search</a> z'],
            ['[options arg1=val1 arg2="val with space" arg3]', 'arg1:val1&arg2:val with space&arg3:1'],
        ];
    }

    /**
     * @dataProvider caseProvider
     */
    public function testContent($text, $expected)
    {
        $sc = $this->getShortcodeProcessor();
        $this->assertEquals($expected, $sc->process($text));
    }

    public function testCustomSyntax()
    {
        $sc = new Shortcode('{{', '}}');
        $sc->register('name', function($options) {
            return $options['_code'];
        });
        $sc->register('content', function($options) {
            return $options['_content'];
        });
        $this->assertEquals('name', $sc->process('{{name}}'));
        $this->assertEquals('zyx', $sc->process('{{content}}zyx{{/content}}'));
    }
}
