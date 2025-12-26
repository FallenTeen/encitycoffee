# 🎨 Visual Debug Guide - ProdukController

## 📍 Debug Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                    USER PERFORMS ACTION                      │
├─────────────────────────────────────────────────────────────┤
│ Examples:                                                     │
│ • Open /produk/index                                         │
│ • Click "Tambah Produk"                                      │
│ • Submit form                                                │
│ • Edit produk                                                │
│ • Delete produk                                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   CONTROLLER METHOD CALLED                   │
├─────────────────────────────────────────────────────────────┤
│ index() / create() / store() / edit() / update() / destroy()│
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    DEBUG LOG #1 (HEADER)                     │
├─────────────────────────────────────────────────────────────┤
│ Log::info('=== PRODUK XXX DEBUG ===', [                     │
│   'user_id' => ...,                                          │
│   'user_role' => ...,                                        │
│   'assigned_cabang_ids' => ...                               │
│ ]);                                                           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    DEBUG LOG #2+ (DETAILS)                   │
├─────────────────────────────────────────────────────────────┤
│ • Cabang info                                                │
│ • Authorization status                                       │
│ • Data loaded (produk, kategori, satuan)                    │
│ • Filters/Validation                                         │
│ • Access control checks                                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    DEBUG LOG #FINAL                          │
├─────────────────────────────────────────────────────────────┤
│ SUCCESS:                                                      │
│ Log::info('PRODUK XXX - Success', [...])                    │
│                                                               │
│ OR                                                            │
│                                                               │
│ FAILURE/WARNING:                                             │
│ Log::warning('PRODUK XXX - ...', [...])                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              ALL LOGS WRITTEN TO FILE                        │
├─────────────────────────────────────────────────────────────┤
│ storage/logs/laravel.log                                     │
│                                                               │
│ View with: tail -f storage/logs/laravel.log | grep PRODUK   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Method Debug Flow

### INDEX Method
```
GET /produk
    │
    ├─→ [HEADER LOG] User info & assigned cabang
    │
    ├─→ getCabangId() 
    │   └─→ [DEBUG LOG] Cabang Resolution
    │
    ├─→ (if no cabang)
    │   └─→ [INFO LOG] Returning empty data
    │
    ├─→ (if has cabang)
    │   ├─→ getProdukByCabang()
    │   │   └─→ Cache or Database query
    │   │
    │   ├─→ [DEBUG LOG] Filters Applied
    │   │
    │   └─→ [INFO LOG] Results (count, pagination)
    │
    └─→ Render View with data
```

### CREATE Method
```
GET /produk/create
    │
    ├─→ [HEADER LOG] User info & cabang
    │
    ├─→ canManageProduk() check
    │   ├─→ ✓ [INFO LOG] Authorization passed
    │   └─→ ✗ [WARNING LOG] Unauthorized access attempt
    │
    ├─→ getCabangId()
    │
    ├─→ Load options
    │   ├─→ KategoriProduk::all()
    │   ├─→ SatuanProduk::distinct()
    │   └─→ [DEBUG LOG] Options loaded
    │
    └─→ Render form with data
        └─→ [INFO LOG] Rendering form
```

### STORE Method
```
POST /produk
    │
    ├─→ [HEADER LOG] User & request keys
    │
    ├─→ canManageProduk() check
    │
    ├─→ Validator::make()
    │   ├─→ ✓ [INFO LOG] (continue)
    │   └─→ ✗ [WARNING LOG] Validation failed + errors detail
    │
    ├─→ Access control check (if manager/supervisor)
    │   ├─→ ✓ [INFO LOG] (continue)
    │   └─→ ✗ [WARNING LOG] Unauthorized cabang access
    │
    ├─→ Produk::create()
    │
    ├─→ Create stok etalase (if provided)
    │
    ├─→ Clear cache
    │
    └─→ [INFO LOG] Success + produk_id
        └─→ Redirect to index
```

### EDIT Method
```
GET /produk/{id}/edit
    │
    ├─→ [HEADER LOG] User & produk_id
    │
    ├─→ canManageProduk() check
    │
    ├─→ Produk::with(stokEtalase)->find()
    │   └─→ [DEBUG LOG] Produk Loaded
    │
    ├─→ Access control (if manager/supervisor)
    │   ├─→ [INFO LOG] Cabang Access Check
    │   ├─→ ✓ [INFO LOG] (continue)
    │   └─→ ✗ [WARNING LOG] Unauthorized cabang access
    │
    ├─→ Load form options
    │   └─→ [DEBUG LOG] Rendering form
    │
    └─→ Render form with produk data
```

### UPDATE Method
```
PUT /produk/{id}
    │
    ├─→ [HEADER LOG] User, produk_id & request keys
    │
    ├─→ canManageProduk() check
    │
    ├─→ Load Produk
    │   └─→ [DEBUG LOG] Produk Loaded
    │
    ├─→ Access control check
    │   └─→ [INFO LOG] Cabang Access Check
    │
    ├─→ Validator::make()
    │   ├─→ ✓ [INFO LOG] (continue)
    │   └─→ ✗ [WARNING LOG] Validation failed
    │
    ├─→ Stok cabang access check
    │   └─→ ✗ [WARNING LOG] Unauthorized cabang access
    │
    ├─→ Produk::update()
    │
    ├─→ Update stok etalase
    │
    ├─→ Clear cache
    │
    └─→ [INFO LOG] Success + affected cabang
        └─→ Redirect to index
```

### DESTROY Method
```
DELETE /produk/{id}
    │
    ├─→ [HEADER LOG] User & produk_id
    │
    ├─→ canManageProduk() check
    │
    ├─→ Load Produk
    │   └─→ [DEBUG LOG] Produk Details
    │
    ├─→ Access control check
    │   └─→ [INFO LOG] Cabang Access Check
    │
    ├─→ [INFO LOG] Deleting
    │
    ├─→ Produk::delete()
    │
    ├─→ Clear cache for affected cabang
    │
    └─→ [INFO LOG] Success + affected cabang
        └─→ Redirect to index
```

---

## 🚨 Error Decision Tree

```
                    ┌─ LOG ERROR MESSAGE
                    │
        [Error]─────┼─ Was it expected? ──┐
                    │                     │
                    └─────────────────────┘
                          │
                          ├─→ Authorization error?
                          │   └─→ Check: canManageProduk()
                          │       - User role correct?
                          │       - Role is it_support/manager/supervisor?
                          │
                          ├─→ Cabang access error?
                          │   └─→ Check: Cabang Access Check log
                          │       - User has cabang assigned?
                          │       - Cabang intersection exists?
                          │
                          ├─→ Validation error?
                          │   └─→ Check: Validation failed log
                          │       - Which field failed?
                          │       - What is the error message?
                          │       - Fix input & retry
                          │
                          ├─→ Not found error?
                          │   └─→ Check: Produk Loaded log
                          │       - Does produk ID exist?
                          │       - Is it in correct cabang?
                          │
                          ├─→ Database error?
                          │   └─→ Check: ERROR log
                          │       - Model exists?
                          │       - Table exists?
                          │       - Correct relationships?
                          │
                          └─→ Unknown error?
                              └─→ Check: Exception log
                                  - Full error message
                                  - Stack trace
```

---

## 📊 Role-Based Log Patterns

### IT Support
```
✓ assigned_cabang_ids: [1, 2, 3, ...]  (많음)
✓ Authorization passed (항상)
✓ No cabang access restrictions
✓ Can CRUD any product in any cabang
```

### Manager
```
? assigned_cabang_ids: [] (문제!) or [1, 2] (OK)
✓ Authorization passed (만약 role 올바름)
? Cabang Access Check: ✓ intersection (OK) or ✗ no intersection (blocked)
✓ Can CRUD products only in assigned cabang
```

### Supervisor
```
= Same as Manager
```

### Kasir
```
✗ Authorization passed (never - not in allowed roles)
✗ Cannot CREATE/EDIT/DELETE
✓ Can only INDEX/SHOW
✗ "Unauthorized access attempt" warning (expected)
```

---

## 🎯 Quick Scan Indicators

### ✅ Healthy Log Pattern (Index)
```
=== PRODUK INDEX DEBUG ===
├─ user_role: "it_support"
├─ assigned_cabang_ids: [1, 2]
│
PRODUK INDEX - Cabang Resolution
├─ resolved_cabang_id: 1
│
PRODUK INDEX - Filters Applied
├─ search: ""
│
PRODUK INDEX - Results
├─ total_produk: 12  ← Produk ada!
└─ items_returned: 12
```

### ⚠️ Suspicious Pattern (Index)
```
=== PRODUK INDEX DEBUG ===
├─ user_role: "manager"
├─ assigned_cabang_ids: []  ← ⚠️ KOSONG!
│
User has no assigned cabang ← ⚠️ WARNING!
│
PRODUK INDEX - Returning empty data (no cabang) ← ⚠️
```

### ❌ Error Pattern (Store)
```
=== PRODUK STORE DEBUG ===
├─ user_id: 2
│
PRODUK STORE - Validation failed ← ❌
├─ "kategori_id": ["The selected kategori id is invalid."] ← ❌
├─ "sku": ["The sku has already been taken."] ← ❌
```

---

## 🔍 Log Reading Tips

1. **Start from HEADER**
   - `=== PRODUK XXX DEBUG ===`
   - Identifies method & user

2. **Follow INFO logs sequentially**
   - Logs are in execution order
   - Shows step-by-step flow

3. **Look for WARNING logs**
   - Indicates issues/failures
   - But not necessarily blocked

4. **Check for ERROR logs**
   - Indicates exceptions
   - Action failed completely

5. **Compare with successful patterns**
   - See LOG_EXAMPLES.md
   - Identify what's missing

---

## 💻 Terminal Commands Cheat Sheet

```bash
# Watch real-time
tail -f storage/logs/laravel.log | grep PRODUK

# Count occurrences
grep "PRODUK INDEX" storage/logs/laravel.log | wc -l

# Last 50 lines
tail -50 storage/logs/laravel.log

# Last INDEX action
grep "PRODUK INDEX" storage/logs/laravel.log | tail -5

# Find specific user
grep "user_id.*2" storage/logs/laravel.log | grep PRODUK

# Find errors only
grep "ERROR\|WARNING" storage/logs/laravel.log | grep PRODUK

# Export & analyze
grep PRODUK storage/logs/laravel.log | grep "Unauthorized" > report.txt

# Clear old logs (backup first!)
cp storage/logs/laravel.log storage/logs/laravel.backup.log
echo "" > storage/logs/laravel.log
```

---

## 📚 Reference Links

- **Detailed Guide:** DEBUG_LOGS_GUIDE.md
- **Quick Reference:** QUICK_DEBUG.md
- **Practical Examples:** LOG_EXAMPLES.md
- **Implementation Notes:** DEBUG_IMPLEMENTATION.md

---

**Now you're ready to debug like a pro! 🚀**
