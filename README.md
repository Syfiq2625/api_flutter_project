# ⚙️ Manajemen Lab Komputer - Backend API

REST API untuk Sistem Manajemen Lab Komputer. Proyek ini dibangun murni menggunakan **PHP Native dan PDO** tanpa menggunakan *framework* tambahan, dirancang khusus untuk berjalan di lingkungan server lokal (Laragon).

Repositori Frontend (Flutter): [flutter_project](https://github.com/Syfiq2625/flutter_project)

## ✨ Fitur & Aturan Bisnis (*Business Logic*)

- **Validasi Stok Server-Side:** API secara otomatis menolak transaksi dan mengembalikan *HTTP Error* jika jumlah peminjaman melebihi sisa stok riil yang ada di sistem.
- **Kalkulasi Status Dinamis (ADR-002):** Status alat (Tersedia, Dipinjam, Rusak) tidak disimpan mentah sebagai teks di dalam tabel database. Status ini **dihitung secara dinamis** berdasarkan kolom kondisi barang dan agregasi peminjaman aktif. Pendekatan ini menghilangkan risiko anomali atau data "basi".
- **Keamanan Lapis Pertama:** Seluruh komunikasi ke database MySQL diwajibkan melewati *Prepared Statements* (PDO) untuk memblokir risiko SQL Injection.
- **CORS Support:** Setiap *endpoint* secara mandiri menangani *header* CORS dan permintaan *preflight* (`OPTIONS`) yang datang dari Flutter Web.

## 🛠️ Tech Stack

- **Bahasa:** PHP 8+
- **Database:** MySQL
- **Driver:** PDO (PHP Data Objects)
- **Environment:** Laragon (Localhost - Apache/Nginx)

## 🚀 Cara Instalasi & Menjalankan (via Laragon)

1. Buka terminal, masuk ke direktori `www` di dalam Laragon, lalu *clone* repositori ini dengan nama folder yang spesifik (`lab_api`):
   ```bash
   cd C:\laragon\www\
   git clone [https://github.com/USERNAME_ANDA/api_flutter_project.git](https://github.com/Syfiq2625/api_flutter_project.git) lab_api
   ```
2. Nyalakan layanan **Apache** dan **MySQL** melalui panel kontrol Laragon.
3. Buka pengelola Database Anda (HeidiSQL, DBeaver, atau phpMyAdmin).
4. Buat database baru bernama `lab_komputer`.
5. Eksekusi (*import*) file `database.sql` yang berada di dalam repositori ini untuk membangun skema tabel dan memasukkan data *dummy* awal.
6. API siap melayani *request* dari *Frontend* di alamat: `http://localhost/lab_api/`

## 📡 Dokumentasi Endpoint

Format respons yang digunakan selalu seragam: `{ "success": boolean, "data": array|null, "message": string }`

| Metode HTTP | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/tools.php` | Mengambil seluruh daftar alat (Mendukung query `?search=` dan `?filter=`) |
| `GET` | `/loans.php` | Mengambil daftar riwayat peminjaman beserta evaluasi tenggat (*is_late*) |
| `POST` | `/loans.php` | Membuat transaksi peminjaman baru beserta validasi ketersediaan stok |
| `PUT` | `/loans.php?id={id}` | Memproses pengembalian alat (Mencatat `return_date`) |
| `GET` | `/summary.php` | Mengambil data agregat ringan (Total, Dipinjam, Rusak) untuk UI Dashboard |