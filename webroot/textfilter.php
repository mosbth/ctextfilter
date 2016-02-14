<?php
include(__DIR__.'/../vendor/autoload.php');


// Prepare the content
$text = <<<EOD
My Article
=================================

This is an example on writing text and filtering it to become HTML.



Markdown used
---------------------------------

The class uses *markdown* and a external PHP class [php-markdown](http://daringfireball.net/projects/markdown/).



Clickable
---------------------------------

Som links can become clickable links, such as this to http://example.com/.



BBCode
---------------------------------

BBCode [i]is supported[/i] with some [b]limited tags[/b], but quite easy to extend.



ShortCode
---------------------------------

These are own *shortcodes* such as this image with a caption, wrapped in a `<figure>` element.

[FIGURE src="https://www.gravatar.com/avatar/67aaf77308040cd57f0eba43e9f5404a?s=200" caption="Me with a caption."]

EOD;



// Filter the content
$filter = new \Mos\TextFilter\CTextFilter();
$document = $filter->parse($text, ["markdown", "shortcode", "clickable", "bbcode"]);
?>

<!doctype html>
<meta charset="utf-8">
<title>Example on Mos\TextFilter</title>
<?=$document->text?>
