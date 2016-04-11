<?php

namespace Mos\TextFilter;

/**
 * Filter and format content.
 *
 */
class CTextFilter
{
    use TTextUtilities;



    /**
     * Supported filters.
     */
    private $filters = [
        "jsonfrontmatter",
        "yamlfrontmatter",
        "bbcode",
        "clickable",
        "shortcode",
        "markdown",
        "geshi",
        "nl2br",
        "purify",
        "titlefromh1",
        "anchor4Header",
     ];



     /**
      * Current document parsed.
      */
    private $current;



    /**
     * Hold meta information for filters to use.
     */
    private $meta = [];



    /**
     * Call each filter.
     *
     * @deprecated deprecated since version 1.2 in favour of parse().
     *
     * @param string       $text    the text to filter.
     * @param string|array $filters as comma separated list of filter,
     *                              or filters sent in as array.
     *
     * @return string the formatted text.
     */
    public function doFilter($text, $filters)
    {
        // Define all valid filters with their callback function.
        $callbacks = [
            'bbcode'    => 'bbcode2html',
            'clickable' => 'makeClickable',
            'shortcode' => 'shortCode',
            'markdown'  => 'markdown',
            'nl2br'     => 'nl2br',
            'purify'    => 'purify',
        ];

        // Make an array of the comma separated string $filters
        if (is_array($filters)) {
            $filter = $filters;
        } else {
            $filters = strtolower($filters);
            $filter = preg_replace('/\s/', '', explode(',', $filters));
        }

        // For each filter, call its function with the $text as parameter.
        foreach ($filter as $key) {

            if (!isset($callbacks[$key])) {
                throw new Exception("The filter '$filters' is not a valid filter string due to '$key'.");
            }
            $text = call_user_func_array([$this, $callbacks[$key]], [$text]);
        }

        return $text;
    }



    /**
     * Set meta information that some filters can use.
     *
     * @param array $meta values for filters to use.
     *
     * @return void
     */
    public function setMeta($meta)
    {
        return $this->meta = $meta;
    }



    /**
     * Return an array of all filters supported.
     *
     * @return array with strings of filters supported.
     */
    public function getFilters()
    {
        return $this->filters;
    }



    /**
     * Check if filter is supported.
     *
     * @param string $filter to use.
     *
     * @throws mos/TextFilter/Exception  when filter does not exists.
     *
     * @return boolean true if filter exists, false othwerwise.
     */
    public function hasFilter($filter)
    {
        return in_array($filter, $this->filters);
    }



    /**
     * Add array items to frontmatter.
     *
     * @param array|null $matter key value array with items to add
     *                           or null if empty.
     *
     * @return $this
     */
    private function addToFrontmatter($matter)
    {
        if (empty($matter)) {
            return $this;
        }

        if (is_null($this->current->frontmatter)) {
            $this->current->frontmatter = [];
        }

        $this->current->frontmatter = array_merge($this->current->frontmatter, $matter);
        return $this;
    }



    /**
     * Call a specific filter and store its details.
     *
     * @param string $filter to use.
     *
     * @throws mos/TextFilter/Exception when filter does not exists.
     *
     * @return string the formatted text.
     */
    private function parseFactory($filter)
    {
        // Define single tasks filter with a callback.
        $callbacks = [
            "bbcode"    => "bbcode2html",
            "clickable" => "makeClickable",
            "shortcode" => "shortCode",
            "markdown"  => "markdown",
            "geshi"     => "syntaxHighlightGeSHi",
            "nl2br"     => "nl2br",
            "purify"    => "purify",
            'anchor4Header' => 'createAnchor4Header',
        ];

        // Do the specific filter
        $text = $this->current->text;
        switch ($filter) {
            case "jsonfrontmatter":
                $res = $this->jsonFrontMatter($text);
                $this->current->text = $res["text"];
                $this->addToFrontmatter($res["frontmatter"]);
                break;

            case "yamlfrontmatter":
                $res = $this->yamlFrontMatter($text);
                $this->current->text = $res["text"];
                $this->addToFrontmatter($res["frontmatter"]);
                break;

            case "titlefromh1":
                $title = $this->getTitleFromFirstH1($text);
                $this->current->text = $text;
                if (!isset($this->current->frontmatter["title"])) {
                    $this->addToFrontmatter(["title" => $title]);
                }
                break;

            case "bbcode":
            case "clickable":
            case "shortcode":
            case "markdown":
            case "geshi":
            case "nl2br":
            case "purify":
            case "anchor4Header":
                $this->current->text = call_user_func_array(
                    [$this, $callbacks[$filter]],
                    [$text]
                );
                break;

            default:
                throw new Exception("The filter '$filter' is not a valid filter     string.");
        }
    }



    /**
     * Call each filter and return array with details of the formatted content.
     *
     * @param string $text   the text to filter.
     * @param array  $filter array of filters to use.
     *
     * @throws mos/TextFilter/Exception  when filterd does not exists.
     *
     * @return array with the formatted text and additional details.
     */
    public function parse($text, $filter)
    {
        $this->current = new \stdClass();
        $this->current->frontmatter = null;
        $this->current->text = $text;

        foreach ($filter as $key) {
            $this->parseFactory($key);
        }

        $this->current->text = $this->getUntilStop($this->current->text);

        return $this->current;
    }



    /**
     * Add excerpt as short version of text if available.
     *
     * @param object &$current same structure as returned by parse().
     *
     * @return void.
     */
    public function addExcerpt($current)
    {
        list($excerpt, $hasMore) = $this->getUntilMore($current->text);
        $current->excerpt = $excerpt;
        $current->hasMore = $hasMore;
    }



    /**
     * Extract front matter from text.
     *
     * @param string $text       the text to be parsed.
     * @param string $startToken the start token.
     * @param string $stopToken  the stop token.
     *
     * @return array with the formatted text and the front matter.
     */
    private function extractFrontMatter($text, $startToken, $stopToken)
    {
        $tokenLength = strlen($startToken);

        $start = strpos($text, $startToken);
        // Is a valid start?
        if ($start !== false && $start !== 0) {
            if ($text[$start - 1] !== "\n") {
                $start = false;
            }
        }

        $frontmatter = null;
        if ($start !== false) {
            $stop = strpos($text, $stopToken, $tokenLength - 1);

            if ($stop !== false && $text[$stop - 1] === "\n") {
                $length = $stop - ($start + $tokenLength);

                $frontmatter = substr($text, $start + $tokenLength, $length);
                $textStart = substr($text, 0, $start);
                $text = $textStart . substr($text, $stop + $tokenLength);
            }
        }

        return [$text, $frontmatter];
    }



    /**
     * Extract JSON front matter from text.
     *
     * @param string $text the text to be parsed.
     *
     * @return array with the formatted text and the front matter.
     */
    public function jsonFrontMatter($text)
    {
        list($text, $frontmatter) = $this->extractFrontMatter($text, "{{{\n", "}}}\n");

        if (!empty($frontmatter)) {
            $frontmatter = json_decode($frontmatter, true);

            if (is_null($frontmatter)) {
                throw new Exception("Failed parsing JSON frontmatter.");
            }
        }

        return [
            "text" => $text,
            "frontmatter" => $frontmatter
        ];
    }



    /**
     * Extract YAML front matter from text.
     *
     * @param string $text the text to be parsed.
     *
     * @return array with the formatted text and the front matter.
     */
    public function yamlFrontMatter($text)
    {
        list($text, $frontmatter) = $this->extractFrontMatter($text, "---\n", "...\n");

        if (function_exists("yaml_parse") && !empty($frontmatter)) {
            $frontmatter = yaml_parse("---\n$frontmatter...\n");

            if ($frontmatter === false) {
                throw new Exception("Failed parsing YAML frontmatter.");
            }
        }

        return [
            "text" => $text,
            "frontmatter" => $frontmatter
        ];
    }



    /**
     * Get the title from the first H1.
     *
     * @param string $text the text to be parsed.
     *
     * @return string|null with the title, if its found.
     */
    public function getTitleFromFirstH1($text)
    {
        $matches = [];
        $title = null;

        if (preg_match("#<h1.*?>(.*)</h1>#", $text, $matches)) {
            $title = strip_tags($matches[1]);
        }

        return $title;
    }



    /**
     * Helper, BBCode formatting converting to HTML.
     *
     * @param string $text The text to be converted.
     *
     * @return string the formatted text.
     *
     * @link http://dbwebb.se/coachen/reguljara-uttryck-i-php-ger-bbcode-formattering
     */
    public function bbcode2html($text)
    {
        $search = [
            '/\[b\](.*?)\[\/b\]/is',
            '/\[i\](.*?)\[\/i\]/is',
            '/\[u\](.*?)\[\/u\]/is',
            '/\[img\](https?.*?)\[\/img\]/is',
            '/\[url\](https?.*?)\[\/url\]/is',
            '/\[url=(https?.*?)\](.*?)\[\/url\]/is'
        ];

        $replace = [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<u>$1</u>',
            '<img src="$1" />',
            '<a href="$1">$1</a>',
            '<a href="$1">$2</a>'
        ];

        return preg_replace($search, $replace, $text);
    }



    /**
     * Make clickable links from URLs in text.
     *
     * @param string $text the text that should be formatted.
     *
     * @return string with formatted anchors.
     *
     * @link http://dbwebb.se/coachen/lat-php-funktion-make-clickable-automatiskt-skapa-klickbara-lankar
     */
    public function makeClickable($text)
    {
        return preg_replace_callback(
            '#\b(?<![href|src]=[\'"])https?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#',
            function ($matches) {
                return "<a href='{$matches[0]}'>{$matches[0]}</a>";
            },
            $text
        );
    }



    /**
     * Syntax highlighter using GeSHi http://qbnz.com/highlighter/.
     *
     * @param string $text     text to be converted.
     * @param string $language which language to use for highlighting syntax.
     *
     * @return string the formatted text.
     */
    public function syntaxHighlightGeSHi($text, $language = "text")
    {
        $language = $language ?: "text";
        $language = ($language === 'html') ? 'html4strict' : $language;
        $geshi = new \GeSHi($text, $language);
        $geshi->set_overall_class('geshi');
        $geshi->enable_classes('geshi');
        //$geshi->set_header_type(GESHI_HEADER_PRE_VALID);
        //$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
        //echo "<pre>", $geshi->get_stylesheet(false) , "</pre>"; exit;

        $code = $geshi->parse_code();

        // Replace last &nbsp;</pre>, -strlen("&nbsp;</pre>") == 12
        $code = substr_replace($code, "</pre>", -12);
        return $code;
    }



    /**
     * Format text according to HTML Purifier.
     *
     * @param string $text that should be formatted.
     *
     * @return string as the formatted html-text.
     */
    public function purify($text)
    {
        $config   = \HTMLPurifier_Config::createDefault();
        $config->set("Cache.DefinitionImpl", null);
        //$config->set('Cache.SerializerPath', '/home/user/absolute/path');

        $purifier = new \HTMLPurifier($config);
    
        return $purifier->purify($text);
    }



    /**
     * Format text according to Markdown syntax.
     *
     * @param string $text the text that should be formatted.
     *
     * @return string as the formatted html-text.
     */
    public function markdown($text)
    {
        return \Michelf\MarkdownExtra::defaultTransform($text);
    }



    /**
     * For convenience access to nl2br
     *
     * @param string $text text to be converted.
     *
     * @return string the formatted text.
     */
    public function nl2br($text)
    {
        return nl2br($text);
    }



    /**
     * Shortcode to to quicker format text as HTML.
     *
     * @param string $text text to be converted.
     *
     * @return string the formatted text.
     */
    public function shortCode($text)
    {
        /* Needs PHP 7
        $patternsAndCallbacks = [
            "/\[(FIGURE)[\s+](.+)\]/" => function ($match) {
                return self::ShortCodeFigure($matches[2]);
            },
            "/(```([\w]*))\n([^`]*)```[\n]{1}/s" => function ($match) {
                return $this->syntaxHighlightGeSHi($matches[3], $matches[2]);
            },
        ];

        return preg_replace_callback_array($patternsAndCallbacks, $text);
        */

        $patterns = [
            "/\[(FIGURE)[\s+](.+)\]/",
            "/(```)([\w]*)\n([^`]*)```[\n]{1}/s",
            "/\[(ASCIINEMA)[\s+](.+)\]/",
        ];

        return preg_replace_callback(
            $patterns,
            function ($matches) {
                switch ($matches[1]) {

                    case "FIGURE":
                        return self::ShortCodeFigure($matches[2]);
                    break;

                    case "ASCIINEMA":
                        return self::ShortCodeAsciinema($matches[2]);
                    break;

                    case "```":
                        return $this->syntaxHighlightGeSHi($matches[3], $matches[2]);
                    break;

                    default:
                        return "{$matches[1]} is unknown shortcode.";
                }
            },
            $text
        );
    }



    /**
     * Init shortcode handling by preparing the option list to an array, for those using arguments.
     *
     * @param string $options for the shortcode.
     *
     * @return array with all the options.
     */
    public static function shortCodeInit($options)
    {
        preg_match_all('/[a-zA-Z0-9]+="[^"]+"|\S+/', $options, $matches);

        $res = array();
        foreach ($matches[0] as $match) {
            $pos = strpos($match, '=');
            if ($pos === false) {
                $res[$match] = true;
            } else {
                $key = substr($match, 0, $pos);
                $val = trim(substr($match, $pos+1), '"');
                $res[$key] = $val;
            }
        }

        return $res;
    }



    /**
     * Shortcode for <figure>.
     *
     * Usage example: [FIGURE src="img/home/me.jpg" caption="Me" alt="Bild på mig" nolink="nolink"]
     *
     * @param string $options for the shortcode.
     *
     * @return array with all the options.
     */
    public static function shortCodeFigure($options)
    {
        // Merge incoming options with default and expose as variables
        $options= array_merge(
            [
                'id' => null,
                'class' => null,
                'src' => null,
                'title' => null,
                'alt' => null,
                'caption' => null,
                'href' => null,
                'nolink' => false,
            ],
            self::ShortCodeInit($options)
        );
        extract($options, EXTR_SKIP);

        $id = $id ? " id='$id'" : null;
        $class = $class ? " class='figure $class'" : " class='figure'";
        $title = $title ? " title='$title'" : null;

        if (!$alt && $caption) {
            $alt = $caption;
        }

        if (!$href) {
            $pos = strpos($src, '?');
            $href = $pos ? substr($src, 0, $pos) : $src;
        }

        $start = null;
        $end = null;
        if (!$nolink) {
            $start = "<a href='{$href}'>";
            $end = "</a>";
        }

        $html = <<<EOD
<figure{$id}{$class}>
{$start}<img src='{$src}' alt='{$alt}'{$title}/>{$end}
<figcaption markdown=1>{$caption}</figcaption>
</figure>
EOD;

        return $html;
    }



    /**
     * Shortcode for [asciinema].
     *
     * @param string $code the code to process.
     * @param string $options for the shortcode.
     * @return array with all the options.
     */
    public static function ShortCodeAsciinema($options) {
        // Merge incoming options with default and expose as variables
        $options= array_merge(
            [
                'id' => null,
                'class' => null,
                'src' => null,
                'title' => null,
                'caption' => null,
            ],
            self::ShortCodeInit($options)
        );
        extract($options, EXTR_SKIP);

        $id = $id ? " id=\"$id\"" : null;
        $class = $class ? " class=\"figure asciinema $class\"" : " class=\"figure asciinema\"";
        $title = $title ? " title=\"$title\"" : null;

        $html = <<<EOD
<figure{$id}{$class}$title>
<script type="text/javascript" src="https://asciinema.org/a/{$src}.js" id="asciicast-{$src}" async></script>
<figcaption markdown=1>{$caption}</figcaption>
</figure>
EOD;

        return $html;
    }



/**
 * Shortcode for including a SVG-image inside a <figure>.
 *
 * @param string $code the code to process.
 * @param string $options for the shortcode.
 * @return array with all the options.
 */
/*public static function ShortCodeSVGFigure($options) {
  extract(array_merge(array(
    'id' => null,
    'class' => null,
    'src' => null,
    'path' => null,
    'title' => null,
    'alt' => null,
    'caption' => null,
    'href' => null,
    'nolink' => false,
  ), CTextFilter::ShortCodeInit($options)), EXTR_SKIP);

  $id = $id ? " id='$id'" : null;
  //$class = $class ? " class='$class'" : null;
  $class = $class ? " class='figure $class'" : " class='figure'";
  $title = $title ? " title='$title'" : null;
  
  if(!$alt && $caption) {
    $alt = $caption;
  }

  if(!$href) {
    $pos = strpos($src, '?');
    $href = $pos ? substr($src, 0, $pos) : $src;
  }

  if(!$nolink) {
    $a_start = "<a href='{$href}'>";
    $a_end = "</a>";
  }

  // Import the file containing the svg-image
  $svg = null;
  
  if($path[0] != '/') {
    $path = self::$dir . '/' . $path;
  }

  if(is_file($path)) {
    $svg = file_get_contents($path);
  }
  else {
    $svg = "No such file: $path";
  }
  $html = <<<EOD
<figure{$id}{$class}>
{$svg}
<figcaption markdown=1>{$caption}</figcaption>
</figure>
EOD;

  return $html;
}

*/



/**
 * Shorttags to to quicker format text as HTML.
 *
 * @param string text text to be converted.
 * @return string the formatted text.
 */
/*public static function ShortTags($text) {
  $callback = function($matches) {
    switch($matches[1]) {
      case 'IMG':
        $caption = t('Image: ');
        $pos = strpos($matches[2], '?');
        $href = $pos ? substr($matches[2], 0, $pos) : $matches[2];
        $src = htmlspecialchars($matches[2]);
        return <<<EOD
<figure>
<a href='{$href}'><img src='{$src}' alt='{$matches[3]}' /></a>
<figcaption markdown=1>{$caption}{$matches[3]}</figcaption>
</figure>
EOD;

      case 'IMG2':
        $caption = null; //t('Image: ');
        $pos = strpos($matches[2], '?');
        $href = $pos ? substr($matches[2], 0, $pos) : $matches[2];
        $src = htmlspecialchars($matches[2]);
        return <<<EOD
<figure class="{$matches[4]}">
<a href='{$href}'><img src='{$src}' alt='{$matches[3]}' /></a>
<figcaption markdown=1>{$caption}{$matches[3]}</figcaption>
</figure>
EOD;
      case 'BOOK':
        $isbn = $matches[2];
        $stores = array(
          'BTH' => "http://bth.summon.serialssolutions.com/?#!/search?ho=t&amp;q={$isbn}",
          'Libris' => "http://libris.kb.se/hitlist?q={$isbn}",
          'Google Books' => "http://books.google.com/books?q={$isbn}",
          'Bokus' => "http://www.bokus.com/bok/{$isbn}",
          'Adlibris' => "http://www.adlibris.com/se/product.aspx?isbn={$isbn}",
          'Amazon' => "http://www.amazon.com/s/ref=nb_ss?url=field-keywords={$isbn}",
          'Barnes&Noble' => "http://search.barnesandnoble.com/booksearch/ISBNInquiry.asp?r=1&IF=N&EAN={$isbn}",
        );
        $html = null;
        foreach($stores as $key => $val) {
          $html .= "<a href='$val'>$key</a> &bull; ";
        }
        return substr($html, 0, -8);
      break;

      case 'YOUTUBE':
        $caption = t('Figure: ');
        $height = ceil($matches[3] / (16/9));
        return <<<EOD
<figure>
<iframe width='{$matches[3]}' height='{$height}' src="http://www.youtube.com/embed/{$matches[2]}" frameborder="0"
allowfullscreen></iframe>
<figcaption>{$caption}{$matches[4]}</figcaption>
</figure>
EOD;
      break;
      
      case 'syntax=': return CTextFilter::SyntaxHighlightGeSHi($matches[3], $matches[2]); break;
      case '```': return CTextFilter::SyntaxHighlightGeSHi($matches[3], $matches[2]); break;
      //case 'syntax=': return "<pre>" . highlight_string($matches[3], true) . "</pre>"; break;
      //case 'INCL':  include($matches[2]); break;
      case 'INFO':  return "<div class='info' markdown=1>"; break;
      case '/INFO': return "</div>"; break;
      case 'BASEURL': return CLydia::Instance()->request->base_url; break;
      case 'FIGURE': return CTextFilter::ShortCodeFigure($matches[2]); break;
      case 'FIGURE-SVG': return CTextFilter::ShortCodeSVGFigure($matches[2]); break;
      case 'ASCIINEMA': return CTextFilter::ShortCodeAsciinema($matches[2]); break;
      default: return "{$matches[1]} IS UNKNOWN SHORTTAG."; break;
    }
  };
  $patterns = array(
    '#\[(BASEURL)\]#',
    //'/\[(AUTHOR) name=(.+) email=(.+) url=(.+)\]/',
    '/\[(FIGURE)[\s+](.+)\]/',
    '/\[(FIGURE-SVG)[\s+](.+)\]/',
    '/\[(ASCIINEMA)[\s+](.+)\]/',
    '/\[(IMG) src=(.+) alt=(.+)\]/',
    '/\[(IMG2) src=(.+) alt="(.+)" class="(.+)"\]/',
    '/\[(BOOK) isbn=(.+)\]/',
    '/\[(YOUTUBE) src=(.+) width=(.+) caption=(.+)\]/',
    '/~~~(syntax=)(php|html|html5|css|sql|javascript|bash)\n([^~]+)\n~~~/s',
    '/(```)(php|html|html5|css|sql|javascript|bash|text|txt|python)\n([^`]+)\n```/s',
    //'/\[(INCL)/s*([^\]+)/',
    '#\[(INFO)\]#', '#\[(/INFO)\]#',
  );

  $ret = preg_replace_callback($patterns, $callback, $text);
  return $ret;
}
*/



    /**
     * Support SmartyPants for better typography.
     *
     * @param string text text to be converted.
     * @return string the formatted text.
     */
/*     public static function SmartyPants($text) {   
      require_once(__DIR__.'/php_smartypants_1.5.1e/smartypants.php');
      return SmartyPants($text);
    }
*/


    /**
     * Support enhanced SmartyPants/Typographer for better typography.
     *
     * @param string text text to be converted.
     * @return string the formatted text.
     */
/*     public static function Typographer($text) {   
      require_once(__DIR__.'/php_smartypants_typographer_1.0/smartypants.php');
      $ret = SmartyPants($text);
      return $ret;
    }
*/
}
