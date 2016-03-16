<?php

namespace Anax\Content;

/**
 * Utilities when working with text.
 *
 */
trait TTextUtilities
{
    /**
     * Get text until <!--stop--> or all text.
     *
     * @param string $text with content
     *
     * @return string with text
     */
    public function getUntilStop($text)
    {
        $pos = stripos($text, "<!--stop-->");
        if ($pos) {
            $text = substr($text, 0, $pos);
        }
        return $text;
    }


    /**
     * Get text until <!--more--> or all text.
     *
     * @param string $text with content
     *
     * @return string with text
     */
    public function getUntilMore($text)
    {
        $pos = stripos($text, "<!--more-->");
        $hasMore = $pos;
        if ($pos) {
            $text = substr($text, 0, $pos);
        }
        return $text;
    }
}
