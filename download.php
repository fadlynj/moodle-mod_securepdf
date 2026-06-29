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

/**
 * Convert a #RRGGBB (or #RGB) hex colour to an [r, g, b] array.
 *
 * @param string $hex colour value
 * @param array $fallback [r, g, b] used when the value is invalid
 * @return array [r, g, b]
 */
function securepdf_hex_to_rgb($hex, array $fallback) {
    $hex = ltrim(trim((string)$hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return $fallback;
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

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
$contenthash = '';
foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }
    $pdfcontent = $file->get_content();
    $filename = $file->get_filename();
    $contenthash = $file->get_contenthash();
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
    $footerlines[] = get_string('downloadtime', 'securepdf',
        userdate(time(), get_string('strftimedownload', 'securepdf')));
}

$haswatermark = ($stamp !== '' || !empty($footerlines));

if ($haswatermark) {
    try {
        // Rasterise each PDF page with Imagick (already required by this plugin) and
        // rebuild the PDF with TCPDF, drawing the watermark text on top of each page.
        // The core \pdf class (TCPDF) cannot import existing PDF pages, so we re-render.
        $config = get_config('securepdf');
        // Dedicated (usually lower) download resolution; fall back to the view resolution.
        if (!empty($config->downloadresolution)) {
            $res = (int)$config->downloadresolution;
        } else if (!empty($config->resolution)) {
            $res = (int)$config->resolution;
        } else {
            $res = 150;
        }

        // Rasterising the PDF is the expensive step, so cache the per-page JPEGs
        // (keyed by file content + resolution). Repeat downloads skip Imagick entirely;
        // the per-user watermark is still drawn fresh below.
        $cache = \cache::make('mod_securepdf', 'downloadpages');
        $cachekey = $contenthash . '_' . $res;
        $jpegs = [];
        $count = $cache->get($cachekey . '_count');
        if ($count !== false && $count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $blob = $cache->get($cachekey . '_' . $i);
                if ($blob === false) {
                    $jpegs = [];
                    break;
                }
                $jpegs[$i] = $blob;
            }
        }
        if (empty($jpegs)) {
            $im = new \Imagick();
            $im->setResolution($res, $res);
            // Render straight to JPEG so Imagick never keeps a full RGBA raster per page.
            $im->setColorspace(\Imagick::COLORSPACE_SRGB);
            $im->readImageBlob($pdfcontent);
            foreach ($im as $frame) {
                // Flatten transparency onto white, strip metadata, compress: smaller
                // blob = faster embed + smaller output.
                $frame->setImageBackgroundColor('white');
                if (defined('\Imagick::ALPHACHANNEL_REMOVE')) {
                    $frame->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                }
                $frame->setImageFormat('jpeg');
                $frame->setImageCompressionQuality(82);
                $frame->stripImage();
                $jpegs[] = $frame->getImageBlob();
            }
            $im->clear();
            $im->destroy();
            // Persist for subsequent downloads.
            $cache->set($cachekey . '_count', count($jpegs));
            foreach ($jpegs as $i => $blob) {
                $cache->set($cachekey . '_' . $i, $blob);
            }
        }

        $pdf = new pdf('P', 'pt');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        foreach ($jpegs as $jpeg) {
            // Page size in points, derived from the image's pixel dimensions.
            $imgsize = getimagesizefromstring($jpeg);
            if (!$imgsize || $imgsize[0] <= 0 || $imgsize[1] <= 0) {
                continue;
            }
            $wpt = $imgsize[0] * 72.0 / $res;
            $hpt = $imgsize[1] * 72.0 / $res;

            $pdf->AddPage('', array($wpt, $hpt));
            $pdf->Image('@' . $jpeg, 0, 0, $wpt, $hpt, 'JPEG');

            // Large diagonal stamp.
            if ($stamp !== '') {
                $pdf->SetAlpha(0.22);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->SetFont('helvetica', 'B', max(24, $wpt / 9));
                $pdf->StartTransform();
                $pdf->Rotate(45, $wpt / 2, $hpt / 2);
                $pdf->SetXY(0, ($hpt / 2) - ($wpt / 14));
                $pdf->Cell($wpt, $wpt / 7, $stamp, 0, 0, 'C');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
            }

            // Footer info box: each line stacked in a column inside a bordered box.
            if (!empty($footerlines)) {
                // Configurable appearance, with safe fallbacks.
                $textrgb = securepdf_hex_to_rgb($securepdfdata->dlwmtextcolor ?? '', [200, 0, 0]);
                $bgrgb = securepdf_hex_to_rgb($securepdfdata->dlwmbgcolor ?? '', [255, 255, 255]);
                $borderrgb = securepdf_hex_to_rgb($securepdfdata->dlwmbordercolor ?? '', [200, 0, 0]);
                $bgalpha = isset($securepdfdata->dlwmbgopacity)
                    ? min(100, max(0, (int)$securepdfdata->dlwmbgopacity)) / 100.0 : 0.80;
                $allowedfonts = ['helvetica', 'times', 'courier'];
                $font = in_array($securepdfdata->dlwmfont ?? '', $allowedfonts, true)
                    ? $securepdfdata->dlwmfont : 'helvetica';
                // Fixed point size if configured, otherwise scale to the page.
                $fixedsize = isset($securepdfdata->dlwmfontsize) ? (int)$securepdfdata->dlwmfontsize : 0;
                $footsize = ($fixedsize > 0) ? $fixedsize : max(7, $wpt / 55);

                $lineheight = $footsize * 1.6;
                $padding = $footsize * 0.8;
                $margin = max(8, $wpt / 60);
                $radius = $footsize / 3;

                $pdf->SetFont($font, '', $footsize);

                // Box height fits all lines; width fits the longest line.
                $boxheight = ($lineheight * count($footerlines)) + ($padding * 2);
                $boxwidth = 0;
                foreach ($footerlines as $line) {
                    $linewidth = $pdf->GetStringWidth($line) + ($padding * 2);
                    if ($linewidth > $boxwidth) {
                        $boxwidth = $linewidth;
                    }
                }
                // Keep the box on the page.
                $boxwidth = min($boxwidth, $wpt - ($margin * 2));

                // Place the box according to the configured position.
                $pos = isset($securepdfdata->dlwmposition) ? (string)$securepdfdata->dlwmposition : 'bottomleft';
                if (strpos($pos, 'left') !== false) {
                    $boxx = $margin;
                } else if (strpos($pos, 'right') !== false) {
                    $boxx = $wpt - $boxwidth - $margin;
                } else {
                    $boxx = ($wpt - $boxwidth) / 2;
                }
                if (strpos($pos, 'top') !== false) {
                    $boxy = $margin;
                } else if (strpos($pos, 'middle') !== false) {
                    $boxy = ($hpt - $boxheight) / 2;
                } else {
                    $boxy = $hpt - $boxheight - $margin;
                }

                // Translucent background fill (its own opacity).
                $pdf->SetAlpha($bgalpha);
                $pdf->SetFillColorArray($bgrgb);
                $pdf->RoundedRect($boxx, $boxy, $boxwidth, $boxheight, $radius, '1111', 'F');

                // Solid border on top of the fill.
                $pdf->SetAlpha(1);
                $pdf->SetDrawColorArray($borderrgb);
                $pdf->SetLineWidth(max(0.4, $footsize / 14));
                $pdf->RoundedRect($boxx, $boxy, $boxwidth, $boxheight, $radius, '1111', 'D');

                // Stacked text lines (column).
                $pdf->SetTextColorArray($textrgb);
                $texty = $boxy + $padding;
                foreach ($footerlines as $line) {
                    $pdf->SetXY($boxx + $padding, $texty);
                    $pdf->Cell($boxwidth - ($padding * 2), $lineheight, $line, 0, 0, 'L');
                    $texty += $lineheight;
                }
                $pdf->SetAlpha(1);
            }
        }

        $pdfcontent = $pdf->Output('', 'S');
    } catch (\Throwable $e) {
        // If stamping fails, fall back to the original file so the download still works.
        debugging('mod_securepdf: failed to watermark PDF on download - ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

send_file($pdfcontent, $filename, 0, 0, true, true, 'application/pdf');

