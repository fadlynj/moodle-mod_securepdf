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
 * English strings for securepdf.
 *
 * @package    mod_securepdf
 * @copyright  2020 Yedidia Klein <yedidia@openapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Secure PDF';
$string['modulenameplural'] = 'Secure PDFs';
$string['modulename_help'] = 'Use the securepdf module for adding PDF files securely to your course. The student won\'t be able to download the pdf, It will be shown to him as image for page - without right click to save image';

$string['securepdf:addinstance'] = 'Add a new securepdf';
$string['securepdf:view'] = 'View securepdf';

$string['pluginadministration'] = 'Secure PDF administration';
$string['pluginname'] = 'Secure PDF';

$string['eventpage_view'] = 'Secure PDF page viewed';

$string['resolution'] = 'Default resolution of image';
$string['resolution_explain'] = 'Set the resolution of image from PDF, as higher resolution you are using - the page will load slower';
$string['downloadresolution'] = 'Download resolution';
$string['downloadresolution_explain'] = 'Resolution (DPI) used to rasterise pages when building a watermarked download. Lower is faster and produces smaller files but is less sharp. Leave empty to use the default resolution above. Rasterised pages are cached, so the first download of a file is the slowest.';

$string['page'] = 'Page';
$string['nosuchpage'] = 'Error - No such page!';
$string['install_imagick'] = 'PHP-Imagick need to be installed, otherwise you and student won\'t be able to see the content';
$string['imagick_pdf_policy'] = 'You must set the policy of ImageMagick to allow PDF read. See https://stackoverflow.com/questions/52703123/override-default-imagemagick-policy-xml';
$string['cachedef_pages'] = 'Pages from PDF cache';
$string['imagickrequired'] = 'PHP Imagemagick extension is required';

$string['addusername'] = 'Add username to each image';
$string['addusername_explain'] = 'Add username to each image of the PDF';
$string['addsiteaddress'] = 'Add site name to image';
$string['addsiteaddress_explain'] = 'Add site name to each image of the PDF';
$string['usernameposition'] = 'Username and site name position';
$string['usernameposition_explain'] = 'Set the position of username and site name on the image';
$string['top'] = 'Top';
$string['bottom'] = 'Bottom';
$string['middle'] = 'Middle';
$string['showall'] = 'Show all slides in one long page ';
$string['pagesperview'] = 'Pages per view';
$string['pagesperview_help'] = 'How many PDF pages to show on a single screen when not using the "one long page" option. Set to 1 to show a single page at a time.';
$string['allowdownload'] = 'Allow download original PDF to students';
$string['downloadwatermark'] = 'Download watermark';
$string['dlwmconfidential'] = 'Stamp "CONFIDENTIAL" on the downloaded PDF';
$string['dlwmtext'] = 'Custom stamp text';
$string['dlwmtext_help'] = 'Text for the large diagonal stamp on the downloaded PDF. Leave empty to use "CONFIDENTIAL".';
$string['dlwmuser'] = 'Add downloader name to the downloaded PDF';
$string['dlwmip'] = 'Add downloader IP address to the downloaded PDF';
$string['dlwmtime'] = 'Add download date and time to the downloaded PDF';
$string['dlwmtextcolor'] = 'Info text colour';
$string['dlwmbgcolor'] = 'Info background colour';
$string['dlwmbordercolor'] = 'Info border colour';
$string['dlwmbgopacity'] = 'Info background opacity';
$string['dlwmfont'] = 'Info font';
$string['dlwmfontsize'] = 'Info text size';
$string['dlwmfontsizeauto'] = 'Auto';
$string['dlwmposition'] = 'Info position';
$string['dlwmpos_topleft'] = 'Top left';
$string['dlwmpos_topcenter'] = 'Top centre';
$string['dlwmpos_topright'] = 'Top right';
$string['dlwmpos_middleleft'] = 'Middle left';
$string['dlwmpos_middlecenter'] = 'Middle centre';
$string['dlwmpos_middleright'] = 'Middle right';
$string['dlwmpos_bottomleft'] = 'Bottom left';
$string['dlwmpos_bottomcenter'] = 'Bottom centre';
$string['dlwmpos_bottomright'] = 'Bottom right';
$string['dlwmcolor'] = 'Colour format';
$string['dlwmcolor_help'] = 'Enter a colour as a hex code in the form #RRGGBB, e.g. #c80000 for red. The short form #RGB is also accepted.';
$string['confidential'] = 'CONFIDENTIAL';
$string['downloadedby'] = 'Downloaded by: {$a}';
$string['ipaddress'] = 'IP: {$a}';
$string['downloadtime'] = 'Downloaded: {$a}';
$string['strftimedownload'] = '%d %B %Y, %H:%M';
$string['downloadpdf'] = 'Download PDF';
$string['jumptopage'] = 'Go to page';
$string['go'] = 'Go';
$string['notallowedtodownload'] = 'You are not allowed to download the PDF';
$string['nofile'] = 'No file found';
$string['nocacheyet'] = 'No cache yet - Please wait...';
$string['nocache'] = 'There is a problem with the cache or with cron - please contact the administrator';

