# 📚 Perpustakaan Online - Modal Stats Feature

## 🎯 Overview

Feature interaktif statistics cards di dashboard admin yang menampilkan:
- 📚 **Total Buku** - Daftar semua buku dengan stok
- 👥 **Anggota** - Daftar semua member dengan status
- 🔄 **Sedang Dipinjam** - Buku yang belum dikembalikan
- ⏰ **Terlambat** - Peminjaman yang overdue

Setiap card dapat di-hover untuk melihat tooltip dan di-klik untuk membuka modal dengan data detail.

---

## ✨ Features

### Interactive Cards ✅
- Hover effects dengan shadow dan scaling
- Tooltip muncul saat hover
- Click untuk membuka modal
- Close modal dengan menekan overlay atau × button

### Modal Display ✅
- Dark overlay background
- Centered modal box
- Responsive design (mobile-friendly)
- Scrollable table untuk large datasets
- Status badges dengan warna berbeda

### Data Loading ✅
- AJAX requests dengan session authentication
- Loading state dengan spinner
- Error handling dengan message yang user-friendly
- Empty state handling

### Multi-Tenant Support ✅
- Data difilter berdasarkan school_id dari session
- Setiap sekolah hanya melihat data miliknya

---

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+
- MySQL/MariaDB
- XAMPP atau web server lainnya
- Browser modern (Chrome, Firefox, Edge, Safari)

### Testing Feature
1. **Buka Dashboard:**
   ```
   http://localhost/perpustakaan-online/public/index.php
   ```

2. **Pastikan Sudah Login**
   - Feature memerlukan session authentication
   - Jika belum login, akan redirect

3. **Hover Over Card**
   - Lihat shadow dan transform effect
   - Lihat tooltip dengan deskripsi

4. **Klik Card**
   - Modal overlay muncul
   - Data table muncul di modal
   - Tunggu 1-3 detik untuk loading

5. **Close Modal**
   - Klik × button di kanan atas modal
   - Atau klik area gelap di sekitar modal

---

## 📁 File Structure

### Frontend Files
```
assets/
├── js/
│   └── stats-modal.js          # Modal manager & AJAX logic
└── css/
    └── index.css                # Card & modal styling

public/
├── index.php                     # Dashboard dengan cards
└── debug-stats-modal.html        # Debug tool
```

### Backend Files
```
public/api/
├── get-stats-books.php          # Books endpoint
├── get-stats-members.php        # Members endpoint
├── get-stats-borrowed.php       # Active borrows endpoint
└── get-stats-overdue.php        # Overdue items endpoint

src/
├── auth.php                      # Authentication helper
└── db.php                        # Database connection
```

### Documentation Files
```
FIX_SESSION_CREDENTIALS.md        # Technical deep dive
FIX_SUMMARY.md                    # Quick overview
QUICK_TEST_GUIDE.md               # User testing steps
CREDENTIALS_EXPLAINED.md           # Detailed explanation
IMPLEMENTATION_CHECKLIST.md        # Complete checklist
README.md                         # This file
```

---

## 🔧 How It Works

### Architecture
```
User Dashboard (index.php)
    ├─ 4 Interactive Cards
    │  ├─ Total Buku
    │  ├─ Anggota
    │  ├─ Sedang Dipinjam
    │  └─ Terlambat
    │
    └─ Modal System (stats-modal.js)
       ├─ Event Listeners (card clicks)
       ├─ AJAX Requests (fetch with credentials)
       ├─ Data Display (HTML table generation)
       └─ Modal Overlay Management
           
API Endpoints (public/api/)
    ├─ get-stats-books.php
    │  └─ SELECT from books table
    ├─ get-stats-members.php
    │  └─ SELECT from members table
    ├─ get-stats-borrowed.php
    │  └─ SELECT from borrows (returned_at IS NULL)
    └─ get-stats-overdue.php
       └─ SELECT from borrows (status='overdue')
```

### Data Flow
```
1. User clicks card → Card click event triggered
2. modalManager.openModal(type) called
3. Modal overlay shown (CSS class: .active)
4. fetchAndDisplayData(type) started
5. fetch() with credentials sends AJAX request
6. API endpoint receives request WITH session
7. requireAuth() validates session ✅
8. Database query executed (filtered by school_id)
9. Results formatted to JSON
10. Response received, parsed, rendered
11. displayData() generates HTML table
12. Table inserted into modal body
13. User sees data in modal ✅
```

---

## 🔐 Security Features

### Authentication
- Setiap API endpoint require session authentication
- `requireAuth()` function validates user login
- Redirect to login jika session tidak valid

### Authorization
- School ID filtering di database queries
- User hanya melihat data dari sekolah mereka
- Prevents cross-school data access

### Data Protection
- Prepared statements (SQL injection prevention)
- HTML escaping dengan `htmlspecialchars()`
- XSS prevention
- JSON responses (tidak HTML)

### Session Security
- AJAX requests include cookies dengan `credentials: 'include'`
- Same-origin requests only (CORS not applicable)
- Session validation on every request

---

## 🧪 Testing & Debugging

### Quick Test
1. Open browser F12 → Console tab
2. Click card
3. Look for success log: `"Response: {success: true, ...}"`
4. Verify table appears in modal

### Detailed Debug
1. Open debug tool:
   ```
   http://localhost/perpustakaan-online/public/debug-stats-modal.html
   ```

2. Click test buttons:
   - "Test Books Endpoint" → Check API response
   - "Check Session" → Verify authentication
   - "Check Auth" → Verify authorization

3. View live data previews with status indicators

### Network Inspection
1. F12 → Network tab
2. Click card
3. Find request to `get-stats-books.php`
4. Check:
   - Status: Should be **200**
   - Response: Should be **valid JSON**
   - Headers: Should have **Cookie** header

### Common Issues & Solutions

| Issue | Symptom | Solution |
|-------|---------|----------|
| Not authenticated | Redirect to login | Logout & login |
| Session expired | 302 response | Refresh page & login |
| Database empty | "No data" message | Check database for records |
| Path wrong | 404 error | Verify absolute path |
| CORS error | Blocked request | Check credentials flag |

---

## 📊 Database Requirements

### Required Tables

#### books
```sql
- id (INT, PK)
- title (VARCHAR)
- author (VARCHAR)
- category (VARCHAR)
- copies (INT)
- school_id (INT)
- created_at (DATETIME)
```

#### members
```sql
- id (INT, PK)
- name (VARCHAR)
- nisn (VARCHAR)
- email (VARCHAR)
- status (ENUM: active, inactive)
- school_id (INT)
- created_at (DATETIME)
```

#### borrows
```sql
- id (INT, PK)
- book_id (INT, FK)
- member_id (INT, FK)
- borrowed_at (DATETIME)
- due_at (DATETIME)
- returned_at (DATETIME, nullable)
- status (ENUM: pending, active, overdue, completed)
- school_id (INT)
```

---

## 📈 Performance

### Response Times
- Endpoint response: **200-500ms** (depends on data size)
- Modal open: **<500ms**
- Data display: **1-3 seconds** (includes network + rendering)

### Optimization Tips
- Limit results if >1000 rows (add pagination)
- Add database indexes on frequently queried columns
- Cache results jika data tidak sering berubah
- Use CDN untuk assets (CSS/JS) di production

### Browser Support
- Chrome 60+
- Firefox 55+
- Edge 79+
- Safari 12+
- Mobile browsers (responsive design)

---

## 🚦 Status Indicators

### Card Status
- **Available** (Green) - Stok tersedia
- **Unavailable** (Red) - Stok habis

### Member Status
- **Aktif** (Green) - Member aktif
- **Nonaktif** (Gray) - Member tidak aktif

### Borrow Status
- **Sedang Dipinjam** (Blue) - Normal borrow
- **Akan Jatuh Tempo** (Yellow) - Due within 3 days
- **TERLAMBAT** (Red) - Overdue

---

## 🔄 API Reference

### GET /public/api/get-stats-books.php
**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Judul Buku",
            "author": "Penulis",
            "category": "Kategori",
            "total": 5,
            "borrowed": 2,
            "available": 3,
            "status": "Tersedia"
        }
    ],
    "total": 10
}
```

### GET /public/api/get-stats-members.php
**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Nama Member",
            "nisn": "123456789",
            "email": "member@email.com",
            "status": "Aktif",
            "current_borrows": 2,
            "joined_date": "01 Jan 2024"
        }
    ],
    "total": 50
}
```

### GET /public/api/get-stats-borrowed.php
**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "book_title": "Judul Buku",
            "book_author": "Penulis",
            "member_name": "Nama Member",
            "member_nisn": "123456789",
            "borrowed_date": "01 Jan 2024",
            "due_date": "08 Jan 2024",
            "days_remaining": 2,
            "status": "Sedang Dipinjam"
        }
    ],
    "total": 25
}
```

### GET /public/api/get-stats-overdue.php
**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "book_title": "Judul Buku",
            "book_author": "Penulis",
            "member_name": "Nama Member",
            "member_nisn": "123456789",
            "borrowed_date": "01 Jan 2024",
            "due_date": "08 Jan 2024",
            "days_overdue": 5
        }
    ],
    "total": 3
}
```

---

## 🎨 Customization

### Change Card Colors
Edit `/assets/css/index.css`:
```css
.stat {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Change Modal Width
Edit `/assets/css/index.css`:
```css
#statsModal .modal-content {
    max-width: 900px;  /* Change this value */
}
```

### Add More Cards
1. Add HTML in `/public/index.php`:
```html
<div class="stat" data-stat-type="new-type" data-tooltip="Description">
    <h3>New Card Title</h3>
    <div class="stat-value">0</div>
</div>
```

2. Add endpoint: `/public/api/get-stats-new-type.php`
3. Add to modalManager in `stats-modal.js`

---

## 📝 Future Enhancements

- [ ] Pagination untuk large datasets
- [ ] Search/filter dalam modal
- [ ] Sort by columns
- [ ] Export to CSV/PDF
- [ ] Date range filters
- [ ] Data refresh button
- [ ] Caching layer
- [ ] Advanced analytics charts

---

## 🆘 Support & Troubleshooting

### Common Questions

**Q: Data tidak muncul saat klik card?**
A: Buka F12 → Network tab → klik card → cek response dari endpoint

**Q: Modal tidak muncul sama sekali?**
A: Check console untuk JavaScript errors, verifikasi modal HTML ada di index.php

**Q: Hanya melihat data dari sekolah lain?**
A: Ini security issue, contact administrator

**Q: Halaman loading lambat?**
A: Check database size, consider adding pagination

### Debug Resources
- `/QUICK_TEST_GUIDE.md` - Quick testing
- `/public/debug-stats-modal.html` - Interactive debug tool
- `/CREDENTIALS_EXPLAINED.md` - Technical details
- `/FIX_SESSION_CREDENTIALS.md` - Architecture deep dive

---

## 📜 License

Bagian dari Perpustakaan Online project.

---

## ✅ Checklist (What's Implemented)

- [x] Interactive cards dengan hover effects
- [x] Tooltips dengan deskripsi
- [x] Modal popup system
- [x] 4 API endpoints dengan auth
- [x] Session credential handling
- [x] Data table rendering
- [x] Status badges
- [x] Error handling
- [x] Responsive design
- [x] Debug tools
- [x] Complete documentation

---

**Status:** PRODUCTION READY ✅

Feature ini siap untuk digunakan di production. Semua komponen telah diimplementasi dan ditest.

