# Featured Sections - Dokumentasi Lengkap

## Overview

Halaman dashboard siswa sekarang menampilkan **4 featured sections** khusus yang menampilkan buku-buku dari kategori spesifik:

1. **📚 Fiksi** - Buku-buku cerita dan novel fiksi
2. **📖 Nonfiksi** - Buku-buku pembelajaran dan referensi nonfiksi
3. **🔍 Referensi** - Buku-buku referensi, kamus, dan panduan
4. **💭 Komik** - Buku-buku komik dan manga

---

## Fitur Utama

### ✅ Automatic Display
- Section hanya ditampilkan jika ada buku dalam kategori tersebut
- Jika kategori kosong, section tidak ditampilkan sama sekali

### ✅ Responsive Grid
- **Desktop (>1024px):** repeat(auto-fill, minmax(140px, 1fr)) - hingga 6 buku per baris
- **Tablet (768-1024px):** repeat(auto-fill, minmax(120px, 1fr)) - 4-5 buku per baris
- **Mobile (480-768px):** repeat(auto-fill, minmax(100px, 1fr)) - 3-4 buku per baris
- **Extra Small (<480px):** repeat(auto-fill, minmax(100px, 1fr)) - 2-3 buku per baris

### ✅ Rich Visual Design
- Section header dengan gradient background (accent-light)
- Icon emoji untuk setiap kategori
- Judul kategori yang prominent
- Counter buku di setiap section
- Hover effects pada book cards

### ✅ Smooth Animations
- **Section entrance:** fadeInUp animation 0.3s-0.6s
- **Book cards:** Staggered scaleIn dengan delays 0.3s-0.55s
- Setiap book card punya animation delay yang berbeda untuk cascade effect

### ✅ Full Functionality
- Tombol "Pinjam" untuk meminjam buku
- Link "Detail" untuk melihat detail buku
- Informasi lengkap: judul, pengarang, kategori, rating
- Status badge (Tersedia/Tidak Tersedia)

---

## Technical Implementation

### Backend (PHP)

```php
// 1. Define featured categories
$featured_categories = ['Fiksi', 'Nonfiksi', 'Referensi', 'Komik'];

// 2. Fetch books for each category (max 6 books per category)
$featured_books = [];
foreach ($featured_categories as $cat) {
    $stmt = $pdo->prepare('
        SELECT * FROM books 
        WHERE school_id = :school_id 
        AND category = :category 
        ORDER BY created_at DESC 
        LIMIT 6
    ');
    $stmt->execute(['school_id' => $school_id, 'category' => $cat]);
    $featured_books[$cat] = $stmt->fetchAll();
}
```

### Frontend (HTML/CSS/JavaScript)

**HTML Structure:**
```html
<!-- Featured Section -->
<div class="featured-section">
  <!-- Header with Icon, Title, and Book Count -->
  <div class="featured-section-header">
    <span class="featured-section-icon">📚</span>
    <h2 class="featured-section-title">Fiksi</h2>
    <span class="featured-section-subtitle">6 buku</span>
  </div>
  
  <!-- Books Grid -->
  <div class="featured-books-grid">
    <!-- Book cards looped from featured_books -->
  </div>
</div>
```

**CSS Animations:**
```css
.featured-section {
  animation: fadeInUp 0.6s ease-out 0.3s both;
}

.featured-books-grid .book-card {
  animation: scaleIn 0.5s ease-out backwards;
}

.featured-books-grid .book-card:nth-child(1) { animation-delay: 0.3s; }
.featured-books-grid .book-card:nth-child(2) { animation-delay: 0.35s; }
.featured-books-grid .book-card:nth-child(3) { animation-delay: 0.4s; }
/* ... dan seterusnya */
```

---

## Setup & Configuration

### 1. Database Requirement

Pastikan tabel `books` memiliki kolom `category`. Contoh struktur:

```sql
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    view_count INT DEFAULT 0,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

### 2. Insert Sample Data

Gunakan file `sql/sample_featured_sections.sql` untuk menambahkan sample data:

```sql
INSERT INTO books (school_id, title, author, category, created_at, view_count) 
VALUES (1, 'Harry Potter', 'J.K. Rowling', 'Fiksi', NOW(), 150);
```

Atau import langsung:
```bash
mysql -u username -p database_name < sql/sample_featured_sections.sql
```

### 3. File Locations

- **Main File:** `public/student-dashboard.php`
- **Documentation:** `FEATURED_SECTIONS.md` (file ini)
- **Sample Data:** `sql/sample_featured_sections.sql`

---

## Customization Guide

### Menambah/Mengubah Kategori

**Langkah 1:** Edit featured categories di `public/student-dashboard.php` (line ~65):
```php
$featured_categories = ['Fiksi', 'Nonfiksi', 'Referensi', 'Komik', 'Biografi'];
```

**Langkah 2:** Update icons mapping (line ~1155):
```php
$section_icons = [
    'Fiksi' => '📚',
    'Nonfiksi' => '📖',
    'Referensi' => '🔍',
    'Komik' => '💭',
    'Biografi' => '👤'
];
```

**Langkah 3:** Pastikan database memiliki buku dengan kategori baru

### Mengubah Jumlah Buku per Section

Edit LIMIT di line ~71:
```php
// Sebelum:
$stmt = $pdo->prepare('... LIMIT 6');

// Sesudah (ubah ke 8 buku):
$stmt = $pdo->prepare('... LIMIT 8');
```

### Mengubah Grid Columns

Edit CSS di featured-books-grid (line ~670):
```css
.featured-books-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 20px;
}
```

Ubah `minmax(140px, 1fr)` untuk mengubah lebar kolom:
- Lebih kecil = lebih banyak kolom
- Lebih besar = lebih sedikit kolom

### Mengubah Animasi

Edit delays di CSS (line ~680):
```css
.featured-books-grid .book-card:nth-child(1) { animation-delay: 0.3s; }
.featured-books-grid .book-card:nth-child(2) { animation-delay: 0.35s; }
/* ... */
```

---

## Browser Compatibility

| Browser | Status |
|---------|--------|
| Chrome | ✅ Fully supported |
| Firefox | ✅ Fully supported |
| Safari | ✅ Fully supported |
| Edge | ✅ Fully supported |
| IE 11 | ❌ Not supported (CSS Grid, modern animations) |

---

## Performance Notes

- **Database Queries:** 5 queries total
  1. `SELECT DISTINCT category` untuk filter sidebar
  2. 4 queries untuk featured books (1 per kategori) - maksimal 6 buku + all books query
  
- **Optimization Tips:**
  - Queries sudah menggunakan LIMIT 6 untuk featured sections
  - Index pada kolom `school_id` dan `category` sangat disarankan
  - All books di-load hanya sekali pada page load

- **Page Load Impact:**
  - Featured sections tidak menambah loading time signifikan
  - Recomended index: `INDEX (school_id, category)`

---

## Troubleshooting

### Featured Sections tidak muncul?
1. Pastikan ada buku di database dengan kategori yang sesuai
2. Check kategori di database (case-sensitive!)
3. Verify school_id yang benar

### Animasi tidak smooth?
1. Check browser support untuk CSS animations
2. Inspect DevTools untuk error messages
3. Pastikan CSS file di-load dengan benar

### Query terlalu lambat?
1. Add indexes pada `books` table:
   ```sql
   ALTER TABLE books ADD INDEX idx_school_category (school_id, category);
   ALTER TABLE books ADD INDEX idx_created_at (created_at DESC);
   ```
2. Check database performance dengan EXPLAIN

---

## Future Enhancements

- [ ] Drag & drop kategori untuk customize featured sections
- [ ] Admin panel untuk mengelola kategori featured
- [ ] Dynamic featured section order (admin setting)
- [ ] Load more functionality untuk setiap section
- [ ] Filter featured sections berdasarkan rating/popularity
- [ ] Custom section descriptions
- [ ] "Featured Book of the Week" highlight

---

## Files Modified

1. **public/student-dashboard.php**
   - Added featured books queries (lines 65-81)
   - Added featured sections CSS (lines 637-714)
   - Added featured sections HTML (lines 1155-1190)
   - Added responsive styles untuk featured sections

2. **New Files:**
   - `FEATURED_SECTIONS.md` - Documentation ini
   - `sql/sample_featured_sections.sql` - Sample data

---

## Support & Questions

Untuk informasi lebih lanjut atau customization khusus, silakan refer ke dokumentasi atau hubungi development team.

