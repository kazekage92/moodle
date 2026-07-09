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

function kburnsvideo_view($kburnsvideo, $course, $cm, $context) {
    $params = [
        'context' => $context,
        'objectid' => $kburnsvideo->id,
    ];
    $event = \mod_kburnsvideo\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('kburnsvideo', $kburnsvideo);
    $event->trigger();

    $completion = new \completion_info($course);
    $completion->set_module_viewed($cm);
}

function kburnsvideo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    require_course_login($course, true, $cm);
    if ($filearea !== 'video') {
        return false;
    }
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_kburnsvideo', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, null, 0, $forcedownload, $options);
}
