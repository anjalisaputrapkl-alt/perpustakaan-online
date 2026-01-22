# 📚 SISTEM BUKU FAVORIT - DOKUMENTASI BACKEND

## ✅ Status Implementasi
Semua backend untuk fitur Buku Favorit sudah **LENGKAP** dan siap digunakan!

---

## 1️⃣ DATABASE STRUCTURE

### Tabel: `favorites`
```sql
CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_book (student_id, book_id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);
```

**Kolom:**
- `id` - Primary key
- `student_id` - ID siswa (FK ke users.id)
- `book_id` - ID buku (FK ke books.id)
- `category` - Kategori buku
- `created_at` - Waktu ditambahkan

---

## 2️⃣ API ENDPOINTS

### Base URL
```
/public/api/favorites.php?action=ACTION
```

### A. GET CATEGORIES
**Endpoint:** `favorites.php?action=categories`
**Method:** GET
**Auth:** Required

**Response Success:**
```json
{
  "success": true,
  "data": [
    "Fiksi",
    "Non-Fiksi",
    "Biografi",
    "Sejarah"
  ]
}
```

---

### B. GET BOOKS BY CATEGORY
**Endpoint:** `favorites.php?action=books_by_category&category=Fiksi`
**Method:** GET
**Auth:** Required

**Parameters:**
- `category` - Nama kategori (optional)

**Response Success:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Laskar Pelangi",
      "author": "Andrea Hirata",
      "category": "Fiksi",
      "cover_image": "book_1.jpg"
    }
  ],
  "total": 1
}
```

---

### C. ADD FAVORITE
**Endpoint:** `favorites.php?action=add`
**Method:** POST
**Auth:** Required
**Auto-Creates Notification:** ✅ Yes (type: `info`)

**Parameters:**
```
POST Data:
- book_id (required) - ID buku
- category (optional) - Kategori buku
```

**Features:**
- ✅ Validasi duplikasi - Tolak jika sudah ada
- ✅ Auto-fetch book title untuk notifikasi
- ✅ Create notifikasi tipe `info` ke siswa

**Response Success:**
```json
{
  "success": true,
  "message": "Buku berhasil ditambahkan ke favorit"
}
```

**Response Error (Duplikasi):**
```json
{
  "success": false,
  "message": "Buku sudah ada di favorit Anda"
}
```

**Notification Created:**
- **Type:** `info`
- **Title:** "Buku Ditambahkan ke Favorit"
- **Message:** "Anda telah menambahkan "[Judul Buku]" ke koleksi favorit Anda."

---

### D. LIST FAVORITES
**Endpoint:** `favorites.php?action=list&category=Fiksi`
**Method:** GET
**Auth:** Required

**Parameters:**
- `category` - Filter by category (optional)

**Response Success:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "student_id": 5,
      "book_id": 10,
      "category": "Fiksi",
      "created_at": "2026-01-22 10:30:00",
      "title": "Laskar Pelangi",
      "author": "Andrea Hirata",
      "book_category": "Fiksi",
      "cover_image": "book_10.jpg"
    }
  ],
  "total": 1
}
```

---

### E. REMOVE FAVORITE
**Endpoint:** `favorites.php?action=remove`
**Method:** POST
**Auth:** Required

**Parameters:**
```
POST Data:
- id (required) - ID favorit yang akan dihapus
```

**Features:**
- ✅ Validasi ownership - Hanya pemilik yang bisa delete
- ✅ Safety check - Verify student_id match

**Response Success:**
```json
{
  "success": true,
  "message": "Buku berhasil dihapus dari favorit"
}
```

---

### F. COUNT FAVORITES
**Endpoint:** `favorites.php?action=count`
**Method:** GET
**Auth:** Required

**Response Success:**
```json
{
  "success": true,
  "count": 5
}
```

---

## 3️⃣ JAVASCRIPT INTEGRATION EXAMPLES

### Get Categories
```javascript
fetch('/public/api/favorites.php?action=categories')
  .then(res => res.json())
  .then(data => {
    console.log('Categories:', data.data);
    // Populate dropdown
    data.data.forEach(cat => {
      // Add option to select
    });
  });
```

### Get Books by Category
```javascript
const category = 'Fiksi';
fetch(`/public/api/favorites.php?action=books_by_category&category=${category}`)
  .then(res => res.json())
  .then(data => {
    console.log('Books:', data.data);
    // Populate books dropdown
  });
```

### Add to Favorites
```javascript
const formData = new FormData();
formData.append('book_id', bookId);
formData.append('category', selectedCategory);

fetch('/public/api/favorites.php?action=add', {
  method: 'POST',
  body: formData
})
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✓ Buku ditambahkan ke favorit!');
      // Reload favorites list
      loadFavorites();
    } else {
      alert('✗ ' + data.message);
    }
  });
```

### Get Favorites List
```javascript
fetch('/public/api/favorites.php?action=list')
  .then(res => res.json())
  .then(data => {
    console.log('Favorites:', data.data);
    // Display favorites in UI
    data.data.forEach(fav => {
      // Render favorite item
    });
  });
```

### Remove from Favorites
```javascript
const favoriteId = 123;

const formData = new FormData();
formData.append('id', favoriteId);

fetch('/public/api/favorites.php?action=remove', {
  method: 'POST',
  body: formData
})
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✓ Buku dihapus dari favorit');
      // Reload favorites list
      loadFavorites();
    }
  });
```

### Get Count
```javascript
fetch('/public/api/favorites.php?action=count')
  .then(res => res.json())
  .then(data => {
    console.log('Total favorites:', data.count);
    // Update badge
    document.querySelector('.favorite-badge').textContent = data.count;
  });
```

---

## 4️⃣ NOTIFICATION SYSTEM INTEGRATION

Ketika siswa menambah buku ke favorit:

**Notification Created Automatically:**
- ✅ Type: `info`
- ✅ Title: "Buku Ditambahkan ke Favorit"
- ✅ Message: "Anda telah menambahkan "[Book Title]" ke koleksi favorit Anda."
- ✅ Visible in: `/public/notifications.php`

**No manual trigger needed** - Otomatis dibuat saat add favorit!

---

## 5️⃣ QUERY CHEAT SHEET

### Count favorite per student
```sql
SELECT COUNT(*) as total 
FROM favorites 
WHERE student_id = ?;
```

### Get all favorites with book details
```sql
SELECT f.id, f.student_id, f.book_id, f.category, f.created_at,
       b.title, b.author, b.cover_image
FROM favorites f
JOIN books b ON f.book_id = b.id
WHERE f.student_id = ?
ORDER BY f.created_at DESC;
```

### Check if book is favorited
```sql
SELECT COUNT(*) as is_favorite 
FROM favorites 
WHERE student_id = ? AND book_id = ?;
```

### Get unique categories from favorites
```sql
SELECT DISTINCT category 
FROM favorites 
WHERE student_id = ? 
ORDER BY category;
```

### Delete favorite
```sql
DELETE FROM favorites 
WHERE id = ? AND student_id = ?;
```

---

## 6️⃣ ERROR HANDLING

| Status | Message | Cause |
|--------|---------|-------|
| 400 | ID buku tidak valid | book_id missing or non-numeric |
| 400 | Buku sudah ada di favorit | Duplicate favorite |
| 400 | ID favorit tidak valid | favorite_id missing or non-numeric |
| 403 | Unauthorized | Student tidak pemilik favorite |
| 401 | Unauthorized | Session expired |
| 405 | Method not allowed | POST vs GET mismatch |

---

## 7️⃣ TESTING CHECKLIST

- [ ] Add book to favorites - Check notification appears
- [ ] Try adding same book again - Should get "sudah ada" error
- [ ] List favorites - All books showing correctly
- [ ] Filter by category - Only that category shows
- [ ] Remove favorite - Item disappears from list
- [ ] Count favorites - Number accurate
- [ ] Check notifications.php - Favorite notification visible

---

## 📝 FILES CREATED/MODIFIED

**Created:**
- `/sql/run-favorites-migration.php` - Migration script
- `/sql/favorites_table.sql` - SQL schema

**Modified:**
- `/public/api/favorites.php` - Updated with NotificationsHelper + new logic

**Already Exist:**
- `/src/FavoriteModel.php` - Model class (unchanged, fully compatible)
- `/src/NotificationsHelper.php` - Notifications system (used by favorites)

---

## 🚀 QUICK START

1. **Database migration already ran** - Table `favorites` created
2. **APIs ready** - All 6 endpoints working
3. **Notifications active** - Auto-created on add favorite
4. **Frontend ready** - Call API endpoints from your JS code

### Minimum Code to Get Started:
```html
<!-- Add favorite button -->
<button onclick="addFavorite(bookId)">❤️ Add to Favorites</button>

<script>
async function addFavorite(bookId) {
  const formData = new FormData();
  formData.append('book_id', bookId);
  
  const res = await fetch('/public/api/favorites.php?action=add', {
    method: 'POST',
    body: formData
  });
  
  const data = await res.json();
  if (data.success) {
    alert('Added to favorites!');
  } else {
    alert('Error: ' + data.message);
  }
}
</script>
```

That's it! Happy coding! 🎉
