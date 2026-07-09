<?php
namespace mod_kburnsvideo\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');

class publish_video extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id to publish into'),
            'section' => new external_value(PARAM_INT, 'Course section number (0 = general section)'),
            'name' => new external_value(PARAM_TEXT, 'Activity title'),
            'transcript' => new external_value(PARAM_RAW, 'Concatenated narration transcript', VALUE_DEFAULT, ''),
            'draftitemid' => new external_value(PARAM_INT, 'Draft area item id containing the uploaded video (from webservice/upload.php)'),
        ]);
    }

    public static function execute(int $courseid, int $section, string $name, string $transcript, int $draftitemid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'section' => $section,
            'name' => $name,
            'transcript' => $transcript,
            'draftitemid' => $draftitemid,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        self::validate_context($coursecontext);
        require_capability('mod/kburnsvideo:addinstance', $coursecontext);

        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = 'kburnsvideo';
        $moduleinfo->course = $course->id;
        $moduleinfo->section = $params['section'];
        $moduleinfo->visible = 1;
        $moduleinfo->name = $params['name'];
        $moduleinfo->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0];
        $moduleinfo->transcript = $params['transcript'];

        $moduleinfo = create_module($moduleinfo);

        $cmid = $moduleinfo->coursemodule;
        $modcontext = \context_module::instance($cmid);

        file_save_draft_area_files(
            $params['draftitemid'],
            $modcontext->id,
            'mod_kburnsvideo',
            'video',
            0,
            ['subdirs' => false, 'maxfiles' => 1]
        );

        return [
            'cmid' => $cmid,
            'courseid' => $course->id,
            'url' => (new \moodle_url('/mod/kburnsvideo/view.php', ['id' => $cmid]))->out(false),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'The new course module id'),
            'courseid' => new external_value(PARAM_INT, 'The course id'),
            'url' => new external_value(PARAM_URL, 'Direct URL to view the new activity'),
        ]);
    }
}
