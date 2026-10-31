# Changelog

Semua perubahan penting pada proyek **Absensi Anugerah Makmur** dicatat di file ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan penomoran versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

Nomor versi yang tampil di website ada di `version.php`. Ubah file itu bersamaan dengan catatan di sini setiap rilis baru.

## [1.1.0] - 2026-10-31

### Ditambahkan
- Gaji pokok per jam dan uang makan per hari untuk setiap pegawai di menu Pegawai, dengan input berformat rupiah.
- Laporan admin: kolom Gaji Pokok (total jam kali gaji per jam) dan Uang Makan, serta empat kartu ringkasan (Total Jam Kerja, Total Gaji Pokok, Total Uang Makan, Total Keseluruhan) yang mengikuti filter rentang tanggal dan pegawai.
- Rekap Excel: ringkasan gaji pokok per jam, hari absen, uang makan per hari, total gaji pokok, total uang makan, dan total diterima untuk tiap pegawai (memakai rumus Excel sehingga bisa disesuaikan).
- Riwayat absensi pegawai per bulan dengan pilihan bulan dan tahun, lengkap dengan ringkasan jumlah hari hadir dan total jam.
- Tampilan nomor versi di halaman login, halaman Absen, dan dashboard admin.
- `CHANGELOG.md` dan `.gitignore`.

### Diubah
- Unduhan CSV laporan kini memuat kolom Gaji Pokok dan Uang Makan.

## [0.9.2] - 2026-10-21

### Diperbaiki
- Absen gagal dengan error 403 di hosting gratis. Pemrosesan absen dipindah ke `kirim.php` dengan permintaan form biasa dan foto dikirim sebagai base64. Jika server menolak, pesan kini menampilkan kode HTTP-nya.

## [0.9.1] - 2026-10-17

### Diubah
- Tipografi tanpa tanda titik tengah (·): status GPS tampil bertingkat, status hari ini berupa label berwarna, judul dan tanggal dipisah baris.

## [0.9.0] - 2026-10-17

### Ditambahkan
- Tiga area toko (Toko A, B, C) dengan koordinat dan radius masing-masing. Pegawai otomatis absen di toko terdekat yang masih dalam radius.
- Kolom Toko di Dashboard dan Laporan, serta kolom Toko Masuk dan Toko Pulang di CSV.
- Peta di Laporan menampilkan semua toko aktif.

### Diubah
- Jam yang tampil di halaman Absen mengikuti jam server, bukan jam perangkat. Jam yang tersimpan di database sejak awal memakai jam server.
- Istilah "kantor" diganti menjadi "toko".

## [0.8.0] - 2026-10-08

### Ditambahkan
- Peta lokasi masuk dan pulang di Laporan (Leaflet dan OpenStreetMap), lengkap dengan area radius.

## [0.7.0] - 2026-10-07

### Ditambahkan
- Koreksi Absen oleh admin: mengisi jam pulang yang terlupa, dengan catatan alasan wajib dan penanda "dikoreksi".
- Tambah absen manual dan hapus data absen oleh admin.
- Label "Belum pulang" pada baris yang belum absen pulang.
- Backup database (.sql) dan backup database beserta foto (.zip) dari menu Laporan.

## [0.6.0] - 2026-10-07

### Ditambahkan
- Logo Anugerah Makmur di header dan halaman login, serta favicon dan ikon layar utama iPhone.

## [0.5.0] - 2026-10-07

### Diubah
- Rekap Excel kini memuat kolom Masuk, Keluar, dan Total untuk setiap pegawai.
- Pilihan bulan rekap memakai nama bulan bahasa Indonesia.

## [0.4.0] - 2026-10-07

### Ditambahkan
- Perhitungan total jam kerja dari jam masuk dan jam pulang, mendukung shift malam yang melewati tengah malam.
- Rekap bulanan Excel (.xlsx) berformat tanggal di baris dan nama pegawai di kolom.

### Dihapus
- Tampilan jarak, pengaturan jam masuk, dan status terlambat, karena aplikasi memakai sistem shift.

## [0.3.0] - 2026-10-06

### Ditambahkan
- Foto absen dikompres otomatis di perangkat sebelum dikirim, sehingga ukurannya kecil.

## [0.2.0] - 2026-10-06

### Diubah
- Tampilan dibuat lebih modern dengan warna kuning lembut.
- Di HP, menu berpindah menjadi tab sticky di bagian bawah layar.

## [0.1.0] - 2026-10-06

### Ditambahkan
- Rilis awal: login dengan peran admin dan staf, absen masuk dan pulang berbasis GPS dalam radius kantor, foto selfie opsional.
- Admin: dashboard, manajemen pegawai, pengaturan lokasi, laporan dengan filter, dan unduhan CSV.
- Tampilan Tailwind CSS bertema kuning lembut.
