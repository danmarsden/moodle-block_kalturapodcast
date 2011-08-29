<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Kaltura Podcast block
 *
 * @package    block
 * @subpackage kalturapodcast
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_kalturapodcast extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_kalturapodcast');
    }
    function applicable_formats() {
        return array('all' => true);
    }
    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('pluginname', 'block_kalturapodcast'));
    }
    public function instance_allow_config() {
        return true;
    }
    function get_content() {
        global $CFG, $COURSE, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        //check existence of local plugin
        if (!file_exists($CFG->dirroot.'/local/kaltura/lib.php')) {
            $this->content->footer = $OUTPUT->notification(get_string('missingkalturaplugin', 'block_kalturapodcast'));
            return $this->content;
        }
        //check existence of local course data plugin.
        if (!file_exists($CFG->dirroot.'/local/une_course_data/lib.php')) {
            $this->content->footer = $OUTPUT->notification(get_string('missingcoursedataplugin', 'block_kalturapodcast'));
            return $this->content;
        }
        $this->block_kalturapodcast_generate($COURSE);
        return $this->content;
    }
    function block_kalturapodcast_generate($course, $updatecache=false, $updatesort=false, $thisconfig='') {
        global $CFG, $OUTPUT;
        $this->page->requires->js('/blocks/kalturapodcast/player.js');
        $this->page->requires->yui2_lib(array('dom','dragdrop', 'animation', 'container','element'));
        $this->page->requires->css('/local/kaltura/styles.css');
        if (is_int($course)) {
            $courseid = $course;
        } else {
            $courseid = $course->id;
        }
        if (empty($thisconfig)) {
            $thisconfig = $this->config;
        }
        $cachedir = $CFG->dataroot.'/cache/kalturapodcast/';
        require_once($CFG->dirroot.'/local/kaltura/lib.php');

        //get local meta tag:
        $playlistcategories = course_data_find_by_shortname($CFG->block_kalturapodcast_meta_category, $courseid);
        if (empty($playlistcategories)) {
            $this->content->footer = $OUTPUT->notification(get_string('missingcoursedatameta', 'block_kalturapodcast', $CFG->block_kalturapodcast_meta_category));
            return $this->content;
        }
        $playlistcategory = '';
        foreach ($playlistcategories as $cat) {
            if (!empty($playlistcategory)) {
                $playlistcategory .= ',';
            }
            $playlistcategory .= $cat->data;
        }
        try {
            $client = kalturaClientSession(true);
            $config = $client->getConfig();
        } catch (Exception $e) {
            $this->content->text .= get_string('contenterror','block_kalturapodcast', $e->getMessage());
            return $this->content;
        }
        //check if not update and file exists.
        if (!$updatecache && !empty($thisconfig->playlistid) && file_exists($cachedir.$thisconfig->playlistid.'.txt')){
            $cachefilename = $cachedir.$thisconfig->playlistid.'.txt';
            $handle = fopen($cachefilename, 'rb');
            $this->content->text = fread($handle, filesize($cachefilename));
            fclose($handle);
        } else {
            $updatecache = true;
            if (is_int($course)) {
                $course = $DB->get_record('course', array('id' => $course));
            }
            //check if this block already has a playlistid set or if the category has changed.
            if (empty($thisconfig->playlistid) || (isset($thisconfig->metatag) && $thisconfig->metatag != $playlistcategory)) {
                $thisconfig->metatag = $playlistcategory; //save category into config so we can check if it has changed.
                //create a new playlist
                try {
                    $playlist = new KalturaPlaylist;
                    $playlist->name = format_string($course->fullname);
                    $playlist->description = get_string('autocreatedplaylist','block_kalturapodcast');
                    $playlist->partnerId = $config->partnerId;
                    $playlist->type = 5; //5==playlist type
                    $playlist->playlistType = 10; //10== dynamic
                    $playlist->totalResults = $CFG->block_kalturapodcast_maxfeed;

                    //add filter for this playlist.
                    $filter = new KalturaMediaEntryFilterForPlaylist;
                    $filter->patnerIdEqual = $config->partnerId;
                    $filter->categoriesMatchOr = $playlistcategory;
                    $filter->mediaTypeIn = '1,2,5,6,201';
                    $filter->moderationStatusIn = '2,5,6,1';
                    $filter->limit = $CFG->block_kalturapodcast_maxfeed;
                    $filter->statusIn = '2,1';
                    $filter->orderBy = 'recent';
                    $playlist->filters = array($filter);

                    $result = $client->playlist->add($playlist);
                    if (!empty($result->id)) {
                        $thisconfig->playlistid = $result->id;
                        //now create a new itunes feed for the above playlist.
                        $feed = new KalturaITunesSyndicationFeed;
                        $feed->playlistId = $thisconfig->playlistid;
                        $feed->name = format_string($course->fullname);;
                        $feed->type = 3; //3== itundes feed type.
                        $feed->feedImageUrl = $CFG->block_kalturapodcast_feed_imageurl;
                        $feed->ownerName = $CFG->block_kalturapodcast_feed_owner_name;
                        $feed->ownerEmail = $CFG->block_kalturapodcast_feed_owner_email;
                        $feed->categories = KalturaITunesSyndicationFeedCategories::EDUCATION;
                        $feed->feedLandingPage = $CFG->block_kalturapodcast_landing_page;
                        $feed->landingPage = $CFG->block_kalturapodcast_landing_page;
                        $feed->flavorParamId = $CFG->block_kalturapodcast_content_flavor;

                        $result = $client->syndicationFeed->add($feed);
                        if (!empty($result->id)) {
                            $thisconfig->feedid = $result->id;
                        }
                    } else {
                        $this->content->footer = $OUTPUT->notification(get_string('errorcreatingplaylist', 'block_kalturapodcast'));
                        return $this->content;
                    }
                    $thisconfig->courseid = $courseid;
                    $this->instance_config_save($thisconfig);
                } catch (Exception $e) {
                    $this->content->text .= get_string('contenterror','block_kalturapodcast', $e->getMessage());
                    return $this->content;
                }
            } else if (!empty($thisconfig->playlistid) && $updatesort) {
                $playlist = new KalturaPlaylist;
                $playlist->name = format_string($course->fullname);
                $playlist->description = get_string('autocreatedplaylist','block_kalturapodcast');
                $playlist->type = 5; //5==playlist type
                $playlist->playlistType = 10; //10== dynamic
                $playlist->totalResults = $CFG->block_kalturapodcast_maxfeed;

                //add filter for this playlist.
                $filter = new KalturaMediaEntryFilterForPlaylist;
                $filter->patnerIdEqual = $config->partnerId;
                $filter->categoriesMatchOr = $playlistcategory;
                $filter->mediaTypeIn = '1,2,5,6,201';
                $filter->moderationStatusIn = '2,5,6,1';
                $filter->limit = $CFG->block_kalturapodcast_maxfeed;
                $filter->statusIn = '2,1';
                $filter->orderBy = $thisconfig->feedsort;
                $playlist->filters = array($filter);

                $client->playlist->update($thisconfig->playlistid, $playlist);
                $updatecache = true;
            }

            try {
                $results = $client->playlist->execute($thisconfig->playlistid);
            } catch (Exception $e) {
                $this->content->text .= get_string('contenterror','block_kalturapodcast', $e->getMessage());
                return $this->content;
            }

            if (!empty($results)) {
                $numfeed = (!empty($thisconfig->feednum) ? $thisconfig->feednum : 10);

                //check if we need to sort this array. - kaltura doesn't perform natural sorting on numbers - eg 1, 10, 2, 20
                if (!empty($thisconfig->feedsort) && $thisconfig->feedsort == 'name') {
                    $res = array();
                    foreach ($results as $result) {
                        $res[$result->name] = $result;
                    }
                    uksort($res, "strnatcasecmp");
                    $results = $res;
                }
                $count = 0;
                $this->content->text = '';
                $row = 0;
                foreach ($results as $result) {
                    if ($count < $numfeed) {
                        $mediaicon = $OUTPUT->pix_icon('page_white',get_string('unknown', 'block_kalturapodcast'),'block_kalturapodcast');
                        if ($result->mediaType == KalturaMediaType::AUDIO) {
                            $mediaicon = $OUTPUT->pix_icon('sound_none',get_string('audio', 'block_kalturapodcast'),'block_kalturapodcast');
                        } else if ($result->mediaType == KalturaMediaType::VIDEO) {
                            $mediaicon = $OUTPUT->pix_icon('film',get_string('video', 'block_kalturapodcast'),'block_kalturapodcast');
                        } else if ($result->mediaType == KalturaMediaType::IMAGE) {
                            $mediaicon = $OUTPUT->pix_icon('image',get_string('image', 'block_kalturapodcast'),'block_kalturapodcast');
                        } else if ($result->mediaType == KalturaMediaType::LIVE_STREAM_FLASH) {
                            $mediaicon = $OUTPUT->pix_icon('transmit',get_string('transmit', 'block_kalturapodcast'),'block_kalturapodcast');
                        } else if ($result->mediaType == KalturaMediaType::LIVE_STREAM_QUICKTIME) {
                            $mediaicon = $OUTPUT->pix_icon('transmit',get_string('transmit', 'block_kalturapodcast'),'block_kalturapodcast');
                        } else if ($result->mediaType == KalturaMediaType::LIVE_STREAM_REAL_MEDIA) {
                            $mediaicon = $OUTPUT->pix_icon('transmit',get_string('transmit', 'block_kalturapodcast'),'block_kalturapodcast');
                        } else if ($result->mediaType == KalturaMediaType::LIVE_STREAM_WINDOWS_MEDIA) {
                            $mediaicon = $OUTPUT->pix_icon('transmit',get_string('transmit', 'block_kalturapodcast'),'block_kalturapodcast');
                        }
                        if ($result->mediaType == KalturaMediaType::IMAGE) {
                            $this->content->text .= '<div class="podcastitem image r'.$row.'">'.
                                                    '<a href="'.$result->downloadUrl.'">'.$mediaicon.format_string($result->name).'</a></div>';
                        } else {

                            $feedtitle = format_string($result->name).'<span class="kalturaduration"> ('.format_time($result->duration).')</span>';
                            $this->content->text .= '<div class="podcastitem r'.$row.
                                                    '"><a href="#" onclick="panel_playersetBody(\''.$result->id.'\', \''.$CFG->block_kalturapodcast_player.'\');panel_player.show();return false">'.$mediaicon.$feedtitle.'</a></div>';
                        }
                        $count++;
                        $row = empty($row) ? 1 : 0;
                    }
                }
            } else {
                $this->content->text .= get_string('nopodcasts','block_kalturapodcast');
            }
        }
        //now save file if set
        if ($updatecache && !empty($this->content->text)) {
            check_dir_exists($cachedir);
            $handle = fopen($cachedir.$thisconfig->playlistid.'.txt', 'w');
            fwrite($handle, $this->content->text);
            fclose($handle);
        }
        //now display links to feeds.
        $feedurl = 'www.kaltura.com/api_v3/getFeed.php?partnerId='.$config->partnerId.'&feedId='.$thisconfig->feedid;
        $this->content->footer = '<ul><li><a href="itpc://'.$feedurl.'" target="_blank">'.
                                 $OUTPUT->pix_icon('podcast',get_string('itunesfeed', 'block_kalturapodcast'), 'block_kalturapodcast').get_string('itunesfeed', 'block_kalturapodcast').'</a></li>';
        $this->content->footer .= '<li><a href="http://'.$feedurl.'" target="_blank">'.
                                  $OUTPUT->pix_icon('rss',get_string('mrssfeed', 'block_kalturapodcast'), 'block_kalturapodcast').get_string('mrssfeed', 'block_kalturapodcast').'</a></li></ul>';
        $this->content->text = get_string('blockheader', 'block_kalturapodcast').$this->content->text;
        $this->content->footer .= get_string('blockfooter', 'block_kalturapodcast');
    }
    function cron() {
        global $DB;
        // We are going to measure execution times
        $starttime =  microtime();

        $rs = $DB->get_recordset('block_instances', array('blockname'=>'kalturapodcast'));
        mtrace('');
        $counter = 0;
        foreach ($rs as $rec) {
            $config = unserialize(base64_decode($rec->configdata));
            if (!empty($config->playlistid)) {
                $block = block_instance('kalturapodcast', $rec);
                $block->block_kalturapodcast_generate($config->courseid, true);
                $counter++;
            }
        }
        mtrace($counter . ' kaltura feeds refreshed (took ' . microtime_diff($starttime, microtime()) . ' seconds)');
    }
    function instance_config_save($data, $nolongerused = false) {
        global $COURSE;
        if (isset($data->feedsort)) { //only trigger this if comes from edit_form
            $this->block_kalturapodcast_generate($COURSE, true, true, $data);
        }
        parent::instance_config_save($data);
    }
}

function course_data_find_by_shortname($shortname,$courseid) {
    global $DB;
    $sql = "SELECT d.data FROM {local_course_info_data} d, {local_course_info_field} f
            WHERE f.id=d.fieldid AND f.shortname = ? AND d.courseid = ?";
    return $DB->get_records_sql($sql, array($shortname, $courseid));
}