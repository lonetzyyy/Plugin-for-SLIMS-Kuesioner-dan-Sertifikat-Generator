<?php
/**
 *
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 * Modified for Excel output (C) 2010 by Wardiyono (wynerst@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Report By Titles */

// key to authenticate
// define('INDEX_AUTH', '1');

// main system configuration
// require '../../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

if (!function_exists('httpQuery')) {
    function httpQuery($query = []) {
        return http_build_query(array_unique(array_merge($_GET, $query)));
    }
}

if (isset($_GET['report']) || isset($_GET['reportView'])) {
    
    $reportView = false;
    $num_recs_show = 20;
    if (isset($_GET['reportView'])) {
        $reportView = true;
    }
    
    require_once MDLBS . 'reporting/report_dbgrid.inc.php';

    if (!$reportView) {
        // Halaman Pembungkus Filter Laporan
        ?>
        <!-- filter -->
        <div class="per_title">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="m-0 mr-3"><?php echo __('Laporan Kuesioner Pengunjung'); ?></h2>
                <div class="btn-group">
                    <a href="#" onclick="parent.jQuery('#mainContent').simbioAJAX('<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['report' => null, 'reportView' => null]) ?>'); return false;" class="btn btn-default"><i class="fa fa-table"></i> <?php echo __('Daftar Kuesioner'); ?></a>
                </div>
            </div>
        </div>
        <div class="infoBox">
            <?php echo __('Filter Laporan'); ?>
        </div>
        <div class="sub_section">
            <div class="clearfix"></div>
            <form method="get" action="<?= $_SERVER['PHP_SELF'] ?>" target="reportView">
                <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>"/>
                <input type="hidden" name="mod" value="<?= htmlspecialchars($_GET['mod'] ?? '') ?>"/>
                <input type="hidden" name="report" value="yes"/>
                <div id="filterForm">
                    <div class="form-group divRow">
                        <label><?= __('NPM / Identitas') ?></label>
                        <?php echo simbio_form_element::textField('text', 'npm', '', 'class="form-control col-4"'); ?>
                    </div>
                    <div class="form-group divRow">
                        <label><?= __('Nama Lengkap') ?></label>
                        <?php echo simbio_form_element::textField('text', 'nama', '', 'class="form-control col-4"'); ?>
                    </div>
                </div>
                <input type="submit" name="applyFilter" class="btn btn-primary" value="<?php echo __('Apply Filter'); ?>" />
                <input type="hidden" name="reportView" value="true" />
            </form>
        </div>
        <!-- filter end -->
        
        <div class="paging-area"><div class="pt-3 pr-3" id="pagingBox"></div></div>
        <iframe name="reportView" id="reportView" src="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['reportView' => 'true']) ?>" frameborder="0" style="width: 100%; height: 500px;"></iframe>
        <?php
        exit;
    } else {
        // Mode Rendering IFrame (Datagrid & Spreadsheet Logic)
        ob_start();
	    global $dbs;
	    
        $reportgrid = new report_datagrid();
        $reportgrid->table_attr = 'class="s-table table table-sm table-bordered"';
        $reportgrid->setSQLColumn('id',
            'created_at AS \'' . __('Tanggal Dikirim') . '\'',
            'npm AS \'' . __('NPM / Identitas') . '\'',
            'nama AS \'' . __('Nama Lengkap') . '\'',
            'email AS \'' . __('Email') . '\'',
            'pertanyaan AS \'' . __('Detail Jawaban') . '\'');

        if (!function_exists('showJawabanRpt')) {
            function showJawabanRpt($db, $data) {
                $json = $data[5] ?? '';
                $arr = json_decode($json, true) ?? [];
                $out = '<ul style="margin:0; padding-left:15px; font-size:12px;">';
                foreach($arr as $j) {
                    $t = htmlspecialchars($j['pertanyaan'] ?? '');
                    $a = htmlspecialchars($j['jawaban'] ?? '');
                    $out .= "<li><strong>{$t}</strong>: {$a}</li>";
                }
                $out .= '</ul>';
                return $out;
            }
        }
        $reportgrid->modifyColumnContent(5, 'callback{showJawabanRpt}');
        $reportgrid->setSQLorder('created_at DESC');
        $reportgrid->invisible_fields = array(0);

        // Filter search criteria
        $criteria = "npm != 'setting'";
        if (isset($_GET['npm']) && !empty($_GET['npm'])) {
            $criteria .= ' AND npm LIKE \'%'.$dbs->escape_string($_GET['npm']).'%\'';
        }
        if (isset($_GET['nama']) && !empty($_GET['nama'])) {
            $criteria .= ' AND nama LIKE \'%'.$dbs->escape_string($_GET['nama']).'%\'';
        }
        
        $table_spec = 'kuesioner';
        $reportgrid->setSQLCriteria($criteria);

        // Spreadsheet Export
        $reportgrid->show_spreadsheet_export = true;
        $reportgrid->spreadsheet_export_btn = '<a href="'.AWB.'modules/reporting/spreadsheet.php" class="s-btn btn btn-default">'.__('Export to spreadsheet format').'</a>';

        if (isset($_GET['recsEachPage'])) {
            $recsEachPage = (integer)$_GET['recsEachPage'];
            $num_recs_show = ($recsEachPage >= 20 && $recsEachPage <= 500) ? $recsEachPage : 20;
        }

        echo $reportgrid->createDataGrid($dbs, $table_spec, $num_recs_show);

        echo '<script type="text/javascript">'."\n";
        echo 'parent.$(\'#pagingBox\').html(\''.str_replace(array("\n", "\r", "\t"), '', $reportgrid->paging_set).'\');'."\n";
        echo '</script>';

        // Menyiapkan $_SESSION['xlsdata'] manual agar teks output JSON terekstrak rapi masuk ke Excel
        $xlsquery = "SELECT created_at, npm, nama, email, pertanyaan FROM kuesioner WHERE $criteria ORDER BY created_at DESC";
        $xls_q = $dbs->query($xlsquery);
        
        $xlsdata = [];
        $xlsheader = ['Tanggal Eksekusi', 'NPM / Identitas', 'Nama Lengkap', 'Alamat Email', 'Jawaban Feedback'];
        $xlsdata[] = $xlsheader;
        
        if ($xls_q) {
            while ($a = $xls_q->fetch_row()) {
                $json = $a[4] ?? '';
                $arr = json_decode($json, true) ?? [];
                $ans_str = "";
                foreach($arr as $j) {
                    $t = $j['pertanyaan'] ?? '';
                    $ans = $j['jawaban'] ?? '';
                    $ans_str .= $t . " : " . $ans . "\n";
                }
                $a[4] = $ans_str; 
                $xlsdata[] = $a;
            }
        }

        unset($_SESSION['xlsquery']);
        $_SESSION['xlsdata'] = $xlsdata;
        $_SESSION['tblout'] = "laporan-kuesioner-arsip";
        $content = ob_get_clean();
        
        // Membungkus iFrame dengan layout kertas milik SLiMS
        require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
        exit;
    }
}

$is_bulk_print = (isset($_GET['action']) && $_GET['action'] == 'bulk_print_sertifikat');
$is_single_print = (isset($_GET['action']) && $_GET['action'] == 'print_sertifikat');
$is_send_email = (isset($_POST['sendSertifikat']) && $_POST['sendSertifikat'] == 'true');
$is_bulk_send_email = (isset($_POST['sendBulkSertifikat']) && $_POST['sendBulkSertifikat'] == 'true');

if ($is_bulk_print || $is_single_print || $is_send_email || $is_bulk_send_email) {
    if (!($can_read AND $can_write)) die('Access Denied');
    
    $kues_ids = [];
    if ($is_bulk_print || $is_bulk_send_email) {
        $raw_ids = explode(',', $_POST['kues_ids'] ?? $_GET['kues_ids'] ?? '');
        foreach ($raw_ids as $r) { if((int)$r > 0) $kues_ids[] = (int)$r; }
    } else {
        $id_param = (integer)(isset($_POST['kues_id']) ? $_POST['kues_id'] : (isset($_GET['kues_id']) ? $_GET['kues_id'] : 0));
        if ($id_param > 0) $kues_ids[] = $id_param;
    }
    
    if (empty($kues_ids)) die("Tidak ada data tabel yang dipilih.");
    $ids_str = implode(',', $kues_ids);
    
    // Fetch Settings Certificate
    $q_set = $dbs->query("SELECT template_sertifikat, config_sertifikat FROM kuesioner WHERE npm = 'setting' LIMIT 1");
    $d_set = $q_set->fetch_assoc();
    $template_file = $d_set['template_sertifikat'] ?? '';
    $config_sert = json_decode($d_set['config_sertifikat'] ?? '{}', true) ?? [];
    
    $pos_x = (int)($config_sert['pos_x'] ?? 150);
    $pos_y = (int)($config_sert['pos_y'] ?? 100);
    $ukuran_font = (int)($config_sert['ukuran_font'] ?? 64);
    $warna_teks = $config_sert['warna_teks'] ?? '#000000';
    $jenis_font = $config_sert['jenis_font'] ?? '';
    
    $warna_teks = ltrim($warna_teks, '#');
    $r = hexdec(substr($warna_teks, 0, 2));
    $g = hexdec(substr($warna_teks, 2, 2));
    $b = hexdec(substr($warna_teks, 4, 2));

    // GENERATE PDF
    require_once __DIR__ . '/../assets/fpdf.php';
    
    $template_path = __DIR__ . '/../assets/' . $template_file;
    $has_template = (!empty($template_file) && file_exists($template_path));
    
    $q_kues = $dbs->query("SELECT nama, email FROM kuesioner WHERE id IN ($ids_str)");
    if (!$q_kues || $q_kues->num_rows == 0) die("Data tidak ditemukan");
    
    if ($is_bulk_print || $is_single_print) {
        $use_zip = ($is_bulk_print && $q_kues->num_rows > 1);
        if ($use_zip) {
            $zip = new ZipArchive();
            $zipname = sys_get_temp_dir() . '/Sertifikat_Bulk_' . time() . '.zip';
            if ($zip->open($zipname, ZipArchive::CREATE) !== TRUE) {
                die("Gagal membuat arsip ZIP.");
            }
            
            $has_file = false;
            while ($d_kues = $q_kues->fetch_assoc()) {
                $last_nama = strtoupper(stripslashes($d_kues['nama'] ?? ''));
                $pdf = new FPDF('L','mm','A4');
                // Load font kustom jika bukan font standar
                $core_fonts = ['arial', 'helvetica', 'times', 'courier', 'symbol', 'zapfdingbats'];
                $current_style = 'B';
                if (!in_array(strtolower($jenis_font), $core_fonts)) {
                    $pdf->AddFont($jenis_font, '', $jenis_font . '.php');
                    $current_style = ''; // Gunakan regular jika font kustom (kecuali ada file bold-nya)
                }
                $pdf->AddPage();
                if ($has_template) $pdf->Image($template_path, 0, 0, 297, 210);
                $pdf->SetFont($jenis_font, $current_style, $ukuran_font);
                $pdf->SetTextColor($r, $g, $b);
                $pdf->SetXY(0, $pos_y);
                $pdf->Cell(297, 10, $last_nama, 0, 0, 'C');
                
                $clean_nama = preg_replace('/[^A-Za-z0-9]/', '_', $last_nama);
                $pdf_str = $pdf->Output('S');
                $zip->addFromString('Sertifikat_' . $clean_nama . '.pdf', $pdf_str);
                $has_file = true;
            }
            $zip->close();
            
            if ($has_file) {
                header("Content-Type: application/zip");
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"Sertifikat_Kuesioner_" . date('Ymd') . ".zip\"");
                header("Content-Length: " . filesize($zipname));
                ob_clean();
                flush();
                readfile($zipname);
                unlink($zipname);
                exit;
            } else {
                die("Gagal memproses file PDF ke dalam zip.");
            }
        } else {
            $d_kues = $q_kues->fetch_assoc();
            $last_nama = strtoupper(stripslashes($d_kues['nama'] ?? ''));
            $pdf = new FPDF('L','mm','A4');
            // Load font kustom jika bukan font standar
            $core_fonts = ['arial', 'helvetica', 'times', 'courier', 'symbol', 'zapfdingbats'];
            $current_style = 'B';
            if (!in_array(strtolower($jenis_font), $core_fonts)) {
                $pdf->AddFont($jenis_font, '', $jenis_font . '.php');
                $current_style = '';
            }
            $pdf->AddPage();
            if ($has_template) $pdf->Image($template_path, 0, 0, 297, 210);
            $pdf->SetFont($jenis_font, $current_style, $ukuran_font);
            $pdf->SetTextColor($r, $g, $b);
            $pdf->SetXY(0, $pos_y);
            $pdf->Cell(297, 10, $last_nama, 0, 0, 'C');
            
            $pdf_name = 'Sertifikat_Kuesioner_' . preg_replace('/[^A-Za-z0-9]/', '_', $last_nama) . '.pdf';
            $pdf->Output('I', $pdf_name);
            exit;
        }
    } else {
        $sukses = 0;
        $gagal = 0;
        
        while ($d_kues = $q_kues->fetch_assoc()) {
            $last_nama = strtoupper(stripslashes($d_kues['nama'] ?? ''));
            $last_email = $d_kues['email'] ?? '';
            
            if (empty($last_email)) {
                $gagal++;
                continue;
            }
            
            $pdf = new FPDF('L','mm','A4');
            // Load font kustom jika bukan font standar
            $core_fonts = ['arial', 'helvetica', 'times', 'courier', 'symbol', 'zapfdingbats'];
            $current_style = 'B';
            if (!in_array(strtolower($jenis_font), $core_fonts)) {
                $pdf->AddFont($jenis_font, '', $jenis_font . '.php');
                $current_style = '';
            }
            $pdf->AddPage();
            if ($has_template) $pdf->Image($template_path, 0, 0, 297, 210);
            $pdf->SetFont($jenis_font, $current_style, $ukuran_font);
            $pdf->SetTextColor($r, $g, $b);
            $pdf->SetXY($pos_x, $pos_y);
            $pdf->Cell(0, 10, $last_nama, 0, 0, 'L');
            
            try {
                $temp_pdf = sys_get_temp_dir() . '/Sertifikat_Kuesioner_' . mt_rand() . '.pdf';
                $pdf->Output('F', $temp_pdf);
                
                $mail_msg = "Halo " . ucwords(strtolower($last_nama)) . ",\n\n";
                $mail_msg .= "Terima kasih telah meluangkan waktu untuk mengisi Kuesioner Perpustakaan Widyatama.\n";
                $mail_msg .= "Sebagai bentuk apresiasi, terlampir adalah e-Certificate untuk Anda.\n\n";
                $mail_msg .= "Salam literasi,\nPerpustakaan Universitas Widyatama";

                $mail = \SLiMS\Mail::getInstance();
                $mail->clearAllRecipients();
                $mail->addAddress($last_email, $last_nama);
                $mail->Subject = 'Sertifikat Apresiasi Kuesioner Perpustakaan';
                $mail->Body = $mail_msg;
                $mail->isHTML(false);
                $mail->addAttachment($temp_pdf, 'e-Certificate_'.$last_nama.'.pdf');
                
                if ($mail->send()) {
                    $sukses++;
                } else {
                    $gagal++;
                }
                if (file_exists($temp_pdf)) { @unlink($temp_pdf); }
            } catch (\Exception $e) {
                $gagal++;
            }
        }
        
        if ($sukses > 0) {
            toastr(__("$sukses Sertifikat berhasil dikirim ke email."))->success();
        }
        if ($gagal > 0) {
            toastr(__("$gagal Sertifikat gagal dikirim (Mungkin email kosong / ditolak server)."))->error();
        }
            
        $qs = preg_replace('/&?action=[^&]*/', '', $_SERVER['QUERY_STRING'] ?? '');
        echo '<script language="Javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$qs.'\');</script>';
        exit;
    }
}

/* DATA DELETION PROCESS */
if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) die();
    $sql_op = new simbio_dbop($dbs);
    if (!is_array($_POST['itemID'])) $_POST['itemID'] = array((integer)$_POST['itemID']);
    
    foreach ($_POST['itemID'] as $itemID) {
        $sql_op->delete('kuesioner', 'id='.(integer)$itemID);
    }
    toastr(__('Data Berhasil Dihapus'))->success();
    echo '<script language="Javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.($_POST['lastQueryStr']??'').'\');</script>';
    exit();
}

$page_title = 'Laporan Kuesioner';
?>
<!-- filter -->
<div class="menuBox">
<div class="menuBoxInner laporanKuesionerIcon">
    <div class="per_title">
        <h2><?php echo __('Daftar Laporan Kuesioner'); ?></h2>
    </div>
    <div class="sub_section">
        <div class="btn-group pull-left mr-3 mb-2">
            <a href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['report' => 'yes']) ?>" class="btn btn-default"><i class="fa fa-print"></i> <?php echo __('Laporan'); ?></a>
        </div>
        <form name="search" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="search" method="get" class="form-inline">
            <input type="hidden" name="mod" value="<?php echo htmlspecialchars($_GET['mod'] ?? ''); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">
            
            <?php 
                $recs = isset($_GET['recs']) ? (int)$_GET['recs'] : 20; 
                $recs_opts = [10, 20, 50, 100, 500];
            ?>
            <select name="recs" class="form-control mr-3">
                <?php foreach($recs_opts as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo $recs == $r ? 'selected' : ''; ?>><?php echo $r . ' Baris'; ?></option>
                <?php endforeach; ?>
            </select>
            <?php echo __('Search'); ?>
            <input type="text" name="keywords" size="30" class="form-control col-3 ml-2" value="<?php echo htmlspecialchars($_GET['keywords'] ?? ''); ?>">
            <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default ml-2">
        </form>
    </div>
</div>
</div>
<?php
/* main content */

// create datagrid
$datagrid = new simbio_datagrid();

$table_spec = 'kuesioner';

$datagrid->setSQLColumn('id',
    'created_at AS \'' . __('Tanggal Dikirim') . '\'',
    'npm AS \'' . __('NPM / Identitas') . '\'',
    'nama AS \'' . __('Nama Lengkap') . '\'',
    'email AS \'' . __('Email') . '\'',
    'pertanyaan AS \'' . __('Detail Jawaban') . '\'',
    'id AS \'Aksi\'');

if (!function_exists('showJawaban')) {
    function showJawaban($db, $data) {
        $json = $data[5] ?? '';
        $arr = json_decode($json, true) ?? [];
        $out = '<ul style="margin:0; padding-left:15px; font-size:12px;">';
        foreach($arr as $j) {
            $t = htmlspecialchars($j['pertanyaan'] ?? '');
            $a = htmlspecialchars($j['jawaban'] ?? '');
            $out .= "<li><strong>{$t}</strong>: {$a}</li>";
        }
        $out .= '</ul>';
        return $out;
    }
}
$datagrid->modifyColumnContent(5, 'callback{showJawaban}');

if (!function_exists('showAksiMenuKuesioner')) {
    function showAksiMenuKuesioner($db, $data) {
        $id = $data[0];
        $qs = preg_replace('/&?action=[^&]*/', '', $_SERVER['QUERY_STRING'] ?? '');

        $html = '<div class="dropdown position-relative" onmouseenter="jQuery(this).find(\'.dropdown-menu\').addClass(\'show\');" onmouseleave="jQuery(this).find(\'.dropdown-menu\').removeClass(\'show\');">';
        $html .= '<button class="btn btn-sm btn-light border px-3" type="button" id="dropdownMenuButton'.$id.'" style="border-radius: 20px; cursor: pointer;">';
        $html .= '<i class="fa fa-ellipsis-v text-secondary"></i>';
        $html .= '</button>';
        $html .= '<div class="dropdown-menu dropdown-menu-right shadow-sm border-0" aria-labelledby="dropdownMenuButton'.$id.'" style="margin-top: 0px; top: 100%; z-index: 9999;">';
        $html .= '<a class="dropdown-item py-2" href="#" onclick="if(confirm(\'Kirim sertifikat digital ini secara otomatis via Email kepada responden?\')) { let b=jQuery(this); b.html(\'<i class=\\\'fa fa-spinner fa-spin text-success mr-2\\\'></i> Mengirim...\'); parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$qs.'\', {method: \'POST\', addData: \'sendSertifikat=true&kues_id='.$id.'&lastQueryStr=\'+encodeURIComponent(\''.$qs.'\')}); } return false;"><i class="fa fa-paper-plane text-success mr-2"></i> Kirim ke Email</a>';
        $html .= '<a class="dropdown-item py-2" href="'.$_SERVER['PHP_SELF'].'?'.$qs.'&action=print_sertifikat&kues_id='.$id.'" target="_blank"><i class="fa fa-print text-info mr-2"></i> Cetak PDF Sertifikat</a>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
$datagrid->modifyColumnContent(6, 'callback{showAksiMenuKuesioner}');

$datagrid->setSQLorder('created_at DESC');

// is there any search
$criteria = "npm != 'setting'";
if (isset($_GET['keywords']) AND $_GET['keywords']) {
   $keywords = $dbs->escape_string($_GET['keywords']);
   $criteria .= " AND (npm LIKE '%$keywords%' OR nama LIKE '%$keywords%' OR email LIKE '%$keywords%' OR pertanyaan LIKE '%$keywords%')";
}
$datagrid->setSQLCriteria($criteria);

// set table and table header attributes
$datagrid->table_attr = 'id="dataList" class="s-table table table-sm"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
$qs = preg_replace('/&?action=[^&]*/', '', $_SERVER['QUERY_STRING'] ?? '');
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'] . '?' . $qs;

// put the result into variables
$recs = isset($_GET['recs']) ? (int)$_GET['recs'] : 20;
if ($recs <= 0) $recs = 20;
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, $recs, ($can_read AND $can_write));

if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
    echo '<div class="infoBox">'.$msg.' : "'.htmlentities($_GET['keywords']).'"</div>';
}

echo '<div class="table-responsive" style="min-height: 400px; padding-bottom: 20px;">';
echo $datagrid_result;
echo '</div>';
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($('.uncheck-all').length > 0) {
        var btnDownloadHtml = '<input type="button" value="Download Sertifikat" class="s-btn btn btn-primary ml-2 btn-bulk-download" />';
        var btnEmailHtml = '<input type="button" value="Kirim Email Massal" class="s-btn btn btn-success ml-2 btn-bulk-email" />';
        
        
        $('.uncheck-all').after(btnEmailHtml).after(btnDownloadHtml); 
        
        $('.btn-bulk-download').click(function() {
            var selected = [];
            $('input[name="itemID[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            if (selected.length == 0) {
                alert('Centang terlebih dahulu baris partisipan / responden dari tabel sebelum mendownload tipe bulk!');
                return;
            }
            var qs = "<?php echo addslashes($qs); ?>";
            var actionUrl = "<?php echo $_SERVER['PHP_SELF']; ?>?action=bulk_print_sertifikat&" + qs + "&kues_ids=" + selected.join(',');
            
            window.open(actionUrl, '_blank');
        });
        
        $('.btn-bulk-email').click(function() {
            var selected = [];
            $('input[name="itemID[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            if (selected.length == 0) {
                alert('Centang terlebih dahulu baris tabel sebelum mengirim email massal!');
                return;
            }
            if(confirm('Teruskan otomatis Sertifikat Digital ke ' + selected.length + ' responden terpilih via Email? (Proses ini mungkin memerlukan waktu beberapa saat)')) {
                $('.btn-bulk-email').val('Sedang mengirim...').prop('disabled', true);
                var qs = "<?php echo addslashes($qs); ?>";
                parent.jQuery('#mainContent').simbioAJAX('<?php echo $_SERVER['PHP_SELF']; ?>?' + qs, {
                    method: 'POST', 
                    addData: 'sendBulkSertifikat=true&kues_ids=' + selected.join(',') + '&lastQueryStr=' + encodeURIComponent(qs)
                });
            }
        });
    }
});
</script>
