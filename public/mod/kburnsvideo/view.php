<?php
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('kburnsvideo', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$kburnsvideo = $DB->get_record('kburnsvideo', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/kburnsvideo:view', $context);

kburnsvideo_view($kburnsvideo, $course, $cm, $context);

$PAGE->set_url('/mod/kburnsvideo/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($kburnsvideo->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($kburnsvideo->name));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_kburnsvideo', 'video', 0, 'itemid', false);
$file = reset($files);

if ($file) {
    $videourl = moodle_url::make_pluginfile_url(
        $context->id, 'mod_kburnsvideo', 'video', 0, '/', $file->get_filename()
    );
    echo html_writer::tag('video', '', [
        'src' => $videourl->out(),
        'controls' => 'controls',
        'style' => 'max-width:100%;',
    ]);
} else {
    echo $OUTPUT->notification(get_string('novideoyet', 'kburnsvideo'), 'info');
}

if (!empty($kburnsvideo->transcript)) {
    echo $OUTPUT->heading(get_string('transcript', 'kburnsvideo'), 3);
    echo html_writer::tag('div', nl2br(format_text($kburnsvideo->transcript, FORMAT_PLAIN)), ['class' => 'kburnsvideo-transcript']);
}

echo $OUTPUT->footer();
