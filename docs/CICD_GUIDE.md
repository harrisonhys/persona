# Panduan Deployment & CI/CD ke GitHub

Dokumen ini berisi langkah-langkah untuk:
1.  Menginisialisasi Git repository lokal.
2.  Membuat repository di GitHub.
3.  Menghubungkan & Push code.
4.  Melihat hasil CI (Automated Testing).

---

## 1. Persiapan GitHub

1.  Login ke [GitHub](https://github.com).
2.  Buat **New Repository**.
    *   **Repository Name**: `graphql-srv` (atau nama lain).
    *   **Visibility**: Private (disarankan) atau Public.
    *   **Initialize with README**: JANGAN dicentang (karena kita sudah punya code).

## 2. Inisialisasi Git Lokal

Buka terminal di folder project ini, lalu jalankan perintah berikut satu per satu:

```bash
# 1. Inisialisasi git (jika belum)
git init

# 2. Rename branch utama ke 'main'
git branch -M main

# 3. Masukkan semua file ke staging
git add .

# 4. Commit pertama
git commit -m "Initial commit with Laravel GraphQL setup"

# 5. Hubungkan ke GitHub (GANTI URL DENGAN REPO ANDA SENDIRI)
# Contoh: git remote add origin https://github.com/username/graphql-srv.git
git remote add origin <URL_REPO_GITHUB_ANDA>

# 6. Push kode ke GitHub
git push -u origin main
```

## 3. CI/CD (GitHub Actions)

Saya telah membuat file `.github/workflows/ci.yml`. 
Secara otomatis, setiap kali Anda melakukan `git push`, GitHub akan:
1.  Menyiapkan server Ubuntu virtual.
2.  Menginstall PHP & Composer.
3.  Menjalankan `php artisan test`.

Anda bisa memantau hasilnya di tab **Actions** di halaman repository GitHub Anda.

### Jika Ingin Deployment Otomatis (CD) ke Server VPS

Saya telah membuat file `.github/workflows/deploy.yml`. Untuk mengaktifkannya:

1.  Pastikan Anda bisa SSH ke server Anda.
2.  Clone repo ini di server Anda (misal di `/var/www/graphql-srv`).
3.  Buka **Settings > Secrets and variables > Actions** di GitHub Repository Anda.
4.  Tambahkan **New repository secret**:
    *   `VPS_HOST`: IP Address server Anda (contoh: `103.x.x.x`).
    *   `VPS_USERNAME`: Username SSH (contoh: `root` atau `deployer`).
    *   `VPS_SSH_KEY`: Private Key SSH (Isi dari file `id_rsa` atau `.pem`).
    *   `VPS_PORT`: (Opsional) Port SSH jika bukan 22.

Setelah ini diset, setiap kali Anda push ke `main`, GitHub akan otomatis mengupdate code di server!

## 4. Troubleshooting
Jika `git services` error atau `.env` ikut terupload:
*   File `.env` **TIDAK BOLEH** diupload ke GitHub (berisi password & key rahasia).
*   Pastikan `.gitignore` sudah berisi `.env` (Laravel default sudah aman).
