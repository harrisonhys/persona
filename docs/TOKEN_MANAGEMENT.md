# Token Management Guide

**Proyek:** Laravel GraphQL Service  
**Tujuan:** Panduan lengkap untuk mengelola API tokens dengan mudah dan aman

---

## 📋 Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Instalasi & Setup](#instalasi--setup)
3. [Menggunakan Artisan Commands](#menggunakan-artisan-commands)
4. [Best Practices](#best-practices)
5. [Troubleshooting](#troubleshooting)

---

## 🎯 Pengenalan

Sistem token management ini menyediakan cara yang mudah dan aman untuk mengelola API tokens, terutama untuk integrasi n8n. Fitur utama:

- ✅ Generate token dengan metadata
- ✅ List semua token aktif dengan status
- ✅ Revoke token dengan aman
- ✅ Rotate token (generate baru + revoke lama)
- ✅ Track penggunaan token (last used timestamp)
- ✅ Warning untuk token yang akan expired
- ✅ Cleanup otomatis untuk token expired

---

## 🚀 Instalasi & Setup

### 1. Jalankan Migration

Migration sudah dijalankan untuk menambahkan kolom metadata ke tabel `personal_access_tokens`:

```bash
php artisan migrate
```

**Kolom yang ditambahkan:**
- `created_by` - ID user yang membuat token
- `metadata` - JSON field untuk informasi tambahan
- `expires_at` - Sudah ada (dari Sanctum)

### 2. Middleware Sudah Terdaftar

Middleware `TrackTokenUsage` sudah ditambahkan ke `config/lighthouse.php` untuk otomatis track penggunaan token.

---

## 💻 Menggunakan Artisan Commands

### Command Utama

```bash
php artisan token:manage {action}
```

**Actions yang tersedia:**
- `generate` - Generate token baru
- `list` - List semua token
- `revoke` - Revoke token
- `rotate` - Rotate token (buat baru + hapus lama)
- `info` - Detail informasi token
- `cleanup` - Cleanup token expired dan unused

---

### 1. Generate Token Baru

#### Interactive Mode (Recommended)

```bash
php artisan token:manage generate
```

Anda akan ditanya:
1. **User email atau ID** - User pemilik token
2. **Token name** - Nama untuk identifikasi (default: `api-token-YYYYMMDD`)
3. **Abilities** - Permission token (default: `*` = all)
4. **Expiration** - Berapa hari token valid (opsional)
5. **Metadata** - Purpose, environment, dll (opsional)

**Contoh output:**

```
🔐 Generate New API Token

User email or ID:
> n8n@bot.com

Token name [api-token-20260203]:
> n8n-production

Grant all abilities? (yes/no) [yes]:
> yes

Set expiration date? (yes/no) [no]:
> yes

Expires in how many days? [90]:
> 90

Add metadata? (yes/no) [no]:
> yes

Purpose/Description:
> n8n automation for production

Environment [production]:
> production

Creating token...

✅ Token created successfully!

⚠️  IMPORTANT: Copy this token now. It will not be shown again!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2|xYz123AbC456DeF789GhI012JkL345MnO678PqR901StU234VwX567YzA890
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

┌──────────┬────────────────────────────────┐
│ Property │ Value                          │
├──────────┼────────────────────────────────┤
│ Token ID │ 2                              │
│ Name     │ n8n-production                 │
│ User     │ n8n@bot.com                    │
│ Abilities│ *                              │
│ Expires  │ 2026-05-04 12:30:00            │
│ Created  │ 2026-02-03 12:30:00            │
└──────────┴────────────────────────────────┘
```

#### Non-Interactive Mode

```bash
php artisan token:manage generate \
  --user=n8n@bot.com \
  --name=n8n-production \
  --abilities=* \
  --expires=90
```

---

### 2. List Tokens

#### List Active Tokens

```bash
php artisan token:manage list --user=n8n@bot.com
```

**Output:**

```
📋 List API Tokens

┌────┬────────────────┬─────────────────┬───────────┬─────────────────┬────────────┬────────────┐
│ ID │ Name           │ Status          │ Abilities │ Last Used       │ Expires    │ Created    │
├────┼────────────────┼─────────────────┼───────────┼─────────────────┼────────────┼────────────┤
│ 1  │ n8n-setup      │ 🟢 Active       │ *         │ 2 hours ago     │ Never      │ 2026-02-02 │
│ 2  │ n8n-production │ 🟡 Expiring Soon│ *         │ Never           │ 2026-02-10 │ 2026-02-03 │
└────┴────────────────┴─────────────────┴───────────┴─────────────────┴────────────┴────────────┘

Total: 2 token(s)
```

**Status Indicators:**
- 🟢 **Active** - Token aktif dan tidak akan expired
- 🟡 **Expiring Soon** - Token akan expired dalam 7 hari
- 🔴 **Expired** - Token sudah expired

#### Include Expired Tokens

```bash
php artisan token:manage list --user=n8n@bot.com --force
```

---

### 3. Revoke Token

#### Interactive Mode

```bash
php artisan token:manage revoke --user=n8n@bot.com
```

Anda akan melihat list token dan diminta memilih yang akan di-revoke.

#### By Token ID

```bash
php artisan token:manage revoke --user=n8n@bot.com --id=2
```

#### By Token Name

```bash
php artisan token:manage revoke --user=n8n@bot.com --name=n8n-setup
```

#### Skip Confirmation

```bash
php artisan token:manage revoke --user=n8n@bot.com --id=2 --force
```

**Output:**

```
🗑️  Revoke API Token

┌──────────┬────────────────────────┐
│ Property │ Value                  │
├──────────┼────────────────────────┤
│ ID       │ 2                      │
│ Name     │ n8n-production         │
│ Created  │ 2026-02-03 12:30:00    │
│ Last Used│ 2026-02-03 14:15:00    │
└──────────┴────────────────────────┘

Are you sure you want to revoke this token? (yes/no) [no]:
> yes

✅ Token revoked successfully.
```

---

### 4. Rotate Token

Rotate token sangat berguna untuk security best practice - generate token baru dan revoke yang lama.

#### Interactive Mode

```bash
php artisan token:manage rotate --user=n8n@bot.com
```

**Contoh:**

```
🔄 Rotate API Token

Old token name to rotate:
> n8n-production

New token name [n8n-production-20260203]:
> n8n-production-2026Q1

This will create a new token and revoke the old one. Continue? (yes/no) [yes]:
> yes

✅ Token rotated successfully!

⚠️  IMPORTANT: Update your applications with this new token!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
3|AbC123DeF456GhI789JkL012MnO345PqR678StU901VwX234YzA567BcD890
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

#### Non-Interactive Mode

```bash
php artisan token:manage rotate \
  --user=n8n@bot.com \
  --name=n8n-production \
  --force
```

> ⚠️ **PENTING:** Setelah rotate, segera update token di n8n dengan token baru!

---

### 5. Token Info

Lihat detail lengkap sebuah token:

```bash
php artisan token:manage info --id=1
```

**Output:**

```
ℹ️  Token Information

┌────────────┬────────────────────────────────┐
│ Property   │ Value                          │
├────────────┼────────────────────────────────┤
│ ID         │ 1                              │
│ Name       │ n8n-production                 │
│ User       │ n8n@bot.com                    │
│ Abilities  │ *                              │
│ Created    │ 2026-02-03 12:30:00            │
│ Last Used  │ 2026-02-03 14:15:00            │
│ Expires    │ 2026-05-04 12:30:00            │
│ Created By │ 1                              │
└────────────┴────────────────────────────────┘

Metadata:
┌─────────────┬────────────────────────────────┐
│ Key         │ Value                          │
├─────────────┼────────────────────────────────┤
│ purpose     │ n8n automation for production  │
│ environment │ production                     │
│ created_at  │ 2026-02-03T12:30:00+07:00      │
│ ip_address  │ 127.0.0.1                      │
│ user_agent  │ Symfony                        │
└─────────────┴────────────────────────────────┘
```

---

### 6. Cleanup Tokens

Cleanup otomatis untuk token expired dan unused:

```bash
php artisan token:manage cleanup
```

**Output:**

```
🧹 Cleanup Tokens

Checking for expired tokens...
Found 2 expired token(s).
Revoke all expired tokens? (yes/no) [yes]:
> yes

✅ Revoked 2 expired token(s).

Checking for unused tokens...
Found 1 token(s) not used in 30+ days.

┌────┬────────────┬────────────┐
│ ID │ Name       │ Last Used  │
├────┼────────────┼────────────┤
│ 5  │ old-token  │ 2025-12-01 │
└────┴────────────┴────────────┘

Consider revoking unused tokens manually with: php artisan token:manage revoke

Checking for expiring tokens...
Found 1 token(s) expiring within 7 days.

┌────┬────────────────┬────────────┬──────────────┐
│ ID │ Name           │ Expires On │ Expires      │
├────┼────────────────┼────────────┼──────────────┤
│ 2  │ n8n-production │ 2026-02-10 │ in 7 days    │
└────┴────────────────┴────────────┴──────────────┘

Consider rotating these tokens with: php artisan token:manage rotate
```

---

## 🎯 Best Practices

### 1. Token Rotation Schedule

> [!IMPORTANT]
> Rotasi token secara berkala untuk keamanan maksimal

**Recommended Schedule:**
- **Production tokens:** Setiap 3-6 bulan
- **Development tokens:** Setiap 1-3 bulan
- **Testing tokens:** Setiap bulan

**Cara mudah:**

```bash
# Setiap quarter, rotate token production
php artisan token:manage rotate \
  --user=n8n@bot.com \
  --name=n8n-production \
  --force
```

### 2. Token Naming Convention

Gunakan naming convention yang jelas:

```
{service}-{environment}-{period}
```

**Contoh:**
- `n8n-production-2026Q1`
- `n8n-staging-202602`
- `api-client-dev-20260203`

### 3. Set Expiration

Selalu set expiration untuk token, kecuali untuk development:

```bash
# Production: 90 days
--expires=90

# Staging: 60 days
--expires=60

# Development: 30 days atau no expiration
--expires=30
```

### 4. Metadata untuk Tracking

Selalu tambahkan metadata untuk audit trail:

```json
{
  "purpose": "n8n automation for production",
  "environment": "production",
  "created_by_name": "Admin",
  "project": "persona-api"
}
```

### 5. Monitor Token Usage

Regularly check token usage:

```bash
# Weekly check
php artisan token:manage list --user=n8n@bot.com

# Monthly cleanup
php artisan token:manage cleanup
```

---

## 🔧 Troubleshooting

### Token Tidak Berfungsi Setelah Rotate

**Problem:** n8n tidak bisa akses API setelah rotate token

**Solution:**
1. Pastikan token baru sudah di-copy dengan benar
2. Update token di n8n HTTP Request node
3. Test dengan curl:
   ```bash
   curl -X POST http://localhost:8000/graphql \
     -H "Authorization: Bearer {NEW_TOKEN}" \
     -H "Content-Type: application/json" \
     -d '{"query": "{ __typename }"}'
   ```

### Token Expired Tapi Masih Digunakan

**Problem:** Token sudah expired tapi masih bisa digunakan

**Solution:**
1. Jalankan cleanup:
   ```bash
   php artisan token:manage cleanup --force
   ```
2. Atau revoke manual:
   ```bash
   php artisan token:manage revoke --id={TOKEN_ID} --force
   ```

### Lupa Token yang Sudah Di-generate

**Problem:** Token plaintext hilang dan tidak bisa di-retrieve

**Solution:**
Token plaintext hanya ditampilkan sekali saat creation. Jika hilang:

1. Revoke token lama:
   ```bash
   php artisan token:manage revoke --name=token-lama
   ```

2. Generate token baru:
   ```bash
   php artisan token:manage generate --user=n8n@bot.com
   ```

### Last Used Tidak Update

**Problem:** Kolom `last_used_at` tidak update saat token digunakan

**Solution:**
1. Pastikan middleware `TrackTokenUsage` sudah terdaftar di `config/lighthouse.php`
2. Clear config cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
3. Restart server

---

## 📅 Scheduled Tasks (Opsional)

Untuk automation, tambahkan ke `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup expired tokens setiap hari
    $schedule->command('token:manage cleanup --force')
             ->daily()
             ->at('02:00');
    
    // Warning untuk expiring tokens setiap minggu
    $schedule->command('token:manage cleanup')
             ->weekly()
             ->mondays()
             ->at('09:00');
}
```

---

## 🔗 Referensi

- [Security Flow Documentation](file:///Users/macbook/Documents/PERSONAL/GRAPHQL-SRV/docs/SECURITY_FLOW.md)
- [Laravel Sanctum Documentation](https://laravel.com/docs/11.x/sanctum)
- [Implementation Plan](file:///Users/macbook/.gemini/antigravity/brain/3b3c85de-3776-4c9c-a0d0-2dbfe378e5e3/implementation_plan.md)

---

**Dibuat:** 2026-02-03  
**Versi:** 1.0  
**Maintainer:** Harrison
