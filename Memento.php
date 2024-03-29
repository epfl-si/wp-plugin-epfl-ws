<?php

/**
 * "Memento" custom post type and taxonomy.
 *
 * For each event in memento.epfl.ch in channels that the WordPress
 * administrators are interested in, there is a local copy as a post
 * inside the WordPress database. This allows e.g. putting memento
 * events into the newsletter or using the full-text search on them.
 */

namespace EPFL\WS\Memento;


if (! defined('ABSPATH')) {
    die('Access denied.');
}

require_once(dirname(__FILE__) . "/inc/base-classes.inc");

require_once(dirname(__FILE__) . "/inc/i18n.inc");
use function \EPFL\WS\___;
use function \EPFL\WS\__x;

/**
 * Object model for Memento streams
 *
 * One stream corresponds to one so-called "term" in the
 * 'epfl-memento-channel' WordPress taxonomy. Each stream has an API URL
 * from which events are continuously fetched.
 */
class MementoStream extends \EPFL\WS\Base\APIChannelTaxonomy
{
    static function get_taxonomy_slug ()
    {
        return 'epfl-memento-channel';
    }

    static function get_term_meta_slug ()
    {
        return "epfl_memento_channel_api_url";
    }

    static function get_post_class ()
    {
        return Memento::class;
    }
}

/**
 * Object model for Memento posts
 *
 * There is one instance of the Memento class for every unique event
 * (identified by the "event_id" and "lang" / "language" API fields,
 * and materialized as a WordPress "post" object of post_type ===
 * 'epfl-memento').
 */
class Memento extends \EPFL\WS\Base\APIChannelPost
{
    static function get_post_type ()
    {
        return 'epfl-memento';
    }

    static function extract_image_url ($api_result)
    {
        return $api_result["event_visual_absolute_url"];
    }

    protected function extract_content ($api_result)
    {
        return $api_result["description"];
    }

    /**
     * Overridden to retain video metadata and subtitle, if any
     */
    protected function _update_post_meta ($api_result)
    {
        parent::_update_post_meta($api_result);
        foreach (["event_start_date", "event_end_date",
                  "event_start_time", "event_end_time",
                  "event_theme", "event_speaker",
                  "event_place_and_room", "event_url_place_and_room",
                  "event_canceled_reason", "translation_id"]
                 as $keep_this_as_meta)
        {
            if ($api_result[$keep_this_as_meta]) {
                $this->_post_meta[$keep_this_as_meta] = $api_result[$keep_this_as_meta];
            }
        }
        foreach (["event_is_internal", "event_canceled"]
        as $keep_this_as_bool_meta) {
            $this->_post_meta[$keep_this_as_bool_meta] = (
                strtolower($api_result[$keep_this_as_bool_meta]) === "true");
        }
    }

    public function get_venue ()
    {
        return get_post_meta($this->ID, "event_place_and_room", true);
    }

    public function get_ical_link ()
    {
        return sprintf("https://memento.epfl.ch/event/export/%d", $this->get_translation_id());
    }

    public function get_start_datetime ()
    {
        return $this->_parse_datetime(
            get_post_meta($this->ID, "event_start_date", true),
            get_post_meta($this->ID, "event_start_time", true));
    }

    public function get_end_datetime ()
    {
        return $this->_parse_datetime(
            get_post_meta($this->ID, "event_end_date", true),
            get_post_meta($this->ID, "event_end_time", true));
    }

    public function _parse_datetime ($date, $time)
    {
        if ($date && $time) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', "$date $time");
        } elseif ($date) {
            return \DateTime::createFromFormat('Y-m-d', $date);
        } else {
            return null;
        }
    }

}

/**
 * Configuration UI and WP callbacks for the memento stream taxonomy
 *
 * This is a "pure static" class; no instances are ever constructed.
 */
class MementoStreamController extends \EPFL\WS\Base\StreamTaxonomyController
{
    static function get_taxonomy_class () {
        return MementoStream::class;
    }

    static function get_human_labels ()
    {
        return array(
            // These are for regster_taxonomy
            'name'              => __x( 'Event Channels', 'taxonomy general name'),
            'singular_name'     => __x( 'Event Channel', 'taxonomy singular name'),
            'search_items'      => ___( 'Search Event Channels'),
            'all_items'         => ___( 'All Event Channels'),
            'edit_item'         => ___( 'Edit Event Channel'),
            'update_item'       => ___( 'Update Event Channel'),
            'add_new_item'      => ___( 'Add Event Channel'),
            'new_item_name'     => ___( 'New Channel Name'),
            'menu_name'         => ___( 'Event Channels'),

            // These are internal to StreamTaxonomyController
            'url_legend'        => ___('Memento Channel API URL'),
            'url_legend_long'   => ___("Source URL of the JSON data. Use <a href=\"https://wiki.epfl.ch/api-rest-actu-memento/memento\" target=\"_blank\">memento-doc</a> for details.")
        );
    }

    static function get_placeholder_api_url ()
    {
        return "https://memento.epfl.ch/api/jahia/mementos/sti/events/en/?format=json";
    }
}

/**
 * WP configuration and callbacks for the EPFL Memento post type
 *
 * This is a "pure static" class; no instances are ever constructed.
 */
class MementoController extends \EPFL\WS\Base\APIChannelPostController
{
    static function get_taxonomy_class () {
        return MementoStream::class;
    }

    static function filter_register_post_type (&$settings) {
        $settings["menu_icon"] = 'dashicons-calendar';
        $settings["menu_position"] = 45;
    }

    static function get_human_labels ()
    {
        return array(
            // For register_post_type:
            'name'               => __x( 'EPFL Events', 'post type general name' ),
            'singular_name'      => __x( 'EPFL Events', 'post type singular name' ),
            'menu_name'          => __x( 'EPFL Events', 'admin menu' ),
            'name_admin_bar'     => __x( 'EPFL Events', 'add new on admin bar' ),
            'view_item'          => ___( 'View EPFL Event' ),
            'all_items'          => ___( 'All EPFL Events for this site' ),
            'search_items'       => ___( 'Search Events' ),
            'not_found'          => ___( 'No events found.' ),
            'not_found_in_trash' => ___( 'No events found in Trash.' ),

            // Others:
            'description'        => ___( 'EPFL events from events.epfl.ch' )
        );
    }
}

MementoStreamController::hook();
MementoController::hook();
