<?php
namespace mod_kburnsvideo;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use mod_kburnsvideo\external\publish_video;

final class publish_video_test extends \core_external\tests\externallib_testcase {

    public function test_publish_video_creates_activity(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $this->setUser($teacher);

        $usercontext = \context_user::instance($teacher->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftitemid,
            'filepath'  => '/',
            'filename'  => 'lecture.mp4',
        ], 'fake video bytes');

        $result = publish_video::execute($course->id, 0, 'Week 1 Lecture', 'Slide one narration.', $draftitemid);
        $result = external_api::clean_returnvalue(publish_video::execute_returns(), $result);

        $this->assertNotEmpty($result['cmid']);
        $this->assertEquals($course->id, $result['courseid']);

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $result['cmid']]);
        $record = $DB->get_record('kburnsvideo', ['id' => $instanceid]);
        $this->assertEquals('Week 1 Lecture', $record->name);
        $this->assertEquals('Slide one narration.', $record->transcript);

        $modcontext = \context_module::instance($result['cmid']);
        $files = $fs->get_area_files($modcontext->id, 'mod_kburnsvideo', 'video', 0, 'itemid', false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertEquals('lecture.mp4', $file->get_filename());
    }

    public function test_publish_video_requires_capability(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->setUser($student);

        $draftitemid = file_get_unused_draft_itemid();

        $this->expectException(\required_capability_exception::class);
        publish_video::execute($course->id, 0, 'Should fail', '', $draftitemid);
    }
}
