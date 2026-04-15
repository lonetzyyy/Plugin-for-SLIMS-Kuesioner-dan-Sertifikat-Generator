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
<div class="w-full shadow-sm border-b border-gray-200 mb-6 sm:mb-10 text-center">
    <img src="<?= SWB ?>plugins/kuesioner_sertifikat_generator/assets/<?= htmlspecialchars($kuesioner_flyer) ?>" alt="Flyer Kuesioner" class="w-full h-48 sm:h-80 md:h-[400px] object-cover object-center">
</div>
<?php endif; ?>

<!-- Main Form Container -->
<div class="w-full max-w-4xl mx-auto pb-8 sm:pb-12 px-4 sm:px-6">
    <div class="w-full">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($kuesioner_judul) ?></h2>
        <p class="text-sm sm:text-base text-gray-500 mb-6 sm:mb-8">Silakan isi form kuesioner di bawah ini dengan lengkap dan benar.</p>
        
        <form action="" method="POST">
                <div class="mb-4 sm:mb-5">
                    <label for="npm" class="block text-sm font-bold text-gray-700 mb-2">No Identitas (NPM,NIDN dll.)</label>
                    <input type="text" name="npm" id="npm" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-3 sm:px-4 py-3 text-sm focus:border-brand-blue focus:ring-0 outline-none transition-all font-bold placeholder:font-normal placeholder-gray-400" placeholder="Contoh: 12345678" required>
                </div>
                
                <div class="mb-4 sm:mb-5">
                    <label for="nama" class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-3 sm:px-4 py-3 text-sm focus:border-brand-blue focus:ring-0 outline-none transition-all placeholder-gray-400" placeholder="Masukkan nama lengkap" required>
                </div>
                
                <div class="mb-4 sm:mb-5">
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Alamat Email</label>
                    <input type="email" name="email" id="email" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-3 sm:px-4 py-3 text-sm focus:border-brand-blue focus:ring-0 outline-none transition-all placeholder-gray-400" placeholder="Masukkan alamat email aktif" required>
                </div>
                
                <div class="mt-6 sm:mt-8 mb-4 sm:mb-6">
                    <h3 class="font-bold text-base sm:text-lg text-brand-blue mb-4 border-b border-gray-100 pb-2">Daftar Pertanyaan</h3>
                    
                    <?php 
                    foreach ($daftar_pertanyaan as $index => $item): 
                        $tanya = is_array($item) ? ($item['pertanyaan'] ?? '') : $item;
                        $tipe = is_array($item) ? ($item['tipe'] ?? 'text') : 'text';
                        // Pilihan di-explode dari comma string
                        $pilihan = is_array($item) ? array_filter(array_map('trim', explode(',', $item['pilihan'] ?? ''))) : [];
                    ?>
                    <div class="mb-5 sm:mb-6">
                        <label class="block text-sm sm:text-base font-bold text-gray-700 mb-2 sm:mb-3"><?= ($index + 1) . '. ' . htmlspecialchars($tanya) ?></label>
                        
                        <?php if ($tipe === 'dropdown' && count($pilihan) > 0): ?>
                            <div class="relative">
                                <select name="jawaban[<?= $index ?>]" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-3 sm:px-4 py-3 text-sm focus:border-brand-blue focus:ring-0 outline-none transition-all text-gray-900 appearance-none" required>
                                    <option value="" disabled selected>-- Pilih Jawaban --</option>
                                    <?php foreach ($pilihan as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        <?php elseif ($tipe === 'radio' && count($pilihan) > 0): ?>
                            <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 sm:gap-4 mt-2">
                                <?php foreach ($pilihan as $idx_opt => $opt): ?>
                                    <label class="flex items-center space-x-3 cursor-pointer group bg-gray-50 border border-gray-100 sm:py-2 sm:px-4 p-3 rounded-xl hover:bg-brand-blue/5 transition-colors">
                                        <input type="radio" name="jawaban[<?= $index ?>]" value="<?= htmlspecialchars($opt) ?>" class="w-4 h-4 sm:w-5 sm:h-5 text-brand-blue border-gray-300 focus:ring-brand-blue focus:ring-2 transition-all cursor-pointer" required>
                                        <span class="text-sm text-gray-700 font-medium group-hover:text-brand-blue transition-colors"><?= htmlspecialchars($opt) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <textarea name="jawaban[<?= $index ?>]" rows="3" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-3 sm:px-4 py-3 text-sm focus:border-brand-blue focus:ring-0 outline-none transition-all placeholder-gray-400" placeholder="Tuliskan jawaban Anda..." required></textarea>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="simpan" class="w-full mt-2 sm:mt-4 bg-brand-blue hover:bg-brand-blue/90 text-white font-bold py-3 sm:py-4 rounded-xl transition-all shadow-lg shadow-brand-blue/20 flex items-center justify-center gap-3 active:scale-[0.98]">
                    Kirim Kuesioner
                </button>
            </form>
        </div>
</div>