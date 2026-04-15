<?php
use SLiMS\DB;

$db = DB::getInstance();

// 1. Ambil pengaturan / pertanyaan dari database (yang disimpan via settings_kuesioner)
$stmt = $db->query("SELECT judul, pertanyaan, flyer FROM kuesioner WHERE npm = 'setting' LIMIT 1");
$setting_row = $stmt->fetch(PDO::FETCH_ASSOC);

$kuesioner_judul = $setting_row['judul'] ?? 'Kuesioner Pengunjung';
$kuesioner_flyer = $setting_row['flyer'] ?? '';
$daftar_pertanyaan = [];
if ($setting_row && !empty($setting_row['pertanyaan'])) {
    $daftar_pertanyaan = json_decode($setting_row['pertanyaan'], true) ?? [];
}

if (empty($daftar_pertanyaan)) {
    // Default fallback jika belum di atur
    $daftar_pertanyaan = ['Tuliskan pertanyaan atau feedback Anda'];
}

// 2. Proses simpan saat form pengunjung dikirim
if (isset($_POST['simpan'])) {
    

    // Ambil semua jawaban dan kombinasikan dengan pertanyaannya
    $jawaban_array = $_POST['jawaban'] ?? [];
    
    $hasil_kuesioner = [];
    foreach ($daftar_pertanyaan as $index => $item) {
        $tanya = is_array($item) ? ($item['pertanyaan'] ?? '') : $item;
        $hasil_kuesioner[] = [
            'pertanyaan' => $tanya,
            'jawaban' => $jawaban_array[$index] ?? ''
        ];
    }
    
    $statement = $db->prepare(<<<SQL
    insert into `kuesioner`
        set 
            `npm` = ?,
            `nama` = ?,
            `email` = ?,
            `judul` = ?,
            `pertanyaan` = ?,
            `created_at` = now(),
            `updated_at` = now()
    SQL);
    
    $statement->execute([
        $_POST['npm'],
        $_POST['nama'],
        $_POST['email'] ?? '',
        $kuesioner_judul,
        json_encode($hasil_kuesioner), // Simpan dalam format JSON yang berisi pertanyaan beserta jawabannya
    ]);
    
    // Tampilkan pesan sukses
    echo "<script>alert('Terima kasih! Kuesioner Anda berhasil dikirim.'); window.location.href = '';</script>";
}
?>

<!-- Flyer Full Width Banner -->
<?php if (!empty($kuesioner_flyer)): ?>
<div class="container-fluid p-0 mb-4">
    <div class="w-100 shadow-sm border-bottom text-center mb-4" style="max-height: 400px; overflow: hidden;">
        <img src="<?= SWB ?>plugins/kuesioner_sertifikat_generator/assets/<?= htmlspecialchars($kuesioner_flyer ?? '') ?>" alt="Flyer Kuesioner" class="img-fluid w-100" style="object-fit: cover; object-position: center;">
    </div>
</div>
<?php endif; ?>

<!-- Main Form Container -->
<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h3 font-weight-bold text-dark mb-2"><?= htmlspecialchars($kuesioner_judul ?? '') ?></h2>
                    <p class="text-muted mb-4">Silakan isi form kuesioner di bawah ini dengan lengkap dan benar.</p>
                    
                    <hr class="mb-4">

                    <form action="" method="POST">
                        <div class="form-group mb-4">
                            <label for="npm" class="font-weight-bold"><?php echo __('No Identitas (NPM, NIDN dll.)'); ?></label>
                            <input type="text" name="npm" id="npm" class="form-control form-control-lg bg-light border-0" style="border-radius: 10px;" placeholder="Contoh: 12345678" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="nama" class="font-weight-bold"><?php echo __('Nama Lengkap'); ?></label>
                            <input type="text" name="nama" id="nama" class="form-control form-control-lg bg-light border-0" style="border-radius: 10px;" placeholder="Masukkan nama lengkap" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="email" class="font-weight-bold"><?php echo __('Alamat Email'); ?></label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg bg-light border-0" style="border-radius: 10px;" placeholder="Masukkan alamat email aktif" required>
                        </div>
                        
                        <div class="mt-5 mb-4">
                            <h4 class="h5 font-weight-bold text-primary mb-3 border-bottom pb-2">Daftar Pertanyaan</h4>
                            
                            <?php 
                            foreach ($daftar_pertanyaan as $index => $item): 
                                $tanya = is_array($item) ? ($item['pertanyaan'] ?? '') : $item;
                                $tipe = is_array($item) ? ($item['tipe'] ?? 'text') : 'text';
                                // Pilihan di-explode dari comma string
                                $pilihan = is_array($item) ? array_filter(array_map('trim', explode(',', $item['pilihan'] ?? ''))) : [];
                            ?>
                            <div class="form-group mb-4 p-3 bg-light rounded" style="border-radius: 12px;">
                                <label class="font-weight-bold d-block mb-3"><?= ($index + 1) . '. ' . htmlspecialchars($tanya ?? '') ?></label>
                                
                                <?php if ($tipe === 'dropdown' && count($pilihan) > 0): ?>
                                    <select name="jawaban[<?= $index ?>]" class="form-control form-control-lg border-0 bg-white" style="border-radius: 8px;" required>
                                        <option value="" disabled selected>-- Pilih Jawaban --</option>
                                        <?php foreach ($pilihan as $opt): ?>
                                            <option value="<?= htmlspecialchars($opt ?? '') ?>"><?= htmlspecialchars($opt ?? '') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($tipe === 'radio' && count($pilihan) > 0): ?>
                                    <div class="d-flex flex-wrap">
                                        <?php foreach ($pilihan as $idx_opt => $opt): ?>
                                            <div class="custom-control custom-radio mr-4 mb-2">
                                                <input type="radio" id="opt_<?= $index ?>_<?= $idx_opt ?>" name="jawaban[<?= $index ?>]" value="<?= htmlspecialchars($opt ?? '') ?>" class="custom-control-input" required>
                                                <label class="custom-control-label font-weight-normal" for="opt_<?= $index ?>_<?= $idx_opt ?>"><?= htmlspecialchars($opt ?? '') ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <textarea name="jawaban[<?= $index ?>]" rows="3" class="form-control form-control-lg border-0 bg-white" style="border-radius: 8px;" placeholder="Tuliskan jawaban Anda..." required></textarea>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" name="simpan" class="btn btn-primary btn-lg btn-block font-weight-bold py-3 shadow-sm mt-4" style="border-radius: 12px;">
                            <i class="fa fa-paper-plane mr-2"></i> Kirim Kuesioner
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>