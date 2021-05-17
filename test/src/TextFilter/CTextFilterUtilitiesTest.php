<?php

namespace Mos\TextFilter;

use PHPUnit\Framework\TestCase;

/**
 * A testclass
 *
 */
class CTextFilterUtilitiesTest extends TestCase
{
    /**
     * Provider for TextWithLinks
     *
     * @return array
     */
    public function providerTextWithLinks()
    {
        $baseurl = "doc";

        return [
            [
                $baseurl,
                "<a href=\"\">text</a>",
                "<a href=\"$baseurl\">text</a>",
            ],
            [
                $baseurl,
                "<a href=\"something\">text</a>",
                "<a href=\"$baseurl/something\">text</a>",
            ],
        ];
    }



     /**
      * Test.
      *
      * @dataProvider providerTextWithLinks
      *
      * @return void
      */
    public function testAddBaseurlToRelativeLinks($baseurl, $text, $exp)
    {
        $filter = new CTextFilter();

        $callback = function ($url, $baseurl) {
            return rtrim("$baseurl/$url", "/");
        };

        $res = $filter->addBaseurlToRelativeLinks($text, $baseurl, $callback);
        $this->assertEquals($exp, $res, "Relative links mssmatch");
    }
}
