<?php
defined('MOODLE_INTERNAL') || die();

function kburnsvideo_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

function kburnsvideo_add_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $DB->insert_record('kburnsvideo', $data);
    return $data->id;
}

function kburnsvideo_update_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    $DB->update_record('kburnsvideo', $data);
    return true;
}

function kburnsvideo_delete_instance($id) {
    global $DB;
    if (!$kburnsvideo = $DB->get_record('kburnsvideo', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('kburnsvideo', ['id' => $kburnsvideo->id]);
    return true;
}
