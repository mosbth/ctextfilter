<?php

namespace Mos\TextFilter;

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
     * @return array with text and boolean if more was detected.
     */
    public function getUntilMore($text)
    {
        $pos = stripos($text, "<!--more-->");
        $hasMore = $pos;
        if ($pos) {
            $text = substr($text, 0, $pos);
        }
        return [$text, $hasMore];
    }



    /**
     * Returns the excerpt of the text with at most the specified amount of characters.
     * 
     * @param int $chars the number of characters to return.
     * @param boolean $hard do a hard break at exactly $chars characters or find closest space.
     * @return string as the excerpt.
     */
/*    public function GetExcerpt($chars=139, $hard=false) {
      if(!isset($this->data['data_filtered'])) {
        return null;
      }
      $excerpt = strip_tags($this->data['data_filtered']);

      if(strlen($excerpt) > $chars) {
        $excerpt   = substr($excerpt, 0, $chars-1);
      }

      if(!$hard) {
        $lastSpace = strrpos($excerpt, ' ');
        $excerpt   = substr($excerpt, 0, $lastSpace);
      }

      return $excerpt;
    }
    
    
    /**
     * Returns the first paragraph ot the text.
     * 
     * @return string as the first paragraph.
     */
/*    public function GetFirstParagraph() {
      if(!isset($this->data['data_filtered'])) {
        return null;
      }
      $excerpt = $this->data['data_filtered'];

      $firstPara = strpos($excerpt, '</p>');
      $excerpt   = substr($excerpt, 0, $firstPara + 4);

      return $excerpt;
    }
*/
}
