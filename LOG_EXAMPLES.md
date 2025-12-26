# 📝 Actual Log Examples - ProdukController

Contoh output log sebenarnya untuk membantu Anda menginterpretasi logs.

---

## 1️⃣ Skenario: IT Support Buka Halaman Daftar Produk

### Log Output:
```
[2025-12-26 14:30:45] local.INFO: === PRODUK INDEX DEBUG === 
{
  "user_id": 1,
  "user_name": "Admin",
  "user_role": "it_support",
  "user_aktif": true,
  "assigned_cabang_ids": [1, 2, 3],
  "assigned_cabang_names": ["Cabang Pusat", "Cabang Barat", "Cabang Timur"]
}

[2025-12-26 14:30:45] local.INFO: PRODUK INDEX - Cabang Resolution 
{
  "request_cabang_id": null,
  "resolved_cabang_id": 1
}

[2025-12-26 14:30:46] local.INFO: PRODUK INDEX - Filters Applied 
{
  "cabang_id": 1,
  "cabang_name": "Cabang Pusat",
  "search": "",
  "kategori_id": "",
  "tipe": "",
  "use_cache": true
}

[2025-12-26 14:30:46] local.INFO: PRODUK INDEX - Results 
{
  "total_produk": 12,
  "current_page": 1,
  "per_page": 20,
  "items_returned": 12,
  "first_produk_id": 1,
  "last_produk_id": 12
}
```

### ✅ Interpretasi:
- IT Support berhasil login
- Sistem memilih Cabang Pusat (default)
- Daftar produk berhasil diambil: **12 item**
- Semua tampil di halaman 1

---

## 2️⃣ Skenario: Manager Tanpa Cabang Assignment Buka Index

### Log Output:
```
[2025-12-26 14:35:20] local.INFO: === PRODUK INDEX DEBUG === 
{
  "user_id": 5,
  "user_name": "Budi Manager",
  "user_role": "manager",
  "user_aktif": true,
  "assigned_cabang_ids": [],
  "assigned_cabang_names": []
}

[2025-12-26 14:35:21] local.WARNING: User has no assigned cabang 
{
  "user_id": 5,
  "role": "manager"
}

[2025-12-26 14:35:21] local.INFO: PRODUK INDEX - Cabang Resolution 
{
  "request_cabang_id": null,
  "resolved_cabang_id": null
}

[2025-12-26 14:35:21] local.WARNING: PRODUK INDEX - No cabang assigned for user 
{
  "user_id": 5
}

[2025-12-26 14:35:21] local.INFO: PRODUK INDEX - Returning empty data (no cabang) 
{
  "user_id": 5,
  "reason": "No cabang assigned or selected"
}
```

### ❌ Interpretasi:
- Manager tidak memiliki cabang yang ditugaskan
- `assigned_cabang_ids` adalah **array kosong []**
- **Solusi:** Assign manager ke cabang di admin panel

---

## 3️⃣ Skenario: Manager Coba Tambah Produk (Tidak Authorized)

### Log Output:
```
[2025-12-26 14:40:15] local.INFO: === PRODUK CREATE DEBUG === 
{
  "user_id": 10,
  "user_name": "Rudi Kasir",
  "user_role": "kasir",
  "assigned_cabang_ids": [1],
  "assigned_cabang_names": ["Cabang Pusat"]
}

[2025-12-26 14:40:15] local.WARNING: PRODUK CREATE - Unauthorized access attempt 
{
  "user_id": 10,
  "user_role": "kasir"
}
```

### ❌ Interpretasi:
- Kasir mencoba akses halaman CREATE produk
- Role `kasir` **tidak memiliki permission**
- Response: "Anda tidak memiliki akses untuk menambah produk"

---

## 4️⃣ Skenario: Tambah Produk - Validation Error

### Log Output:
```
[2025-12-26 14:45:30] local.INFO: === PRODUK STORE DEBUG === 
{
  "user_id": 2,
  "user_role": "manager",
  "request_data_keys": [
    "sku",
    "nama",
    "kategori_id",
    "tipe",
    "satuan_dasar",
    "harga_modal",
    "harga_jual"
  ]
}

[2025-12-26 14:45:30] local.INFO: PRODUK STORE - Authorization passed

[2025-12-26 14:45:31] local.WARNING: PRODUK STORE - Validation failed 
{
  "errors": {
    "kategori_id": ["The selected kategori id is invalid."],
    "sku": ["The sku has already been taken."],
    "harga_jual": ["The harga jual field must be at least 0."]
  }
}
```

### ❌ Interpretasi:
- Ada **3 field error**:
  1. `kategori_id`: ID kategori tidak ada di database
  2. `sku`: SKU sudah dipakai (tidak unique)
  3. `harga_jual`: Harga tidak boleh negatif
- **Solusi:** Perbaiki 3 field ini sebelum submit

---

## 5️⃣ Skenario: Tambah Produk - Berhasil

### Log Output:
```
[2025-12-26 14:50:10] local.INFO: === PRODUK STORE DEBUG === 
{
  "user_id": 2,
  "user_role": "manager",
  "request_data_keys": [
    "sku",
    "nama",
    "kategori_id",
    "tipe",
    "satuan_dasar",
    "harga_modal",
    "harga_jual",
    "stok_etalase"
  ]
}

[2025-12-26 14:50:10] local.INFO: PRODUK STORE - Authorization passed

[2025-12-26 14:50:11] local.INFO: PRODUK STORE - Success 
{
  "produk_id": 25,
  "user_id": 2,
  "produk_sku": "PROD-ARABIKA-001",
  "produk_nama": "Kopi Arabika Premium Sumatera",
  "stok_etalase_created": 2
}
```

### ✅ Interpretasi:
- Produk berhasil dibuat dengan ID **25**
- SKU: `PROD-ARABIKA-001`
- Stok ditambahkan untuk **2 cabang**
- Redirect ke halaman daftar produk

---

## 6️⃣ Skenario: Manager Edit Produk Dari Cabang Lain

### Log Output:
```
[2025-12-26 15:00:25] local.INFO: === PRODUK EDIT DEBUG === 
{
  "user_id": 5,
  "user_name": "Budi Manager",
  "user_role": "manager",
  "produk_id": 10,
  "assigned_cabang_ids": [1],
  "assigned_cabang_names": ["Cabang Pusat"]
}

[2025-12-26 15:00:25] local.INFO: PRODUK EDIT - Authorization passed

[2025-12-26 15:00:26] local.INFO: PRODUK EDIT - Produk Loaded 
{
  "produk_id": 10,
  "produk_nama": "Kopi Robusta",
  "produk_sku": "PROD-ROB-001",
  "kategori_id": 2,
  "stok_etalase_count": 2,
  "stok_cabang_ids": [2, 3]
}

[2025-12-26 15:00:26] local.INFO: PRODUK EDIT - Cabang Access Check 
{
  "user_assigned_cabang": [1],
  "produk_cabang_ids": [2, 3],
  "has_intersection": false
}

[2025-12-26 15:00:26] local.WARNING: PRODUK EDIT - Unauthorized cabang access 
{
  "user_id": 5,
  "produk_id": 10,
  "user_cabang": [1],
  "produk_cabang": [2, 3]
}
```

### ❌ Interpretasi:
- Manager Budi memiliki cabang: **[1] - Cabang Pusat**
- Produk ada di cabang: **[2, 3] - Cabang Lain**
- **Tidak ada intersection** = Manager tidak boleh edit
- **Solusi:** Assign manager ke cabang 2 atau 3

---

## 7️⃣ Skenario: Edit Produk - Berhasil

### Log Output:
```
[2025-12-26 15:10:15] local.INFO: === PRODUK UPDATE DEBUG === 
{
  "user_id": 2,
  "user_role": "manager",
  "produk_id": 10,
  "request_data_keys": [
    "_method",
    "sku",
    "nama",
    "kategori_id",
    "tipe",
    "satuan_dasar",
    "harga_modal",
    "harga_jual",
    "stok_etalase"
  ]
}

[2025-12-26 15:10:15] local.INFO: PRODUK UPDATE - Authorization passed

[2025-12-26 15:10:16] local.INFO: PRODUK UPDATE - Produk Loaded 
{
  "produk_id": 10,
  "produk_nama": "Kopi Robusta",
  "stok_etalase_count": 2,
  "affected_cabang_ids": [1, 2]
}

[2025-12-26 15:10:16] local.INFO: PRODUK UPDATE - Cabang Access Check 
{
  "user_assigned_cabang": [1, 2],
  "produk_cabang_ids": [1, 2],
  "has_intersection": true
}

[2025-12-26 15:10:17] local.INFO: PRODUK UPDATE - Success 
{
  "produk_id": 10,
  "user_id": 2,
  "produk_sku": "PROD-ROB-001",
  "affected_cabang_ids": [1, 2]
}
```

### ✅ Interpretasi:
- Manager authorized untuk cabang [1, 2]
- Produk ada di cabang [1, 2]
- **Ada intersection** = Boleh edit
- Data produk berhasil diupdate
- Cache dibersihkan untuk cabang [1, 2]

---

## 8️⃣ Skenario: Kategori Kosong di Form Create

### Log Output:
```
[2025-12-26 15:20:40] local.INFO: === PRODUK CREATE DEBUG === 
{
  "user_id": 1,
  "user_name": "Admin",
  "user_role": "it_support",
  "assigned_cabang_ids": [1, 2, 3],
  "assigned_cabang_names": ["Pusat", "Barat", "Timur"]
}

[2025-12-26 15:20:40] local.INFO: PRODUK CREATE - Authorization passed

[2025-12-26 15:20:41] local.INFO: PRODUK CREATE - Options loaded 
{
  "kategori_count": 0,
  "satuan_count": 0,
  "satuan_list": [],
  "selected_cabang_id": 1
}

[2025-12-26 15:20:41] local.INFO: PRODUK CREATE - Rendering form 
{
  "cabang_list_count": 3,
  "selected_cabang_id": 1,
  "selected_cabang_name": "Cabang Pusat"
}
```

### ❌ Interpretasi:
- Form CREATE dimuat successfully
- **TAPI: `kategori_count: 0`** = Tidak ada kategori!
- **TAPI: `satuan_count: 0`** = Tidak ada satuan!
- Dropdown kategori akan kosong di form
- **Solusi:** Buat kategori dan satuan produk terlebih dahulu

---

## 9️⃣ Skenario: Hapus Produk - Berhasil

### Log Output:
```
[2025-12-26 15:30:55] local.INFO: === PRODUK DESTROY DEBUG === 
{
  "user_id": 1,
  "user_role": "it_support",
  "produk_id": 25
}

[2025-12-26 15:30:55] local.INFO: PRODUK DESTROY - Authorization passed

[2025-12-26 15:30:56] local.INFO: PRODUK DESTROY - Produk Details 
{
  "produk_id": 25,
  "produk_nama": "Kopi Arabika Premium Sumatera",
  "stok_etalase_count": 2,
  "affected_cabang_ids": [1, 2]
}

[2025-12-26 15:30:56] local.INFO: PRODUK DESTROY - Deleting 
{
  "produk_id": 25,
  "affected_cabang_ids": [1, 2]
}

[2025-12-26 15:30:56] local.INFO: PRODUK DESTROY - Success 
{
  "produk_id": 25,
  "user_id": 1,
  "produk_sku": "PROD-ARABIKA-001",
  "affected_cabang_ids": [1, 2]
}
```

### ✅ Interpretasi:
- Produk ID 25 dihapus (soft delete)
- Cache dibersihkan untuk cabang [1, 2]
- Operasi success

---

## 🔟 Skenario: Database Error (Exception)

### Log Output:
```
[2025-12-26 15:40:30] local.ERROR: Error creating produk 
{
  "error": "Call to undefined method Produk::create()",
  "trace": "..."
}

[2025-12-26 15:40:30] local.ERROR: Error deleting produk 
{
  "error": "Model [App\\Models\\Produk] does not exist",
  "trace": "..."
}
```

### ❌ Interpretasi:
- **Error creating produk** = Metode atau class tidak ada
- **Error deleting produk** = Model tidak ditemukan
- **Solusi:** 
  1. Cek class definition ada atau tidak
  2. Cek namespace import
  3. Cek database relationship

---

## 🎯 Quick Scan Tips

### Cari "Semua Index Calls"
```bash
grep "PRODUK INDEX" storage/logs/laravel.log | tail -20
```

### Cari "Semua Errors"
```bash
grep "ERROR\|CRITICAL" storage/logs/laravel.log | tail -10
```

### Cari "Semua Unauthorized"
```bash
grep "Unauthorized\|WARNING" storage/logs/laravel.log | tail -15
```

### Cari User Tertentu (ID 5)
```bash
grep "user_id.*5" storage/logs/laravel.log | tail -30
```

---

**Done! Sekarang Anda siap debug dengan confidence! 🚀**
