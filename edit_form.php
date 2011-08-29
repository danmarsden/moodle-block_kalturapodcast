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
 * Form for ediging Kaltura Podcast block
 *
 * @package    block
 * @subpackage kalturapodcast
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_kalturapodcast_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        // Fields for editing kaltura podcast block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_kalturapodcast'));
        $mform->setType('config_title', PARAM_MULTILANG);

        $options = array(5=>'5', 10=>'10', 15=>'15' ,20=>'20',25=>'25');
        $mform->addElement('select', 'config_feednum', get_string('displaynum', 'block_kalturapodcast'), $options);
        $mform->setType('config_feednum', PARAM_INT);
        $mform->setDefault('config_feednum',10);

        $sortoptions = array('recent'=>get_string('latest', 'block_kalturapodcast'),
                             'name'=>get_string('name'));
        $mform->addElement('select','config_feedsort',get_string('feedsort', 'block_kalturapodcast'), $sortoptions);
        $mform->setDefault('config_feedsort','recent');

    }
}
