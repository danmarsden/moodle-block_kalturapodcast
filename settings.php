<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
//settings to use to link to course meta stuff.
    $settings->add(new admin_setting_configtext('block_kalturapodcast_meta_category', get_string('podcastmetacategory', 'block_kalturapodcast'),
                       get_string('podcastmetacategory_desc', 'block_kalturapodcast'), 'kaltura_podcast_cat', PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configtext('block_kalturapodcast_player',
        get_string('player', 'block_kalturapodcast'), get_string('player_desc', 'block_kalturapodcast'), '1466432', PARAM_TEXT, 8));

    //setting for player to use when click link in block
    $settings->add(new admin_setting_configtext('block_kalturapodcast_content_flavor',
        get_string('contentflavor', 'block_kalturapodcast'), get_string('contentflavor_desc', 'block_kalturapodcast'), '6', PARAM_INT, 8));

    //settings for Itunes feed.
    $settings->add(new admin_setting_configtext('block_kalturapodcast_landing_page', get_string('landingpage', 'block_kalturapodcast'),
                       get_string('landingpage_desc', 'block_kalturapodcast'), '', PARAM_URL));

    $settings->add(new admin_setting_configtext('block_kalturapodcast_feed_owner_name', get_string('feedownername', 'block_kalturapodcast'),
                       get_string('feedownername_desc', 'block_kalturapodcast'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_kalturapodcast_feed_owner_email', get_string('feedowneremail', 'block_kalturapodcast'),
                       get_string('feedowneremail_desc', 'block_kalturapodcast'), '', PARAM_EMAIL));

    $settings->add(new admin_setting_configtext('block_kalturapodcast_feed_imageurl', get_string('feedimageurl', 'block_kalturapodcast'),
                       get_string('feedimageurl_desc', 'block_kalturapodcast'), '', PARAM_URL));

    $settings->add(new admin_setting_configtext('block_kalturapodcast_maxfeed', get_string('maxfeed','block_kalturapodcast'),
                       get_string('maxfeed_desc','block_kalturapodcast'),'50',PARAM_INT));
}

