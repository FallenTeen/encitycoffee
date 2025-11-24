# Critical-Path Testing Instructions for POS Frontend and Backend

This document outlines key manual and API tests to verify core POS functionalities in resources/js/pages/pos and corresponding backend API endpoints.

## 1. Backend API Endpoints to Test
- Auth
  - GET `/api/pos/auth/me` - verify user info returned when authenticated.
- Shifts
  - GET `/api/pos/shift/aktif` - get active shift.
  - POST `/api/pos/shift/buka` - open new shift.
  - POST `/api/pos/shift/{shift}/tutup` - close shift.
  - GET `/api/pos/shift/{shift}` - get shift detail.
  - GET `/api/pos/shift/` - get paginated list of shifts.
- Produk
  - GET `/api/pos/produk/` - list products.
  - GET `/api/pos/produk/{produk}` - product details.
- Stok
  - GET `/api/pos/stok/cabang/{cabang}` - stock for cabang.
  - GET `/api/pos/stok/cabang/{cabang}/rendah` - low stock items.
- Transaksi
  - POST `/api/pos/transaksi/` - create transaction.
  - POST `/api/pos/transaksi/{transaksi}/batalkan` - cancel transaction.
  - GET `/api/pos/transaksi/shift/{shift}` - list transactions by shift.
- Kalibrasi
  - POST `/api/pos/kalibrasi/` - save calibration.
  - PUT `/api/pos/kalibrasi/{kalibrasi}/pilih` - select calibration.
  - GET `/api/pos/kalibrasi/shift/{shift}` - calibrations by shift.
- Laporan
  - GET `/api/pos/laporan/shift/{shift}/ringkasan` - shift report summary.
  - GET `/api/pos/laporan/cabang/{cabang}/harian` - daily report.
- Sinkronisasi
  - POST `/api/pos/sinkronisasi/proses` - process queue.
  - GET `/api/pos/sinkronisasi/status` - sync status.

## 2. Frontend POS Pages to Test

### Shifts
- `/pos/shifts`
  - Load and display shift list with search/filter.
  - Verify navigation to details page works.
- `/pos/shifts/[id]`
  - Display shift details and transactions.
- `/pos/shifts/open`
  - Open new shift flow.
- `/pos/shifts/close`
  - Close shift flow.

### Produk
- `/pos/produk`
  - List products, filtering.
- `/pos/produk/[id]`
  - Product details display.

### Transaksi
- `/pos/transaksi/create`
  - Create new transaction UI and backend call.
- `/pos/transaksi/[id]`
  - View transaction details, cancel transaction.

### Stok
- `/pos/stok/cabang/[id]`
  - List stock items.
- Low stock warning UI.

### Kalibrasi and Laporan
- Views to visualize calibration data and reports.

## 3. Test Actions
- Authenticate as various roles; verify role-based UI and API access.
- Create, update, and cancel shifts and transactions.
- Validate error handling on invalid inputs or unauthorized access.
- Confirm UI responsiveness, loading states, and error messages.
- Validate that data shown matches backend responses.

## 4. Testing Instructions
1. Use Postman or curl to call APIs in section 1.
2. Use application UI to exercise flows under section 2.
3. Record any bugs or unexpected behavior for remediation.
4. Verify logs, server errors if available.

## 5. After Testing
- Report issues to be fixed before final deployment.
- Update code as necessary.
- Repeat testing post-fixes for verification.

---

You can run these tests manually or automate with your preferred testing framework (e.g., Cypress for UI, PHPUnit or Postman for APIs).

Let me know if you want sample automated test scripts or additional assistance with testing setup.
