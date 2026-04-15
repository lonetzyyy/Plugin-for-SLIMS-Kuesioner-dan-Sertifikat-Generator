<?php
defined('INDEX_AUTH') OR die('Direct access not allowed!');

use SLiMS\DB;

require_once SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require_once SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';

$db = DB::getInstance();


// Ambil data pengaturan saat ini dari table kuesioner untuk mendapatkan flyer yg sudah ada
$stmt = $db->query("SELECT * FROM kuesioner WHERE npm = 'setting' LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$current_judul = $row['judul'] ?? '';
$current_flyer = $row['flyer'] ?? '';
$current_template_sertifikat = $row['template_sertifikat'] ?? '';
$config_sertifikat_raw = $row['config_sertifikat'] ?? '{}';
$config_sertifikat = json_decode($config_sertifikat_raw, true) ?? [];
$pos_x = $config_sertifikat['pos_x'] ?? '150';
$pos_y = $config_sertifikat['pos_y'] ?? '100';
$ukuran_font = $config_sertifikat['ukuran_font'] ?? '30';
$warna_teks = $config_sertifikat['warna_teks'] ?? '#000000';
$jenis_font = $config_sertifikat['jenis_font'] ?? 'Arial';

$current_pertanyaan = [];
if ($row && !empty($row['pertanyaan'])) {
    $current_pertanyaan = json_decode($row['pertanyaan'], true) ?? [];
}

if (empty($current_pertanyaan) || !is_array($current_pertanyaan)) {
    // Memastikan default berisi setidaknya 1 baris
    $current_pertanyaan = [
        ['pertanyaan' => '', 'tipe' => 'text', 'pilihan' => '']
    ]; 
}

// Handler untuk Preview Sertifikat
if (isset($_GET['action']) && $_GET['action'] == 'preview_cert') {
    $template_file = $row['template_sertifikat'] ?? '';
    // Gunakan config dari database
    $warna_hex = $config_sertifikat['warna_teks'] ?? '#000000';
    $warna_hex = ltrim($warna_hex, '#');
    $r_col = hexdec(substr($warna_hex, 0, 2));
    $g_col = hexdec(substr($warna_hex, 2, 2));
    $b_col = hexdec(substr($warna_hex, 4, 2));

    require_once __DIR__ . '/../assets/fpdf.php';
    $template_path = __DIR__ . '/../assets/' . $template_file;
    $has_template = (!empty($template_file) && file_exists($template_path));
    
    $pdf = new FPDF('L','mm','A4');
    $core_fonts = ['arial', 'helvetica', 'times', 'courier', 'symbol', 'zapfdingbats'];
    $current_font = $config_sertifikat['jenis_font'] ?? 'Arial';
    $current_style = 'B';
    if (!in_array(strtolower($current_font), $core_fonts)) {
        $pdf->AddFont($current_font, '', $current_font . '.php');
        $current_style = '';
    }
    $pdf->AddPage();
    if ($has_template) $pdf->Image($template_path, 0, 0, 297, 210);
    $pdf->SetFont($current_font, $current_style, (int)($config_sertifikat['ukuran_font'] ?? 64));
    $pdf->SetTextColor($r_col, $g_col, $b_col);
    $pdf->SetXY(0, (int)($config_sertifikat['pos_y'] ?? 100));
    $pdf->Cell(297, 10, 'NAMA PESERTA', 0, 0, 'C');
    
    ob_clean();
    $pdf->Output('I', 'Preview_Sertifikat.pdf');
    exit;
}

// Proses simpan data (ketika form disubmit)
if (isset($_POST['simpan_setting'])) {
    $judul_kuesioner = $_POST['judul_kuesioner'] ?? '';
    
    // Konfigurasi path penyimpanan
    $target_dir = __DIR__ . '/../assets/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $valid_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Logic untuk mekanisme Upload Flyer
    $flyer_filename = $current_flyer; // retain default/old
    if (isset($_POST['hapus_flyer']) && $_POST['hapus_flyer'] == '1') {
        $flyer_filename = '';
    }
    
    if (isset($_FILES['flyer']) && $_FILES['flyer']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['flyer']['name'], PATHINFO_EXTENSION);
        $filename = 'flyer_' . time() . '.' . strtolower($ext);
        $target_file = $target_dir . $filename;
        
        // Pindahkan file dan validasi hanya jika image
        if (in_array($_FILES['flyer']['type'], $valid_mime)) {
            if (move_uploaded_file($_FILES['flyer']['tmp_name'], $target_file)) {
                $flyer_filename = $filename;
            }
        }
    }
    
    // Logic untuk mekanisme Upload Template Sertifikat
    $template_sertifikat_filename = $current_template_sertifikat; // retain default/old
    if (isset($_POST['hapus_template_sertifikat']) && $_POST['hapus_template_sertifikat'] == '1') {
        $template_sertifikat_filename = '';
    }
    
    if (isset($_FILES['template_sertifikat']) && $_FILES['template_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['template_sertifikat']['name'], PATHINFO_EXTENSION);
        $filename = 'sertifikat_' . time() . '.' . strtolower($ext);
        $target_file = $target_dir . $filename;
        if (in_array($_FILES['template_sertifikat']['type'], $valid_mime)) {
            if (move_uploaded_file($_FILES['template_sertifikat']['tmp_name'], $target_file)) {
                $template_sertifikat_filename = $filename;
            }
        }
    }
    
    // Logic untuk mekanisme Import Font Baru (.ttf)
    if (isset($_FILES['new_font']) && $_FILES['new_font']['error'] === UPLOAD_ERR_OK) {
        $font_ext = strtolower(pathinfo($_FILES['new_font']['name'], PATHINFO_EXTENSION));
        if ($font_ext === 'ttf') {
            $font_target_dir = __DIR__ . '/../assets/font/';
            if (!is_dir($font_target_dir)) {
                mkdir($font_target_dir, 0755, true);
            }
            $font_filename = basename($_FILES['new_font']['name']);
            $font_tmp_file = $font_target_dir . $font_filename;
            
            if (move_uploaded_file($_FILES['new_font']['tmp_name'], $font_tmp_file)) {
                $font_makefont_script = $font_target_dir . 'makefont/makefont.php';
                if (file_exists($font_makefont_script)) {
                    $old_cwd = getcwd();
                    chdir($font_target_dir);
                    require_once 'makefont/makefont.php';
                    
                    // Gunakan output buffering agar pesan dari MakeFont tidak muncul di Master File
                    ob_start();
                    try {
                        MakeFont($font_filename, 'cp1252');
                    } catch (\Exception $e) {
                        // Gagal konversi
                    }
                    ob_end_clean();
                    
                    chdir($old_cwd);
                    // Hapus file .ttf asli setelah konversi untuk menjaga folder tetap bersih (hanya simpan .php dan .z)
                    if (file_exists($font_tmp_file)) @unlink($font_tmp_file); 
                }
            }
        }
    }
    
    // Konfigurasi Sertifikat ke JSON
    $s_conf = [
        'pos_x' => $_POST['pos_x'] ?? '150',
        'pos_y' => $_POST['pos_y'] ?? '100',
        'ukuran_font' => $_POST['ukuran_font'] ?? '64',
        'warna_teks' => $_POST['warna_teks'] ?? '#000000',
        'jenis_font' => $_POST['jenis_font'] ?? 'Arial'
    ];
    $config_sertifikat_json = json_encode($s_conf);

    // Ambil array data pertanyaan
    $pertanyaan_raw = $_POST['pertanyaan'] ?? [];
    $tipe_raw = $_POST['tipe'] ?? [];
    $pilihan_raw = $_POST['pilihan'] ?? [];
    
    $pertanyaan_array = [];
    foreach ($pertanyaan_raw as $index => $tanya) {
        if (trim($tanya) !== '') {
            $pertanyaan_array[] = [
                'pertanyaan' => trim($tanya),
                'tipe' => $tipe_raw[$index] ?? 'text',
                'pilihan' => $pilihan_raw[$index] ?? ''
            ];
        }
    }
    
    // Convert ke JSON
    $pertanyaan_json = json_encode(array_values($pertanyaan_array));
    
    // Simpan ke DB
    if ($row) {
        $update = $db->prepare("UPDATE kuesioner SET judul = ?, pertanyaan = ?, flyer = ?, template_sertifikat = ?, config_sertifikat = ?, updated_at = now() WHERE npm = 'setting'");
        $update->execute([$judul_kuesioner, $pertanyaan_json, $flyer_filename, $template_sertifikat_filename, $config_sertifikat_json]);
    } else {
        $insert = $db->prepare("INSERT INTO kuesioner (npm, nama, judul, pertanyaan, flyer, template_sertifikat, config_sertifikat, created_at, updated_at) VALUES ('setting', 'setting', ?, ?, ?, ?, ?, now(), now())");
        $insert->execute([$judul_kuesioner, $pertanyaan_json, $flyer_filename, $template_sertifikat_filename, $config_sertifikat_json]);
    }
    
    // Refresh dan beri notifikasi SLiMS
    // Menggunakan AJAX handler milik SLiMS untuk me-refresh dengan aman
    echo '<script type="text/javascript">';
    echo 'parent.toastr.success("Pengaturan Kuesioner Berhasil Disimpan!");';
    echo 'parent.jQuery(\'#mainContent\').simbioAJAX(\'' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '\');';
    echo '</script>';
    exit();
}
?>

<div class="menuBox">
    <div class="menuBoxInner masterFileIcon">
        <div class="per_title">
            <h2><?= __('Pengaturan Kuesioner') ?></h2>
        </div>
    </div>
</div>



<?php


// create new instance
$form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'], 'post');
$form->form_enctype = 'multipart/form-data'; // penting untuk upload gambar
$form->submit_button_attr = 'name="simpan_setting" value="'.__('Simpan Pengaturan').'" class="s-btn btn btn-primary"';

// form table attributes
$form->table_attr = 'id="dataList" class="s-table table"';
$form->table_header_attr = 'class="alterCell font-weight-bold"';
$form->table_content_attr = 'class="alterCell2"';

// Form Element: Judul
$form->addTextField('text', 'judul_kuesioner', __('Judul Kuesioner').'*', $current_judul, ' required class="form-control col-6"');

// Form Element: Flyer Upload
$str_flyer = '<div class="mb-2">';
if (!empty($current_flyer)) {
    $str_flyer .= '<div class="mb-3"><img src="'.SWB.'plugins/kuesioner_sertifikat_generator/assets/'.htmlspecialchars($current_flyer).'" style="max-height:150px; border-radius:5px; border:2px solid #ddd;" /></div>';
    $str_flyer .= '<div class="mb-3"><label><input type="checkbox" name="hapus_flyer" value="1"> Hapus Flyer Saat Ini</label></div>';
}
$str_flyer .= '<input type="file" name="flyer" class="form-control col-6" accept="image/jpeg, image/png, image/gif, image/webp">';
$str_flyer .= '<small class="form-text text-muted">Format yang didukung: JPG, PNG, GIF, WEBP</small>';
$str_flyer .= '</div>';
$form->addAnything(__('Gambar Flyer Banner'), $str_flyer);

// Form Element: Certificate Upload & Config
$str_sert = '<div class="mb-2">';
if (!empty($current_template_sertifikat)) {
    $str_sert .= '<div class="mb-3"><img src="'.SWB.'plugins/kuesioner_sertifikat_generator/assets/'.htmlspecialchars($current_template_sertifikat).'" style="max-height:150px; border-radius:5px; border:2px solid #ddd;" /></div>';
    $str_sert .= '<div class="mb-3"><label><input type="checkbox" name="hapus_template_sertifikat" value="1"> Hapus Template Saat Ini</label></div>';
}
$str_sert .= '<div class="input-group col-10 p-0">';
$str_sert .= '<input type="file" name="template_sertifikat" class="form-control" accept="image/jpeg, image/png, image/webp">';
$str_sert .= '<div class="input-group-append">';
$str_sert .= '<a href="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=preview_cert" target="_blank" class="btn btn-info" title="Lihat Preview PDF"><i class="fa fa-file-pdf-o"></i> Preview PDF</a>';
$str_sert .= '</div>';
$str_sert .= '</div>';
$str_sert .= '<small class="form-text text-muted">Format yang didukung: JPG, PNG, WEBP (Ukuran A4 Landscape direkomendasikan)</small>';
$str_sert .= '</div>';

$str_sert .= '<div class="mt-3 p-3" style="background:#f9f9f9; border-radius:5px; border:1px solid #eee;">';
$str_sert .= '<strong>Pengaturan Cetak Nama Peserta</strong><br/>';
$str_sert .= '<div class="form-row mt-2">';
$str_sert .= '<div class="col-2"><label>Posisi X / Kiri</label><input type="number" name="pos_x" class="form-control" value="'.htmlspecialchars($pos_x).'"></div>';
$str_sert .= '<div class="col-2"><label>Posisi Y / Atas</label><input type="number" name="pos_y" class="form-control" value="'.htmlspecialchars($pos_y).'"></div>';
$str_sert .= '<div class="col-2"><label>Ukuran Font</label><input type="number" name="ukuran_font" class="form-control" value="'.htmlspecialchars($ukuran_font).'"></div>';

$str_sert .= '<div class="col-3"><label>Jenis Font</label>';
$str_sert .= '<input type="file" id="new_font_upload" name="new_font" style="display:none;" accept=".ttf" onchange="if(this.value) { $(\'button[name=simpan_setting]\').click(); }">';
$str_sert .= '<div class="input-group"><select name="jenis_font" class="form-control">';
// Mengambil daftar font secara dinamis dari folder assets/font
$font_dir = __DIR__ . '/../assets/font/';
$found_fonts = false;
if (is_dir($font_dir)) {
    $font_files = scandir($font_dir);
    foreach ($font_files as $font_file) {
        if (pathinfo($font_file, PATHINFO_EXTENSION) === 'php') {
            $f_base = pathinfo($font_file, PATHINFO_FILENAME);
            $str_sert .= '<option value="' . $f_base . '" ' . ($jenis_font == $f_base ? 'selected' : '') . '>' . ucfirst($f_base) . '</option>';
            $found_fonts = true;
        }
    }
}

// Jika folder kosong atau tidak ditemukan, gunakan fallback standar TCPDF
if (!$found_fonts) {
    foreach (['helvetica', 'times', 'courier'] as $fallback_font) {
        $str_sert .= '<option value="' . $fallback_font . '" ' . ($jenis_font == $fallback_font ? 'selected' : '') . '>' . ucfirst($fallback_font) . '</option>';
    }
}
$str_sert .= '</select>';
$str_sert .= '<div class="input-group-append"><button type="button" class="btn btn-outline-secondary" onclick="$(\'#new_font_upload\').click();" title="Import Font Baru (.ttf)"><i class="fa fa-upload"></i></button></div>';
$str_sert .= '</div></div>';

$str_sert .= '<div class="col-3"><label>Warna Teks</label><input type="color" name="warna_teks" class="form-control" value="'.htmlspecialchars($warna_teks).'" style="height:38px;"></div>';
$str_sert .= '</div>';
$str_sert .= '<small class="form-text text-muted mt-2">Ubah koordinat X (mendatar) dan Y (menurun) untuk menyesuaikan ketepatan letak sablon NAMA di atas gambar sertifikat. Makin besar nilai X, teks makin ke kanan. Makin besar nilai Y, teks makin ke bawah.</small>';
$str_sert .= '</div>';

$form->addAnything(__('Template Sertifikat'), $str_sert);

// Form Element: Daftar Pertanyaan Dinamis
$str_input = '<div class="wrp"><div id="more"><button class="add_field_button btn btn-success s-margin__bottom-1 mb-2" type="button">'.__('+ Tambah Pertanyaan').'</button>';

foreach ($current_pertanyaan as $key => $item) {
    // Kompatibilitas dengan versi script array 1 dimensi lama
    if (!is_array($item)) {
        $item = ['pertanyaan' => $item, 'tipe' => 'text', 'pilihan' => ''];
    }
    
    $p = htmlspecialchars($item['pertanyaan'] ?? '');
    $t = $item['tipe'] ?? 'text';
    $opt = htmlspecialchars($item['pilihan'] ?? '');
    
    $selText = ($t === 'text') ? 'selected' : '';
    $selDrop = ($t === 'dropdown') ? 'selected' : '';
    $selRadio = ($t === 'radio') ? 'selected' : '';
    $disp = ($t === 'dropdown' || $t === 'radio') ? 'block' : 'none';
    
    $str_input .= '<div class="item" style="display:flex; flex-wrap:wrap; align-items:center; border:1px solid #ddd; padding:10px; margin-bottom:10px; background:#fafafa; border-radius:5px;">';
    $str_input .= '<input type="text" class="itemCode form-control col-4 mb-2 mr-2" name="pertanyaan[]" value="'.$p.'" placeholder="Tuliskan pertanyaan..." required/>';
    $str_input .= '<select name="tipe[]" class="form-control col-2 mb-2 mr-2 tipe-select" onchange="togglePilihan(this)"><option value="text" '.$selText.'>Text Input</option><option value="dropdown" '.$selDrop.'>Drop Down</option><option value="radio" '.$selRadio.'>Skala (Radio)</option></select>';
    $str_input .= '<input type="text" class="form-control col-3 mb-2 mr-2 input-pilihan" style="display:'.$disp.';" name="pilihan[]" value="'.$opt.'" placeholder="Opsi (pisahkan dengan koma: Baik,Cukup,Jelek)"/>';
    $str_input .= '<button type="button" class="remove_field btn btn-danger btn-sm mb-2 ml-2">'.__('Hapus').'</button>';
    $str_input .= '</div>';
}
$str_input .= '</div></div>';

$form->addAnything(__('Daftar Pertanyaan Kuesioner'), $str_input);

// print out the form object
echo $form->printOut();
?>

<script type="text/javascript">
function togglePilihan(sel) {
    var inputPilihan = $(sel).siblings('.input-pilihan');
    if (sel.value === 'dropdown' || sel.value === 'radio') {
        inputPilihan.show().attr("required", "required");
    } else {
        inputPilihan.hide().removeAttr("required");
    }
}
$(document).ready(function() {
    $(".add_field_button").click(function(e){ 
        var tpl = '<div class="item" style="display:flex; flex-wrap:wrap; align-items:center; border:1px solid #ddd; padding:10px; margin-bottom:10px; background:#fafafa; border-radius:5px;">' +
        '<input type="text" class="itemCode form-control col-4 mb-2 mr-2" name="pertanyaan[]" placeholder="Tuliskan pertanyaan..." required/>' +
        '<select name="tipe[]" class="form-control col-2 mb-2 mr-2 tipe-select" onchange="togglePilihan(this)"><option value="text">Text Input</option><option value="dropdown">Drop Down</option><option value="radio">Skala (Radio)</option></select>' +
        '<input type="text" class="form-control col-3 mb-2 mr-2 input-pilihan" style="display:none;" name="pilihan[]" placeholder="Opsi (pisahkan dengan koma: Baik,Cukup,Jelek)"/>' +
        '<button type="button" class="remove_field btn btn-danger btn-sm mb-2 ml-2"><?= __('Hapus')?></button>' +
        '</div>';
        $("#more").append(tpl);
    }); 
    $(".wrp").on("click",".remove_field", function(e){ 
        if ($(".itemCode").length > 1) {
            $(this).closest('.item').remove(); 
        } else {
            alert('<?= __('Harus ada minimal 1 pertanyaan!') ?>');
        }
    });
});
</script>
