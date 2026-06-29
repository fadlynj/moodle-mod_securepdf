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
 * @package    mod_securepdf
 * @copyright  2020 Yedidia Klein <yedidia@openapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/pdflib.php');

$id = required_param('id', PARAM_INT); // Module id.
// get course id from module id.
$cm = get_coursemodule_from_id('securepdf', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/securepdf:view', $context);
$securepdfdata = $DB->get_record('securepdf', array('id' => $cm->instance), '*', MUST_EXIST);

if (!$securepdfdata->allowdownload) {
    print_error('notallowedtodownload', 'securepdf');
}
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_securepdf', 'content', 0, 'sortorder', false);
if (empty($files)) {
    print_error('nofiles', 'securepdf');
}
$pdfcontent = '';
$filename = '';
foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }
    $pdfcontent = $file->get_content();
    $filename = $file->get_filename();
    break;
}
if (empty($pdfcontent)) {
    print_error('nofiles', 'securepdf');
}

// Build the watermark from the activity configuration.
// Large diagonal stamp (CONFIDENTIAL or a custom text).
$stamp = '';
if (!empty($securepdfdata->dlwmconfidential)) {
    $custom = isset($securepdfdata->dlwmtext) ? trim($securepdfdata->dlwmtext) : '';
    $stamp = ($custom !== '') ? $custom : get_string('confidential', 'securepdf');
}
// Footer info lines (who / IP / when).
$footerlines = [];
if (!empty($securepdfdata->dlwmuser)) {
    $footerlines[] = get_string('downloadedby', 'securepdf', fullname($USER) . ' (' . $USER->username . ')');
}
if (!empty($securepdfdata->dlwmip)) {
    $footerlines[] = get_string('ipaddress', 'securepdf', getremoteaddr());
}
if (!empty($securepdfdata->dlwmtime)) {
    $footerlines[] = get_string('downloadtime', 'securepdf', userdate(time()));
}

$haswatermark = ($stamp !== '' || !empty($footerlines));

if ($haswatermark) {
    try {
        // FPDI needs a real source file.
        $tmpdir = make_request_directory();
        $tmpsrc = $tmpdir . '/source.pdf';
        file_put_contents($tmpsrc, $pdfcontent);

        $pdf = new pdf();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pagecount = $pdf->set_pdf($tmpsrc);

        for ($p = 1; $p <= $pagecount; $p++) {
            $pdf->copy_page();
            $pw = $pdf->getPageWidth();
            $ph = $pdf->getPageHeight();

            // Large diagonal stamp.
            if ($stamp !== '') {
                $pdf->SetAlpha(0.20);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->StartTransform();
                $pdf->Rotate(45, $pw / 2, $ph / 2);
                $pdf->SetXY(0, ($ph / 2) - 15);
                $pdf->Cell($pw, 30, $stamp, 0, 0, 'C');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
            }

            // Footer info line(s).
            if (!empty($footerlines)) {
                $pdf->SetAlpha(0.6);
                $pdf->SetTextColor(60, 60, 60);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetXY(5, $ph - 9);
                $pdf->Cell($pw - 10, 6, implode('   |   ', $footerlines), 0, 0, 'C');
                $pdf->SetAlpha(1);
            }
        }

        $pdfcontent = $pdf->Output('', 'S');
    } catch (\Throwable $e) {
        // If stamping fails (e.g. an unsupported PDF), fall back to the original file.
        debugging('mod_securepdf: failed to watermark PDF on download - ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

send_file($pdfcontent, $filename, 0, 0, true, true, 'application/pdf');

