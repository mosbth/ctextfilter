<?php

namespace Mos\TextFilter;

/**
 * A testclass
 *
 */
class CTextFilterTest extends \PHPUnit_Framework_TestCase
{
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
        $res = $filter->doFilter("void", "no-such-filter");
    }
}
