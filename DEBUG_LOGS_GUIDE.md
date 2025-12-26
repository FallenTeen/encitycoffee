# Debug Logs Guide - ProdukController

Panduan lengkap untuk debug setiap method di ProdukController. Semua logs ditulis ke file `storage/logs/laravel.log`.

---

## 📍 Cara Melihat Logs

### Real-time (Linux/Mac):
```bash
tail -f storage/logs/laravel.log
```

### Real-time (Windows PowerShell):
```powershell
Get-Content storage/logs/laravel.log -Wait
```

### Filter logs tertentu:
```bash
grep "PRODUK INDEX" storage/logs/laravel.log
```

---

## 🔍 Debug Points untuk Setiap Method

### 1. **INDEX** - Daftar Produk (`/produk`)

**Header Log:**
```
=== PRODUK INDEX DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id` | ID user yang login |
| `user_name` | Nama user |
| `user_role` | Role user (it_support, manager, supervisor, kasir) |
| `user_aktif` | Apakah user aktif |
| `assigned_cabang_ids` | ID cabang yang ditugaskan ke user |
| `assigned_cabang_names` | Nama-nama cabang |

**Follow-up Logs:**
- `PRODUK INDEX - Cabang Resolution`: Menampilkan cabang yang dipilih vs yg diresolve
- `PRODUK INDEX - Returning empty data`: Jika user belum punya cabang assigned
- `PRODUK INDEX - Filters Applied`: Filter yang digunakan (search, kategori, tipe)
- `PRODUK INDEX - Results`: Total produk, halaman, jumlah items

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: === PRODUK INDEX DEBUG === {
  "user_id": 1,
  "user_role": "it_support",
  "assigned_cabang_ids": [1, 2, 3]
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK INDEX - Filters Applied {
  "cabang_id": 1,
  "search": "kopi",
  "kategori_id": "2"
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK INDEX - Results {
  "total_produk": 5,
  "items_returned": 5
}
```

---

### 2. **SHOW** - Detail Produk (`/produk/{id}`)

**Header Log:**
```
=== PRODUK SHOW DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id`, `user_role` | Identitas user |
| `produk_id` | ID produk yang dilihat |
| `assigned_cabang_ids` | Cabang user |

**Follow-up Logs:**
- `PRODUK SHOW - Cabang Info`: Cabang yang diresolve
- `PRODUK SHOW - Produk Retrieved`: Apakah produk ditemukan, nama produk
- (Warning) `PRODUK SHOW - Produk not found`: Jika produk tidak ada

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: === PRODUK SHOW DEBUG === {
  "user_id": 1,
  "produk_id": 5
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK SHOW - Produk Retrieved {
  "found": true,
  "produk_name": "Kopi Arabika Premium"
}
```

---

### 3. **CREATE** - Form Tambah Produk (`/produk/create`)

**Header Log:**
```
=== PRODUK CREATE DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id`, `user_name`, `user_role` | Identitas user |
| `assigned_cabang_ids`, `assigned_cabang_names` | Cabang user |

**Follow-up Logs:**
- `PRODUK CREATE - Authorization passed`: Jika user authorized
- (Warning) `PRODUK CREATE - Unauthorized access attempt`: Jika user tidak punya akses
- `PRODUK CREATE - Options loaded`: Jumlah kategori, satuan yang tersedia
- `PRODUK CREATE - Rendering form`: Cabang yang dipilih saat form di-render

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: === PRODUK CREATE DEBUG === {
  "user_id": 2,
  "user_role": "manager",
  "assigned_cabang_ids": [1]
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK CREATE - Options loaded {
  "kategori_count": 5,
  "satuan_count": 3,
  "satuan_list": ["pcs", "kg", "liter"]
}
```

---

### 4. **STORE** - Simpan Produk Baru (`POST /produk`)

**Header Log:**
```
=== PRODUK STORE DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id`, `user_role` | Identitas user |
| `request_data_keys` | Field-field yang dikirim |

**Follow-up Logs:**
- `PRODUK STORE - Authorization passed`: User authorized
- (Warning) `PRODUK STORE - Validation failed`: Field yang gagal validasi
- (Warning) `PRODUK STORE - Unauthorized cabang access`: User tidak punya akses ke cabang
- `PRODUK STORE - Success`: Produk berhasil dibuat

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.WARNING: PRODUK STORE - Validation failed {
  "errors": {
    "sku": ["The sku field is required."],
    "kategori_id": ["The selected kategori id is invalid."]
  }
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK STORE - Success {
  "produk_id": 15,
  "produk_sku": "PROD-001",
  "produk_nama": "Kopi Arabika Baru",
  "stok_etalase_created": 2
}
```

---

### 5. **EDIT** - Form Edit Produk (`/produk/{id}/edit`)

**Header Log:**
```
=== PRODUK EDIT DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id`, `user_name`, `user_role` | Identitas user |
| `produk_id` | Produk yang diedit |
| `assigned_cabang_ids`, `assigned_cabang_names` | Cabang user |

**Follow-up Logs:**
- `PRODUK EDIT - Authorization passed`: User authorized
- (Warning) `PRODUK EDIT - Unauthorized access attempt`: User tidak authorized
- `PRODUK EDIT - Produk Loaded`: Detail produk yang dimuat
- `PRODUK EDIT - Cabang Access Check`: Cek akses cabang untuk manager/supervisor
- (Warning) `PRODUK EDIT - Unauthorized cabang access`: Manager tidak punya akses
- `PRODUK EDIT - Rendering form`: Form siap di-render

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: === PRODUK EDIT DEBUG === {
  "user_id": 2,
  "user_role": "manager",
  "produk_id": 5
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK EDIT - Produk Loaded {
  "produk_nama": "Kopi Arabika",
  "stok_etalase_count": 2,
  "stok_cabang_ids": [1, 2]
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK EDIT - Cabang Access Check {
  "user_assigned_cabang": [1],
  "produk_cabang_ids": [1, 2],
  "has_intersection": true
}
```

---

### 6. **UPDATE** - Simpan Edit Produk (`PUT /produk/{id}`)

**Header Log:**
```
=== PRODUK UPDATE DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id`, `user_role` | Identitas user |
| `produk_id` | Produk yang diupdate |
| `request_data_keys` | Field-field yang dikirim |

**Follow-up Logs:**
- `PRODUK UPDATE - Authorization passed`: User authorized
- `PRODUK UPDATE - Produk Loaded`: Detail produk sebelum update
- `PRODUK UPDATE - Cabang Access Check`: Cek akses cabang (manager/supervisor)
- (Warning) `PRODUK UPDATE - Validation failed`: Field yang gagal validasi
- (Warning) `PRODUK UPDATE - Unauthorized cabang access for stok`: Unauthorized stok edit
- `PRODUK UPDATE - Success`: Produk berhasil diupdate

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: === PRODUK UPDATE DEBUG === {
  "user_id": 2,
  "produk_id": 5
}
[YYYY-MM-DD HH:MM:SS] local.WARNING: PRODUK UPDATE - Validation failed {
  "errors": {
    "harga_jual": ["The harga jual field must be at least 0."]
  }
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK UPDATE - Success {
  "produk_id": 5,
  "user_id": 2,
  "produk_sku": "PROD-001",
  "affected_cabang_ids": [1, 2]
}
```

---

### 7. **DESTROY** - Hapus Produk (`DELETE /produk/{id}`)

**Header Log:**
```
=== PRODUK DESTROY DEBUG ===
```

**Info yang di-log:**
| Info | Keterangan |
|------|-----------|
| `user_id`, `user_role` | Identitas user |
| `produk_id` | Produk yang dihapus |

**Follow-up Logs:**
- `PRODUK DESTROY - Authorization passed`: User authorized
- `PRODUK DESTROY - Produk Details`: Detail produk sebelum dihapus
- `PRODUK DESTROY - Cabang Access Check`: Cek akses cabang (manager/supervisor)
- (Warning) `PRODUK DESTROY - Unauthorized cabang access`: Manager tidak punya akses
- `PRODUK DESTROY - Deleting`: Sedang menghapus produk
- `PRODUK DESTROY - Success`: Produk berhasil dihapus

**Contoh output:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: === PRODUK DESTROY DEBUG === {
  "user_id": 1,
  "produk_id": 5
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK DESTROY - Produk Details {
  "produk_nama": "Kopi Arabika",
  "stok_etalase_count": 2,
  "affected_cabang_ids": [1, 2]
}
[YYYY-MM-DD HH:MM:SS] local.INFO: PRODUK DESTROY - Success {
  "produk_id": 5,
  "affected_cabang_ids": [1, 2]
}
```

---

## 🎯 Common Issues & Debug Tips

### ❌ Produk tidak muncul di Index
**Debug steps:**
1. Lihat `assigned_cabang_ids` di log INDEX
2. Jika kosong → user belum assigned ke cabang
3. Jika ada → lihat `PRODUK INDEX - Results` untuk total produk

**Log untuk dicek:**
```
PRODUK INDEX - Returning empty data (no cabang)
```

---

### ❌ Kategori tidak muncul di Create/Edit
**Debug steps:**
1. Lihat `kategori_count` di log CREATE/EDIT
2. Jika 0 → tidak ada kategori di database
3. Jika > 0 → kategori ada, cek frontend

**Log untuk dicek:**
```
PRODUK CREATE - Options loaded
PRODUK EDIT - Rendering form
```

---

### ❌ User tidak bisa edit produk (manager/supervisor)
**Debug steps:**
1. Lihat `user_assigned_cabang` vs `produk_cabang_ids`
2. Jika tidak ada intersection → manager tidak punya akses ke cabang produk
3. Assign user ke cabang yang tepat

**Log untuk dicek:**
```
PRODUK EDIT - Cabang Access Check
PRODUK EDIT - Unauthorized cabang access
```

---

### ❌ Form tidak bisa disimpan (validation error)
**Debug steps:**
1. Lihat errors di log STORE/UPDATE
2. Periksa field mana yang fail
3. Lihat rule validation untuk memperbaiki input

**Log untuk dicek:**
```
PRODUK STORE - Validation failed
PRODUK UPDATE - Validation failed
```

---

## 📊 Role-Based Debug Expectations

### IT Support
- Dapat akses SEMUA cabang
- Log akan show `assigned_cabang_ids: [semua ID]`
- Tidak ada warning authorization/cabang access

### Manager
- Dapat akses cabang yang di-assign saja
- Log akan show Cabang Access Check untuk setiap edit/delete
- Jika unauthorized → warning

### Supervisor
- Sama seperti Manager
- Log akan menunjukkan batasan cabang

### Kasir
- Hanya view (tidak ada CREATE/EDIT/DESTROY)
- Jika coba akses → warning `Unauthorized access`

---

## 💡 Tips Debugging

1. **Buka log di tab terpisah:**
   ```bash
   tail -f storage/logs/laravel.log | grep PRODUK
   ```

2. **Hanya lihat errors:**
   ```bash
   grep "WARNING\|ERROR" storage/logs/laravel.log | tail -20
   ```

3. **Cari user tertentu:**
   ```bash
   grep "user_id.*: 2" storage/logs/laravel.log
   ```

4. **Export ke file untuk analisis:**
   ```bash
   grep PRODUK storage/logs/laravel.log > produk_debug.txt
   ```

---

## 🔄 Log Lifecycle

1. **INDEX/SHOW/CREATE/EDIT**: Method dipanggil
   - Header log (`=== PRODUK XXX DEBUG ===`)
   - Detail log (Cabang, Authorization, Options)
   - Result log (Item found, options loaded, etc)

2. **STORE/UPDATE/DESTROY**: Data diproses
   - Header log
   - Authorization check
   - Validation check (jika ada)
   - Success log OR Error log

3. **Error handling**: Exception tertangkap
   - `Error XXX` log dengan error message

---

Happy debugging! 🚀
