<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_kburnsvideo_publish_video' => [
        'classname'    => '\mod_kburnsvideo\external\publish_video',
        'description'  => 'Create a new AI Slideshow Video activity in a course section and attach an already-uploaded video from the user\'s draft file area.',
        'type'         => 'write',
        'capabilities' => 'mod/kburnsvideo:addinstance',
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
