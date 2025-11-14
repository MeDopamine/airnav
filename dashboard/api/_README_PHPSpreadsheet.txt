# Cara Export Excel (XLSX) di PHP (XAMPP)

Agar fitur export Excel (.xlsx) berjalan, Anda perlu menginstall library PHPSpreadsheet.

## Langkah Instalasi (sekali saja)
1. Pastikan Composer sudah terinstall di Windows Anda. Jika belum, download dari https://getcomposer.org/download/
2. Buka Command Prompt/PowerShell di folder `c:\xampp\htdocs\airnav\dashboard\api` (atau root project).
3. Jalankan perintah berikut:

```
composer require phpoffice/phpspreadsheet
```

4. Setelah selesai, akan muncul folder `vendor/` dan file `vendor/autoload.php`.

Jika sudah, endpoint export Excel akan berjalan otomatis.

---
Jika Anda butuh bantuan instalasi Composer, silakan tanyakan.
