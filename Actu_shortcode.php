<?php

namespace EPFL\WS\Actu;

/*
 * ToDo:
 *    - Add Cache (wp_cache)
 *    - Add CSS classes (similar to https://help-actu.epfl.ch/outils-webmasters/exporter-tri-articles ?)
 *    - INC0203354 - Author + Source (link)
 *    - Format date
 *    - Add author
 *    - Add switch in case the actu contains a video
 *    - Add the category/channel/publics/themes information
 */

use WP_Error;

// include function from /inc/epfl-ws.inc
require_once(dirname(__FILE__) . "/inc/epfl-ws.inc");

require_once(__DIR__ . "/inc/base-classes.inc");
use \EPFL\WS\Base\ListTemplatedShortcodeView;

class ActuShortCode {

  /*
   * Init
   */
  function __construct() {
    add_shortcode('actu', array($this, 'wp_shortcode'));
    add_action("admin_print_footer_scripts", array($this, 'actu_shortcode_button_script'));
    $this->ws = new \EPFL\WS\epflws();
  }

  /*
   * Main logic
   */
  function wp_shortcode($atts, $content=null, $tag='') {
    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // override default attributes with user attributes
    $actu_atts = shortcode_atts([ 'tmpl'      => 'full', // UNUSED - For backward compatibility
                                  'channel'   => '',   // http://actu.epfl.ch/api/v1/channels/ [10 = STI, search https://actu.epfl.ch/api/v1/channels/?name=sti]
                                  'category'  => '',     // https://actu.epfl.ch/api/v1/categories/ [1: EPFL, 2: EDUCATION, 3: RESEARCH, 4: INNOVATION, 5: CAMPUS LIFE]
                                  'lang'      => 'en',   // en, fr
                                  'search'    => '',     // ??? search somewhere ???
                                  'title'     => '',     // search in title (insensitive)
                                  'subtitle'  => '',     // search in subtitle (insensitive)
                                  'text'      => '',     // search in text (insensitive)
                                  'publics'   => '',     // http://actu.epfl.ch/api/v1/publics/ [1: Prospective Students, 2: Students, 3: Collaborators, 4: Industries/partners, 5: Public, 6: Média]
                                  'themes'    => '',     // http://actu.epfl.ch/api/v1/themes/ [1: Basic Sciences, 2: Health, 3: Computer Science, 4: Engineering, 5: Environment, 6: Buildings, 7: Culture, 8: Economy, 9: Energy]
                                  'limit'     => '',     // limit of news returned
                                  'faculties' => '',     // http://actu.epfl.ch/api/v1/faculties/ [1: CDH, 2: CDM, 3: ENAC, 4: IC, 5: SB, 6: STI, 7: SV]
                                  'offset'    => '',     // specify a offset for returned news
                                ], $atts, $tag);
    $this->actu_atts = $actu_atts;

    $tmpl       = esc_attr($actu_atts['tmpl']);
    $channel    = esc_attr($actu_atts['channel']);
    $category   = esc_attr($actu_atts['category']);
    $lang       = esc_attr($actu_atts['lang']);
    $search     = esc_attr($actu_atts['search']);
    $title      = esc_attr($actu_atts['title']);
    $subtitle   = esc_attr($actu_atts['subtitle']);
    $text       = esc_attr($actu_atts['text']);
    $publics    = esc_attr($actu_atts['publics']);
    $themes     = esc_attr($actu_atts['themes']);
    $limit      = esc_attr($actu_atts['limit']);
    $factulties = esc_attr($actu_atts['factulties']);
    $offset     = esc_attr($actu_atts['offset']);

    // https://actu.epfl.ch/search/sti/?keywords=&date_filter=all&themes=4&faculties=6&categories=3&search=Search
    // 'https://actu.epfl.ch/api/v1/channels/10/news/?format=json&lang='.$lang.'&category=3&faculty=3&themes=4';

    // make the correct URL call
    // OLD API $url = 'https://actu.epfl.ch/api/jahia/channels/'.$channel.'/news/'.$lang.'/?format=json';
    // channel and lang are the 2 needed attributes, fallback to STI/EN
    if ($channel) {
      $url = 'https://actu.epfl.ch/api/v1/channels/'.$channel.'/news/?format=json&lang='.$lang;
    } else {
      $url = 'https://actu.epfl.ch/api/v1/news/?format=json&lang='.$lang;
    }
    if ($category)
      $url .= '&category=' . $category;
    if ($search)
      $url .= '&search=' . $search;
    if ($subtitle)
      $url .= '&subtitle=' . $subtitle;
    if ($publics)
      $url .= '&publics=' . $publics;
    if ($title)
      $url .= '&title=' . $title;
    if ($text)
      $url .= '&text=' . $text;
    if ($themes)
      $url .= '&themes=' . $themes;
    if ($limit)
      $url .= '&limit=' . $limit;
    if ($faculties)
      $url .= '&faculties=' . $faculties;
    if ($offset)
      $url .= '&offset=' . $offset;

    //$this->ws->debug($url);
    // fetch actus items
    if ( $actuurl = $this->ws->validate_url( $url, "actu.epfl.ch" ) ) {
      $actus = $this->ws->get_items( $actuurl );
    } else {
      $error = new WP_Error( 'epfl-ws-actu-shortcode', 'URL not validated', 'URL: ' . $url . ' returned an error' );
      $this->ws->log( $error );
    }

    $display_html = $this->display_templated($actus->results);

    // This print out the queryed url, useful for now
    echo "<!-- epfl-actu url: " . $url . " -->";
    return $display_html;
  }

  /*
   * Add the Actu button to TinyMCE
   */
  function actu_shortcode_button_script() {
    if(wp_script_is("quicktags")) {
      ?>
        <script type="text/javascript">
          QTags.addButton(
            "actu_shortcode",
            "Actu",
            callback
          );
          var actuDoc = '<!--\n' +
                        '= Actu shortcode Information =\n' +
                        'Note that more detailed information can be found on the plugin page in the administration section of your site or on GitHub.\n\n' +
                        'Actu Shortcode allows you to integrate EPFL News (actus) in any Wordpress pages or posts. ' +
                        'To do so, just use the [actu] shortcode where ever you want to display the news. ' +
                        'In addition, you can be very picky on which news you want, by passing some arguments to the shortcode.\n' +
                        'Here are some example:\n' +
                        '\t- [actu]\n' +
                        '\t- [actu tmpl=full channel=10 lang=en limit=3]\n' +
                        '\t- [actu tmpl=short channel=10 lang=en limit=20 category=1 title=EPFL subtitle=EPFL text=EPFL faculties=6 themes=1 publics=6]\n' +
                        '\n' +
                        'Generally, you want to pick up your channel. You can search your channel\'s ID here: https://actu.epfl.ch/api/v1/channels/?name=sti\n' +
                        '\n' +
                        '\t* The "category" is in [1: EPFL, 2: EDUCATION, 3: RESEARCH, 4: INNOVATION, 5: CAMPUS LIFE]\n' +
                        '\t* The "publics" is in [1: Prospective Students, 2: Students, 3: Collaborators, 4: Industries/partners, 5: Public, 6: Média]\n' +
                        '\t* The "themes" is in [1: Basic Sciences, 2: Health, 3: Computer Science, 4: Engineering, 5: Environment, 6: Buildings, 7: Culture, 8: Economy, 9: Energy]\n' +
                        '\t* The "faculties" is in [1: CDH, 2: CDM, 3: ENAC, 4: IC, 5: SB, 6: STI, 7: SV]\n' +
                        'Note that you don\'t have to specify any of these if you don\ want to filter.\n' +
                        '\n' +
                        '"search", "title", "subtitle", "text" are search arguments you can use to get news across the school on, in example, keywords.\n' +
                        '\n' +
                        '\n' +
                        'Finally, the source and documentation of this plugin are part of EPFL-WS, you can find help and participate here: <https://github.com/epfl-si/wp-plugin-epfl-ws>\n' +
                        '-->';

          function callback()
          {
            QTags.insertContent(actuDoc);
          }
        </script>
      <?php
    }
  }

  function display_templated($actus)
  {
    $view = new ActuShortcodeView($this->actu_atts);
    $view->ws = $this->ws;
    return $view->as_html($actus);
  }
}

class ActuShortcodeView extends ListTemplatedShortcodeView
{
    function get_slug () {
        return "actu";
    }
    function item_as_html ($item) {
        return "
       <div class=\"actu-news-box\">
        <div class=\"actu-news-title\"><a href=\"https://actu.epfl.ch/news/".get_anchor($item->title)."\">".strtoupper($item->title)."</a></div>
        <div class=\"actu-news-body\">
         <img src=\"".$item->visual_url."\" width=\"170\" height=\"100\">
         <span>".$item->subtitle."</span>
        </div>
       </div>";
    }
}

/**
 * Second-guess the URL of an Actu item in case it's not provided by the API.
 * e.g. https://actu.epfl.ch/news/a-12-million-franc-donation-to-create-a-center-for/
 */
function get_anchor ($title)
{
    $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                                'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                                'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                                'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                                'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
    $title = strtr( $title, $unwanted_array );
    $title = str_replace(" ", "-", $title);
    $title = str_replace("'", "-", $title);
    $title = strtolower($title);
    $title = substr($title, 0, 50);
    return $title;
}

new ActuShortCode();
?>
