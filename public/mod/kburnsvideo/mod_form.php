<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_kburnsvideo_mod_form extends moodleform_mod {
    public function definition() {
        global $PAGE;
        $PAGE->force_settings_menu();
        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('general'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
