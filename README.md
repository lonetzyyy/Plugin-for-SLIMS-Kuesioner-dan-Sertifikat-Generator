# Plugin SLiMS: Kuesioner & Sertifikat Generator

Plugin ini dirancang untuk otomasi pengumpulan umpan balik (kuesioner) dari pemustaka sekaligus memberikan apresiasi instan berupa **e-Certificate** yang dikirimkan langsung ke email responden.

## ✨ Fitur Utama

- **Pembangun Kuesioner Dinamis**: Buat pertanyaan dengan tipe Teks, Dropdown, atau Skala (Radio Button) sesuai kebutuhan.
- **Otomasi Sertifikat PDF**: Menghasilkan sertifikat dalam format PDF secara otomatis dengan data nama responden.
- **Kustomisasi Layout Sertifikat**: Atur posisi teks (X/Y), ukuran font, warna, dan jenis font melalui menu pengaturan.
- **Import Font Kustom (.ttf)**: Unggah file font `.ttf` favorit Anda, dan sistem akan mengonversinya secara otomatis untuk digunakan pada sertifikat.
- **Pengiriman Email Massal**: Kirim sertifikat ke puluhan hingga ratusan responden sekaligus dalam satu kali klik.
- **Manajemen Data Responden**: Lihat, edit, dan hapus data responden melalui dashboard admin yang responsif (AJAX-based).
- **Branding Flyer**: Tambahkan banner flyer pada halaman formulir untuk mempercantik tampilan dan memberikan informasi tambahan.

## 🚀 Instalasi

1. **Unduh Plugin**: Salin folder `kuesioner_sertifikat_generator` ke dalam direktori `plugins/` pada instalasi SLiMS Anda.
3. **Aktifkan Modul**: Masuk ke Admin SLiMS > System > Plugin, lalu pastikan plugin "Kuesioner Sertifikat Generator" dalam kondisi aktif.
4. **Konfigurasi Akses**: Atur hak akses privileges bagi pustakawan yang akan mengelola kuesioner pada menu User Group.

## 🛠️ Cara Penggunaan

1. **Pengaturan Awal**: 
   - Masuk ke menu **Kuesioner Sertifikat > Pengaturan Kuesioner**.
   - Masukkan judul kuesioner dan unggah banner flyer.
   - Susun daftar pertanyaan yang ingin diajukan.
2. **Desain Sertifikat**:
   - Unggah gambar template sertifikat (JPG/PNG/WEBP).
   - Atur koordinat posisi nama peserta, ukuran, warna, dan jenis font.
   - Gunakan tombol **Preview PDF** untuk memastikan tata letak sudah pas.
3. **Publikasi**: 
   - Bagikan link kuesioner kepada pemustaka.
4. **Kirim Sertifikat**: 
   - Masuk ke menu **Laporan Kuesioner**.
   - Pilih responden (atau centang semua), lalu klik tombol **Kirim Email Massal**.
5, **Akses Kuesioner Untuk Pengunjung**:
   - index.php?p=kuesioner

## 📖 Persyaratan Sistem

- SLiMS 9 Bulian atau versi terbaru.
- PHP 7.4 atau lebih tinggi.
- Ekstensi PHP: `GD`, `mysqli`, `zip`, `mbstring`.
- Library FPDF (sudah disertakan dalam paket plugin).

## 📄 Lisensi

Plugin ini didistribusikan di bawah lisensi GNU General Public License v3.0.

---
Dikembangkan untuk memajukan otomasi layanan perpustakaan digital.
