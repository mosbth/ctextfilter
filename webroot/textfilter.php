<?php
/**
 * This is a Anax pagecontroller.
 *
 */
// Include the essential config-file which also creates the $anax variable with its defaults.
include(__DIR__.'/config.php');


// Prepare the content
$html = <<<EOD
Detta är ett exempel på markdown
=================================

En länk till [Markdowns hemsida](http://daringfireball.net/projects/markdown/).

EOD;



// Filter the content
$filter = new CTextFilter();
$html = $filter->doFilter($html, "markdown");



// Do it and store it all in variables in the Anax container.
$anax['title'] = "Kasta tärning";
$anax['main'] = $html;



// Finally, leave it all to the rendering phase of Anax.
include(ANAX_THEME_PATH);
