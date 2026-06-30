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
 * Indonesian strings for securepdf.
 *
 * @package    mod_securepdf
 * @copyright  2020 Yedidia Klein <yedidia@openapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Secure PDF';
$string['modulenameplural'] = 'Secure PDF';
$string['modulename_help'] = 'Gunakan modul Secure PDF untuk menambahkan berkas PDF ke kursus Anda secara aman. Siswa tidak dapat mengunduh PDF, berkas akan ditampilkan sebagai gambar per halaman - tanpa klik kanan untuk menyimpan gambar.';

$string['securepdf:addinstance'] = 'Tambah Secure PDF baru';
$string['securepdf:download'] = 'Unduh berkas Secure PDF';
$string['securepdf:view'] = 'Lihat Secure PDF';

$string['pluginadministration'] = 'Administrasi Secure PDF';
$string['pluginname'] = 'Secure PDF';

$string['eventpage_view'] = 'Halaman Secure PDF dilihat';

$string['resolution'] = 'Resolusi gambar bawaan';
$string['resolution_explain'] = 'Atur resolusi gambar dari PDF, semakin tinggi resolusi yang digunakan - halaman akan dimuat lebih lambat';
$string['downloadresolution'] = 'Resolusi unduhan';
$string['downloadresolution_explain'] = 'Resolusi (DPI) yang dipakai untuk meraster halaman saat membuat unduhan bertanda air. Lebih rendah berarti lebih cepat dan ukuran berkas lebih kecil namun kurang tajam. Kosongkan untuk memakai resolusi bawaan di atas. Halaman hasil raster disimpan di tembolok, jadi unduhan pertama atas sebuah berkas adalah yang paling lambat.';

$string['page'] = 'Halaman';
$string['nosuchpage'] = 'Kesalahan - Halaman tidak ditemukan!';
$string['install_imagick'] = 'PHP-Imagick harus dipasang, jika tidak Anda dan siswa tidak akan dapat melihat konten';
$string['imagick_pdf_policy'] = 'Anda harus mengatur kebijakan ImageMagick agar mengizinkan pembacaan PDF. Lihat https://stackoverflow.com/questions/52703123/override-default-imagemagick-policy-xml';
$string['cachedef_pages'] = 'Tembolok halaman dari PDF';
$string['imagickrequired'] = 'Ekstensi PHP Imagemagick diperlukan';

$string['addusername'] = 'Tambahkan nama pengguna ke setiap gambar';
$string['addusername_explain'] = 'Tambahkan nama pengguna ke setiap gambar PDF';
$string['addsiteaddress'] = 'Tambahkan nama situs ke gambar';
$string['addsiteaddress_explain'] = 'Tambahkan nama situs ke setiap gambar PDF';
$string['usernameposition'] = 'Posisi nama pengguna dan nama situs';
$string['usernameposition_explain'] = 'Atur posisi nama pengguna dan nama situs pada gambar';
$string['top'] = 'Atas';
$string['bottom'] = 'Bawah';
$string['middle'] = 'Tengah';
$string['showall'] = 'Tampilkan semua halaman dalam satu halaman panjang';
$string['pagesperview'] = 'Halaman per tampilan';
$string['pagesperview_help'] = 'Berapa banyak halaman PDF yang ditampilkan dalam satu layar saat tidak menggunakan opsi "satu halaman panjang". Atur ke 1 untuk menampilkan satu halaman setiap kali.';
$string['allowdownload'] = 'Izinkan siswa mengunduh PDF asli';
$string['downloadwatermark'] = 'Tanda air saat unduh';
$string['dlwmconfidential'] = 'Beri cap "RAHASIA" pada PDF yang diunduh';
$string['dlwmtext'] = 'Teks cap khusus';
$string['dlwmtext_help'] = 'Teks untuk cap diagonal besar pada PDF yang diunduh. Kosongkan untuk menggunakan "RAHASIA".';
$string['dlwmuser'] = 'Tambahkan nama pengunduh pada PDF yang diunduh';
$string['dlwmip'] = 'Tambahkan alamat IP pengunduh pada PDF yang diunduh';
$string['dlwmtime'] = 'Tambahkan tanggal dan waktu unduh pada PDF yang diunduh';
$string['dlwmtextcolor'] = 'Warna teks info';
$string['dlwmbgcolor'] = 'Warna latar info';
$string['dlwmbordercolor'] = 'Warna garis tepi info';
$string['dlwmbgopacity'] = 'Opasitas latar info';
$string['dlwmfont'] = 'Font info';
$string['dlwmfontsize'] = 'Ukuran teks info';
$string['dlwmfontsizeauto'] = 'Otomatis';
$string['dlwmposition'] = 'Posisi info';
$string['dlwmpos_topleft'] = 'Kiri atas';
$string['dlwmpos_topcenter'] = 'Tengah atas';
$string['dlwmpos_topright'] = 'Kanan atas';
$string['dlwmpos_middleleft'] = 'Kiri tengah';
$string['dlwmpos_middlecenter'] = 'Tengah';
$string['dlwmpos_middleright'] = 'Kanan tengah';
$string['dlwmpos_bottomleft'] = 'Kiri bawah';
$string['dlwmpos_bottomcenter'] = 'Tengah bawah';
$string['dlwmpos_bottomright'] = 'Kanan bawah';
$string['dlwmcolor'] = 'Format warna';
$string['dlwmcolor_help'] = 'Masukkan warna sebagai kode hex dengan format #RRGGBB, mis. #c80000 untuk merah. Bentuk singkat #RGB juga diterima.';
$string['confidential'] = 'RAHASIA';
$string['downloadedby'] = 'Diunduh oleh: {$a}';
$string['ipaddress'] = 'IP: {$a}';
$string['downloadtime'] = 'Diunduh: {$a}';
$string['strftimedownload'] = '%d %B %Y, %H:%M';
$string['downloadpdf'] = 'Unduh PDF';
$string['jumptopage'] = 'Ke halaman';
$string['go'] = 'Buka';
$string['notallowedtodownload'] = 'Anda tidak diizinkan mengunduh PDF';
$string['nofile'] = 'Berkas tidak ditemukan';
$string['nocacheyet'] = 'Tembolok belum ada - Mohon tunggu...';
$string['nocache'] = 'Ada masalah dengan tembolok atau dengan cron - silakan hubungi administrator';
