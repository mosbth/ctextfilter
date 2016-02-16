<?php

namespace Mos\TextFilter;

/**
 * A testclass
 *
 */
class CTextFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Supported filters.
     */
    private $standardFilters = [
         'yamlfrontmatter',
         'bbcode',
         'clickable',
         'markdown',
         'nl2br',
         'shortcode',
         'purify',
         'titlefromh1',
     ];



     /**
      * Test.
      *
      * @return void
      */
    public function testTitleFromFirstH1()
    {
        $filter = new CTextFilter();

        $text = "";
        $res = $filter->parse($text, ["titlefromh1"]);
        $title = $res->frontmatter["title"];
        $this->assertNull($title, "Title should be null");

        $text = "<h1>My title</h1>";
        $exp = "My title";
        $res = $filter->parse($text, ["titlefromh1"]);
        $title = $res->frontmatter["title"];
        $this->assertEquals($exp, $title, "Title missmatch");

        $text = "<h1><a href=''>My title</a></h1>";
        $exp = "My title";
        $res = $filter->parse($text, ["titlefromh1"]);
        $title = $res->frontmatter["title"];
        $this->assertEquals($exp, $title, "Title missmatch");

        $text = "<h1 class=''>My title</h1>";
        $exp = "My title";
        $res = $filter->parse($text, ["titlefromh1"]);
        $title = $res->frontmatter["title"];
        $this->assertEquals($exp, $title, "Title missmatch");
    }



     /**
      * Test.
      *
      * @expectedException /Mos/TextFilter/Exception
      *
      * @return void
      */
    public function testJsonFrontMatterException()
    {
        $filter = new CTextFilter();

        $text = <<<EOD
{{{

}}}
EOD;
        $filter->parse($text, ["jsonfrontmatter"]);
    }



     /**
      * Test.
      *
      * @return void
      */
    public function testJsonFrontMatter()
    {
        $filter = new CTextFilter();

        $text = "";
        $res = $filter->parse($text, ["jsonfrontmatter"]);
        $this->assertNull($res->frontmatter, "Frontmatter should be null");
        $this->assertEmpty($res->text, "Text should be empty");

        $text = <<<EOD
{{{
}}}

EOD;
        $res = $filter->parse($text, ["jsonfrontmatter"]);
        $this->assertEmpty($res->frontmatter, "Frontmatter should be empty");
        $this->assertEmpty($res->text, "Text should be empty");

        $txt = "TEXT";
        $text = <<<EOD
{{{
{
    "key": "value"
}
}}}
$txt
EOD;
        $res = $filter->parse($text, ["jsonfrontmatter"]);
        $this->assertEquals(
            $res->frontmatter,
            [
                "key" => "value"
            ],
            "Frontmatter should be empty"
        );
        $this->assertEquals($txt, $res->text, "Text missmatch");
    }



    /**
     * Test.
     *
     * @expectedException /Mos/TextFilter/Exception
     *
     * @return void
     */
    public function testYamlFrontMatterException()
    {
        if (!function_exists("yaml_parse")) {
            return;
        }

        $filter = new CTextFilter();

        $text = <<<EOD
---

---
EOD;
        $filter->parse($text, ["yamlfrontmatter"]);
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testYamlFrontMatter()
    {
        if (!function_exists("yaml_parse")) {
            return;
        }

        $filter = new CTextFilter();

        $text = "";
        $res = $filter->parse($text, ["yamlfrontmatter"]);
        $this->assertNull($res->frontmatter, "Frontmatter should be null");
        $this->assertEmpty($res->text, "Text should be empty");

        $text = <<<EOD
---
---

EOD;
        $res = $filter->parse($text, ["yamlfrontmatter"]);
        $this->assertEmpty($res->frontmatter, "Frontmatter should be empty");
        $this->assertEmpty($res->text, "Text should be empty");

        $txt = "TEXT";
        $text = <<<EOD
---
key: value
---
$txt
EOD;
        $res = $filter->parse($text, ["yamlfrontmatter"]);
        $this->assertEquals(
            $res->frontmatter,
            [
                "key" => "value"
            ],
            "Frontmatter not matching"
        );
        $this->assertEquals($txt, $res->text, "Text missmatch");

        $text = <<<EOD
---
key1: value1
key2: This is a long sentence.
---
My Article
=================================

This is an example on writing text and adding a YAML frontmatter.

EOD;
        $res = $filter->parse($text, ["yamlfrontmatter", "markdown"]);
        //var_dump($res);
        $this->assertEquals(
            $res->frontmatter,
            [
                "key1" => "value1",
                "key2" => "This is a long sentence."
            ],
            "Frontmatter not matching"
        );
        //$this->assertEquals($txt, $res->text, "Text missmatch");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testGetFilters()
    {
        $filter = new CTextFilter();

        $filters = $filter->getFilters();
        $res = array_diff($this->standardFilters, $filters);
        $this->assertTrue(empty($res), "Missmatch standard filters.");
    }




    /**
     * Test.
     *
     * @return void
     */
    public function testHasFilter()
    {
        $filter = new CTextFilter();

        $res = $filter->hasFilter("markdown");
        $this->assertTrue($res, "Missmatch has filters.");
    }




    /**
     * Test.
     *
     * @expectedException /Mos/TextFilter/Exception
     *
     * @return void
     */
    public function testHasFilterException()
    {
        $filter = new CTextFilter();

        $filter->hasFilter("NOT EXISTING");
    }




    /**
     * Test.
     *
     * @return void
     */
    public function testPurifier()
    {
        $filter = new CTextFilter();

        $text = "Header\n=========";
        $exp  = "<h1>Header</h1>\n";
        $res = $filter->parse($text, ["markdown", "purify"]);
        $this->assertEquals($exp, $res->text, "Purify failed");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testMarkdown()
    {
        $filter = new CTextFilter();

        $html = "Header\n=========";
        $exp  = "<h1>Header</h1>\n";
        $res = $filter->doFilter($html, "markdown");
        $this->assertEquals($exp, $res, "Markdown <h1> failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testMarkdownAndBBCode()
    {
        $filter = new CTextFilter();

        $html = "Header[b]text[/b]\n=========";
        $exp  = "<h1>Header<strong>text</strong></h1>\n";
        $res = $filter->doFilter($html, "markdown, bbcode");
        $this->assertEquals($exp, $res, "Markdown <h1> failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testMarkdownAndBBCodeAsArray()
    {
        $filter = new CTextFilter();

        $html = "Header[b]text[/b]\n=========";
        $exp  = "<h1>Header<strong>text</strong></h1>\n";
        $res = $filter->doFilter($html, ["markdown", "bbcode"]);
        $this->assertEquals($exp, $res, "Markdown <h1> failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testMarkdownArray()
    {
        $filter = new CTextFilter();

        $html = "Header\n=========";
        $exp  = "<h1>Header</h1>\n";
        $res = $filter->doFilter($html, ["markdown"]);
        $this->assertEquals($exp, $res, "Markdown <h1> failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testUppercase()
    {
        $filter = new CTextFilter();

        $html = "Header\n=========";
        $exp  = "<h1>Header</h1>\n";
        $res = $filter->doFilter($html, "MARKDOWN");
        $this->assertEquals($exp, $res, "Markdown <h1> failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testBBCode()
    {
        $filter = new CTextFilter();

        $html = "[b]text[/b]";
        $exp  = "<strong>text</strong>";
        $res = $filter->doFilter($html, "bbcode");
        $this->assertEquals($exp, $res, "BBCode [b] failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testClickable()
    {
        $filter = new CTextFilter();

        $html = "http://example.com/humans.txt";
        $exp  = "<a href='$html'>$html</a>";
        $res = $filter->doFilter($html, "clickable");
        $this->assertEquals($exp, $res, "clickable failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testNl2Br()
    {
        $filter = new CTextFilter();

        $html = "hej\nhej";
        $exp  = "hej<br />\nhej";
        $res = $filter->doFilter($html, "nl2br");
        $this->assertEquals($exp, $res, "nl2br failed: '$res'");
    }



    /**
     * Test.
     *
     * @return void
     */
    public function testShortCodeFigure()
    {
        $filter = new CTextFilter();

        $src = "/img/me.png";
        $caption = "This is me.";
        
        $html = <<<EOD
[FIGURE src=$src caption="$caption"]
EOD;

        $exp  = <<<EOD
<figure class='figure'>
<a href='$src'><img src='$src' alt='$caption'/></a>
<figcaption markdown=1>$caption</figcaption>
</figure>
EOD;
        $res = $filter->doFilter($html, "shortcode");
        $this->assertEquals($exp, $res, "shortcode failed: '$res'");
    }



    /**
     * Test.
     *
     * @expectedException Exception
     *
     * @return void
     */
    public function testDoItException()
    {
        $filter = new CTextFilter();
        $filter->doFilter("void", "no-such-filter");
    }
}
