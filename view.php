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
 * Prints a particular instance of securepdf
 *
 * @package    mod_securepdf
 * @copyright  2020 Yedidia Klein <yedidia@openapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param('id', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

// Allow jumping directly to a 1-based page number (used by the "go to page" box).
$jump = optional_param('jump', 0, PARAM_INT);
if ($jump > 0) {
    $page = $jump - 1;
}

// Counter for reload.
// This is used for the one page view when cache is not yet created.
$counter = optional_param('counter', 0, PARAM_INT);

$settings = get_config('securepdf');

$cm = get_coursemodule_from_id('securepdf', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$securepdf = new securepdf($context, $cm, $course);

require_login($course, true, $cm);
require_capability('mod/securepdf:view', $context);

$PAGE->set_pagelayout('incourse');

$url = new moodle_url('/mod/securepdf/view.php', array('id' => $id));
$PAGE->set_url('/mod/securepdf/view.php', array('id' => $cm->id));

if (!securepdf::check_imagick()) {
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    die();
}

// Check if we want all pages in one long page.
// Get data from securepdf table.
$securepdfdata = $DB->get_record('securepdf', array('id' => $securepdf->get_instance()->id), '*', MUST_EXIST);
$onepageview = $securepdfdata->onepageview;
if ($onepageview) {
    echo $OUTPUT->header();

    // check if we have to provide a download link of pdf
    if ($securepdfdata->allowdownload) {
        $downloadurl = $CFG->wwwroot . '/mod/securepdf/download.php?id=' . $id;
        // Show a styled button to download the PDF.
        $icon = '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> ';
        echo html_writer::link($downloadurl, $icon . get_string('downloadpdf', 'mod_securepdf'),
            ['target' => '_blank', 'class' => 'btn btn-primary btn-sm mod_securepdf_downloadbtn']);
    }

    $cached = \mod_securepdf\view::checkcache($cm, 0);
    $numpages = $cached['numpages'];
    if (!$numpages) { // No cache - Get page num only.
        echo '<br><br>' . get_string('nocacheyet', 'mod_securepdf');
        // Refresh every minutes.
        $PAGE->requires->js_call_amd('mod_securepdf/reload', 'init', ['counter']);
        // Adhoc task for generating the cache of all pages
        // This situation happen while cache was purged
        $adhoccache = new \mod_securepdf\task\create_cache();
        $adhoccache->set_custom_data(['moduleid' => $cm->id]);
        \core\task\manager::queue_adhoc_task($adhoccache);
    } else {
        for ($i = 0; $i < $numpages; $i++) {
            $page = $i;
            $data = \mod_securepdf\view::getpagedata($cm, $page);
            if (!$data) { // If there is not yet cache for this page.
                echo '<br><br>' . get_string('nocacheyet', 'mod_securepdf');
                // Refresh every minutes.
                if ($counter < 3) {
                    $PAGE->requires->js_call_amd('mod_securepdf/reload', 'init', ['counter']);
                } else if ($counter < 4) { // after 3 times - stop reloading and run the adhoc task
                    // Adhoc task for generating the cache of all pages
                    $adhoccache = new \mod_securepdf\task\create_cache();
                    $adhoccache->set_custom_data(['moduleid' => $cm->id]);
                    \core\task\manager::queue_adhoc_task($adhoccache);
                } else {
                    echo '<br><br>' . get_string('nocache', 'mod_securepdf');
                }
                break;
            }
            // Add watermark to image.
            $data = \mod_securepdf\view::addwatermark($data, $settings);
            echo $OUTPUT->render_from_template('mod_securepdf/singleformulti',
            [   'base64' => $data,
                'page' => $page,
            ]);
        }
    }
} else {
    // Paged view - one or more pages are shown per screen.

    // How many pages to show on a single screen.
    $perpage = isset($securepdfdata->pagesperview) ? max(1, (int)$securepdfdata->pagesperview) : 1;

    // Find out the total number of pages (from cache, fall back to parsing the PDF).
    $cached = \mod_securepdf\view::checkcache($cm, $page);
    $numpages = $cached['numpages'];
    if (!$numpages) {
        // No cache yet - queue the adhoc task and parse on the fly so the page still renders.
        $adhoccache = new \mod_securepdf\task\create_cache();
        $adhoccache->set_custom_data(['moduleid' => $cm->id]);
        \core\task\manager::queue_adhoc_task($adhoccache);

        $numpagesdata = \mod_securepdf\view::getnumpages($context, $settings->resolution, $cm, $page);
        $numpages = $numpagesdata['numpages'];
    }

    // Align the requested page to the start of its chunk and keep it in range.
    if ($page < 0 || $page >= $numpages) {
        $page = 0;
    }
    if ($perpage > 1) {
        $page = $page - ($page % $perpage);
    }
    $lastpage = min($page + $perpage, $numpages) - 1; // Inclusive index of the last page in this chunk.

    // Update page views in table (one row per page) - in order to be able to set completion.
    for ($p = $page; $p <= $lastpage; $p++) {
        $pageview = ['module' => $cm->id,
                    'userid' => $USER->id,
                    'page' => $p
                    ];
        $exist = $DB->get_record('securepdf_pageviews', $pageview);
        if ($exist) {
            $pageview['timemodified'] = time();
            $pageview['id'] = $exist->id;
            $DB->update_record('securepdf_pageviews', $pageview);
        } else {
            $pageview['timemodified'] = time();
            $pageview['timecreated'] = time();
            $DB->insert_record('securepdf_pageviews', $pageview);
        }

        $event = \mod_securepdf\event\page_view::create(array(
            'objectid' => $securepdf->get_instance()->id,
            'context' => context_module::instance($cm->id),
            'other' => $p + 1
        ));
        $event->trigger();
    }

    // Update 'viewed' state if required by completion system.
    // It's here and not in top of this file because we need the total number of pages in this PDF.
    $completion = new completion_info($course);
    // Check if user viewed all pages.
    $allpages = $DB->count_records('securepdf_pageviews', ['module' => $cm->id, 'userid' => $USER->id]);
    if ($allpages == $numpages) {
        $completion->set_module_viewed($cm);
    }

    echo $OUTPUT->header();

    // Collect the images for every page in this chunk.
    $images = [];
    for ($p = $page; $p <= $lastpage; $p++) {
        $imgdata = \mod_securepdf\view::getpagedata($cm, $p);
        if (!$imgdata) { // No cache for this page - parse it now (also writes the cache).
            $numpagesdata = \mod_securepdf\view::getnumpages($context, $settings->resolution, $cm, $p);
            $imgdata = $numpagesdata['data'];
        }
        if ($imgdata) {
            $imgdata = \mod_securepdf\view::addwatermark($imgdata, $settings);
            $images[] = ['base64' => $imgdata, 'page' => $p + 1];
        }
    }

    // Build a compact, windowed list of chunk links (first ... around current ... last)
    // so a very large PDF does not render thousands of buttons.
    $totalchunks = (int)ceil($numpages / $perpage);
    $currentchunk = (int)floor($page / $perpage);
    $window = 2; // Chunks to show on each side of the current one.

    $show = [];
    $show[0] = true;
    $show[$totalchunks - 1] = true;
    for ($c = $currentchunk - $window; $c <= $currentchunk + $window; $c++) {
        if ($c >= 0 && $c < $totalchunks) {
            $show[$c] = true;
        }
    }
    $chunkindices = array_keys($show);
    sort($chunkindices, SORT_NUMERIC);

    $pages = [];
    $previndex = null;
    foreach ($chunkindices as $c) {
        // Insert an ellipsis marker when there is a gap between shown chunks.
        if ($previndex !== null && $c > $previndex + 1) {
            $pages[] = ['ellipsis' => true];
        }
        $start = $c * $perpage;
        $end = min($start + $perpage, $numpages) - 1;
        $label = ($perpage > 1) ? (($start + 1) . '-' . ($end + 1)) : (string)($start + 1);
        $pages[] = [
            'ellipsis' => false,
            'url'      => $CFG->wwwroot . '/mod/securepdf/view.php?id=' . $id . '&page=' . $start,
            'page'     => $label,
            'active'   => ($start == $page),
        ];
        $previndex = $c;
    }

    // Previous / next chunk.
    $previousstart = $page - $perpage;
    $hasprevious = ($previousstart >= 0);
    if (!$hasprevious) {
        $previousstart = 0;
    }
    $nextstart = $page + $perpage;
    $hasnext = ($nextstart < $numpages);
    if (!$hasnext) {
        $nextstart = $page;
    }

    // Header label, e.g. "1" for a single page or "1-5" for a chunk.
    $pagelabel = ($perpage > 1) ? (($page + 1) . '-' . ($lastpage + 1)) : ($page + 1);

    echo $OUTPUT->render_from_template('mod_securepdf/imageview',
        [   'images'      => $images,
            'pagelabel'   => $pagelabel,
            'total'       => $numpages,
            'pages'       => $pages,
            'hasnext'     => $hasnext,
            'hasprevious' => $hasprevious,
            'nexturl'     => $CFG->wwwroot . '/mod/securepdf/view.php?id=' . $id . '&page=' . $nextstart,
            'previousurl' => $CFG->wwwroot . '/mod/securepdf/view.php?id=' . $id . '&page=' . $previousstart,
            'candownload' => !empty($securepdfdata->allowdownload),
            'downloadurl' => $CFG->wwwroot . '/mod/securepdf/download.php?id=' . $id,
            'multichunk'  => ($totalchunks > 1),
            'id'          => $id,
            'jumpaction'  => $CFG->wwwroot . '/mod/securepdf/view.php',
            ]);
}

echo $OUTPUT->footer();
