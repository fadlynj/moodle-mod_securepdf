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
 * The main securepdf configuration form.
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_securepdf
 * @copyright  2020 Yedidia Klein <yedidia@openapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

class mod_securepdf_mod_form extends moodleform_mod {
    /**
     * Defines the securepdf instance configuration form.
     *
     * @return void
     */
    public function definition() {
        global $CFG, $USER, $DB;

        securepdf::check_imagick();

        $mform =& $this->_form;

        // Name and description fields.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name',
                        get_string('maximumchars', '', 255),
                        'maxlength',
                        255,
                        'client');
        moodleform_mod::standard_intro_elements();

        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = ['.pdf'];
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['mainfile'] = true;

        $mform->addElement('filemanager', 'uploaded', get_string('selectfiles'), null, $filemanageroptions);

        // Add a checkbox to allow the user to select if they want to show all slides in a single page
        $mform->addElement('checkbox', 'onepageview', get_string('showall', 'securepdf'));
        $mform->setDefault('onepageview', 0);

        // Number of pages to show per screen (paged view only).
        $perpageoptions = [];
        for ($i = 1; $i <= 10; $i++) {
            $perpageoptions[$i] = $i;
        }
        $mform->addElement('select', 'pagesperview', get_string('pagesperview', 'securepdf'), $perpageoptions);
        $mform->setDefault('pagesperview', 1);
        $mform->addHelpButton('pagesperview', 'pagesperview', 'securepdf');
        // Pages per view is irrelevant when showing everything in one long page.
        $mform->disabledIf('pagesperview', 'onepageview', 'checked');

        // Add a checkbox to allowdownload the file
        $mform->addElement('checkbox', 'allowdownload', get_string('allowdownload', 'securepdf'));
        $mform->setDefault('allowdownload', 0);

        // Download watermark options (only relevant when download is allowed).
        $mform->addElement('static', 'dlwmheading', '',
            html_writer::tag('strong', get_string('downloadwatermark', 'securepdf')));

        $mform->addElement('checkbox', 'dlwmconfidential', get_string('dlwmconfidential', 'securepdf'));
        $mform->setDefault('dlwmconfidential', 0);
        $mform->disabledIf('dlwmconfidential', 'allowdownload', 'notchecked');

        $mform->addElement('text', 'dlwmtext', get_string('dlwmtext', 'securepdf'), array('size' => '40'));
        $mform->setType('dlwmtext', PARAM_TEXT);
        $mform->addHelpButton('dlwmtext', 'dlwmtext', 'securepdf');
        $mform->disabledIf('dlwmtext', 'allowdownload', 'notchecked');
        $mform->disabledIf('dlwmtext', 'dlwmconfidential', 'notchecked');

        $mform->addElement('checkbox', 'dlwmuser', get_string('dlwmuser', 'securepdf'));
        $mform->setDefault('dlwmuser', 0);
        $mform->disabledIf('dlwmuser', 'allowdownload', 'notchecked');

        $mform->addElement('checkbox', 'dlwmip', get_string('dlwmip', 'securepdf'));
        $mform->setDefault('dlwmip', 0);
        $mform->disabledIf('dlwmip', 'allowdownload', 'notchecked');

        $mform->addElement('checkbox', 'dlwmtime', get_string('dlwmtime', 'securepdf'));
        $mform->setDefault('dlwmtime', 0);
        $mform->disabledIf('dlwmtime', 'allowdownload', 'notchecked');

        // Appearance of the download info box (text / IP / time).
        $mform->addElement('text', 'dlwmtextcolor', get_string('dlwmtextcolor', 'securepdf'),
            array('size' => '8', 'placeholder' => '#c80000'));
        $mform->setType('dlwmtextcolor', PARAM_TEXT);
        $mform->setDefault('dlwmtextcolor', '#c80000');
        $mform->addHelpButton('dlwmtextcolor', 'dlwmcolor', 'securepdf');
        $mform->disabledIf('dlwmtextcolor', 'allowdownload', 'notchecked');

        $mform->addElement('text', 'dlwmbgcolor', get_string('dlwmbgcolor', 'securepdf'),
            array('size' => '8', 'placeholder' => '#ffffff'));
        $mform->setType('dlwmbgcolor', PARAM_TEXT);
        $mform->setDefault('dlwmbgcolor', '#ffffff');
        $mform->addHelpButton('dlwmbgcolor', 'dlwmcolor', 'securepdf');
        $mform->disabledIf('dlwmbgcolor', 'allowdownload', 'notchecked');

        $mform->addElement('text', 'dlwmbordercolor', get_string('dlwmbordercolor', 'securepdf'),
            array('size' => '8', 'placeholder' => '#c80000'));
        $mform->setType('dlwmbordercolor', PARAM_TEXT);
        $mform->setDefault('dlwmbordercolor', '#c80000');
        $mform->addHelpButton('dlwmbordercolor', 'dlwmcolor', 'securepdf');
        $mform->disabledIf('dlwmbordercolor', 'allowdownload', 'notchecked');

        // Attach a native colour picker swatch next to each hex text field, two-way synced.
        global $PAGE;
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function(\$) {
                ['id_dlwmtextcolor', 'id_dlwmbgcolor', 'id_dlwmbordercolor'].forEach(function(id) {
                    var text = document.getElementById(id);
                    if (!text || text.dataset.swatchAdded) {
                        return;
                    }
                    text.dataset.swatchAdded = '1';
                    var swatch = document.createElement('input');
                    swatch.type = 'color';
                    swatch.style.marginLeft = '6px';
                    swatch.style.verticalAlign = 'middle';
                    swatch.style.cursor = 'pointer';
                    var valid = function(v) { return /^#[0-9a-fA-F]{6}\$/.test(v); };
                    if (valid(text.value)) {
                        swatch.value = text.value;
                    }
                    text.parentNode.insertBefore(swatch, text.nextSibling);
                    swatch.addEventListener('input', function() {
                        text.value = swatch.value;
                        text.dispatchEvent(new Event('change', {bubbles: true}));
                    });
                    text.addEventListener('input', function() {
                        if (valid(text.value)) {
                            swatch.value = text.value;
                        }
                    });
                    swatch.disabled = text.disabled;
                    var dl = document.getElementById('id_allowdownload');
                    if (dl) {
                        dl.addEventListener('change', function() {
                            swatch.disabled = !dl.checked;
                        });
                    }
                });
            });
        ");

        $opacityoptions = [];
        for ($o = 0; $o <= 100; $o += 10) {
            $opacityoptions[$o] = $o . '%';
        }
        $mform->addElement('select', 'dlwmbgopacity', get_string('dlwmbgopacity', 'securepdf'), $opacityoptions);
        $mform->setDefault('dlwmbgopacity', 80);
        $mform->disabledIf('dlwmbgopacity', 'allowdownload', 'notchecked');

        $fontoptions = ['helvetica' => 'Helvetica', 'times' => 'Times', 'courier' => 'Courier'];
        $mform->addElement('select', 'dlwmfont', get_string('dlwmfont', 'securepdf'), $fontoptions);
        $mform->setDefault('dlwmfont', 'helvetica');
        $mform->disabledIf('dlwmfont', 'allowdownload', 'notchecked');

        $sizeoptions = [0 => get_string('dlwmfontsizeauto', 'securepdf')];
        foreach ([6, 7, 8, 9, 10, 11, 12, 14, 16, 18] as $sz) {
            $sizeoptions[$sz] = $sz . ' pt';
        }
        $mform->addElement('select', 'dlwmfontsize', get_string('dlwmfontsize', 'securepdf'), $sizeoptions);
        $mform->setDefault('dlwmfontsize', 0);
        $mform->disabledIf('dlwmfontsize', 'allowdownload', 'notchecked');

        // Where on the page the info box is drawn.
        $positions = ['topleft', 'topcenter', 'topright',
                      'middleleft', 'middlecenter', 'middleright',
                      'bottomleft', 'bottomcenter', 'bottomright'];
        $positionoptions = [];
        foreach ($positions as $p) {
            $positionoptions[$p] = get_string('dlwmpos_' . $p, 'securepdf');
        }
        $mform->addElement('select', 'dlwmposition', get_string('dlwmposition', 'securepdf'), $positionoptions);
        $mform->setDefault('dlwmposition', 'bottomleft');
        $mform->disabledIf('dlwmposition', 'allowdownload', 'notchecked');

        // Standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('uploaded');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_securepdf', 'content', 0, array('subdirs' => true));
            $defaultvalues['uploaded'] = $draftitemid;
        }

    }

    /**
     * Validates the form input
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = [];
        return $errors;
    }
}
