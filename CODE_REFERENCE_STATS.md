# Complete Code Reference - Interactive Stats Cards

## 1. HTML Structure (student-borrowing-history.php)

### Container with 4 Interactive Cards

```html
<!-- Statistik Cards - Interactive Expandable -->
<div class="stats-grid-interactive">
    <!-- Total Peminjaman -->
    <div class="stat-card-interactive" onclick="toggleStatDetail(this, 'all')">
        <div class="stat-card-header">
            <div>
                <div class="stat-card-label">Total Peminjaman</div>
                <div class="stat-card-value"><?php echo $totalBooks; ?></div>
            </div>
            <div class="stat-card-chevron">
                <iconify-icon icon="mdi:chevron-down" width="20" height="20"></iconify-icon>
            </div>
        </div>
        <div class="stat-card-detail">
            <div class="stat-detail-content">
                <!-- All books in list format -->
                <div class="stat-detail-list">
                    <?php foreach ($allBooks as $item): ?>
                        <div class="stat-detail-item">
                            <div class="detail-item-cover">
                                <!-- Book cover image or placeholder -->
                            </div>
                            <div class="detail-item-info">
                                <!-- Book title, author, dates -->
                            </div>
                            <div class="detail-item-status">
                                <!-- Status badge -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sedang Dipinjam, Sudah Dikembalikan, Telat Dikembalikan (same structure) -->
    ...
</div>
```

### Individual Book Item Structure

```html
<div class="stat-detail-item">
    <!-- Cover Image -->
    <div class="detail-item-cover">
        <?php if (!empty($item['cover_image'])): ?>
            <img src="<?php echo htmlspecialchars('../img/covers/' . $item['cover_image']); ?>" alt="...">
        <?php else: ?>
            <div class="cover-placeholder">
                <iconify-icon icon="mdi:book" width="24" height="24"></iconify-icon>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Book Information -->
    <div class="detail-item-info">
        <h4><?php echo htmlspecialchars($item['book_title'] ?? '-'); ?></h4>
        <p class="detail-author">
            <iconify-icon icon="mdi:pen" width="14" height="14"></iconify-icon>
            <?php echo htmlspecialchars($item['author'] ?? '-'); ?>
        </p>
        <div class="detail-dates">
            <span>Pinjam: <?php echo formatDate($item['borrowed_at']); ?></span>
            <span>Kembali: <?php echo formatDate($item['returned_at']); ?></span>
        </div>
    </div>
    
    <!-- Status Badge -->
    <div class="detail-item-status">
        <span class="badge badge-returned">Dikembalikan</span>
    </div>
</div>
```

---

## 2. CSS Styling (student-borrowing-history.css)

### Main Container

```css
.stats-grid-interactive {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
```

### Card Base Styles

```css
.stat-card-interactive {
    background: var(--card);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    animation: fadeInUp 0.6s ease-out;
}

.stat-card-interactive:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(58, 127, 242, 0.1);
}

.stat-card-interactive.expanded {
    box-shadow: 0 8px 24px rgba(58, 127, 242, 0.15);
}
```

### Card Header

```css
.stat-card-header {
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: linear-gradient(135deg, var(--muted) 0%, transparent 100%);
    border-bottom: 1px solid var(--border);
}

.stat-card-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    font-weight: 600;
}

.stat-card-value {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary);
}
```

### Color-Coded Values

```css
.stat-card-interactive[onclick*="'borrowed'"] .stat-card-value {
    color: var(--warning);  /* Amber */
}

.stat-card-interactive[onclick*="'returned'"] .stat-card-value {
    color: var(--success);  /* Green */
}

.stat-card-interactive[onclick*="'overdue'"] .stat-card-value {
    color: var(--danger);   /* Red */
}
```

### Chevron Icon

```css
.stat-card-chevron {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: rgba(58, 127, 242, 0.08);
    color: var(--primary);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.stat-card-interactive.expanded .stat-card-chevron {
    background: rgba(58, 127, 242, 0.2);
    /* Rotation handled by JS */
}
```

### Detail Section (Expandable)

```css
.stat-card-detail {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
}

.stat-card-interactive.expanded .stat-card-detail {
    opacity: 1;
}

.stat-detail-content {
    padding: 20px;
    border-top: 1px solid var(--border);
}
```

### Book List Items

```css
.stat-detail-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-detail-item {
    display: flex;
    gap: 16px;
    padding: 14px;
    background: var(--muted);
    border-radius: 10px;
    transition: all 0.2s ease;
}

.stat-detail-item:hover {
    background: rgba(58, 127, 242, 0.08);
    transform: translateX(4px);
}
```

### Book Cover

```css
.detail-item-cover {
    flex-shrink: 0;
    width: 60px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--card);
    border: 1px solid var(--border);
}

.detail-item-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cover-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--accent) 0%, #062d4a 100%);
    color: white;
}
```

### Book Information

```css
.detail-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.detail-item-info h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.4;
}

.detail-author {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
}

.detail-dates {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 11px;
    color: var(--text-muted);
}
```

### Empty State

```css
.stat-detail-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px 20px;
    text-align: center;
    color: var(--text-muted);
}

.stat-detail-empty iconify-icon {
    opacity: 0.3;
}

.stat-detail-empty p {
    margin: 0;
    font-size: 13px;
}
```

### Responsive Styles (768px)

```css
@media (max-width: 768px) {
    .stats-grid-interactive {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}
```

### Mobile Styles (480px)

```css
@media (max-width: 480px) {
    .stats-grid-interactive {
        grid-template-columns: 1fr;
    }
}
```

---

## 3. JavaScript (student-borrowing-history.php)

### Toggle Function

```javascript
/**
 * Toggle stat detail expand/collapse
 * Smooth animation untuk membuka/tutup detail statistik
 */
function toggleStatDetail(card, type) {
    const isExpanded = card.classList.contains('expanded');
    const detail = card.querySelector('.stat-card-detail');
    const chevron = card.querySelector('.stat-card-chevron');
    
    if (isExpanded) {
        // Close
        card.classList.remove('expanded');
        detail.style.maxHeight = '0';
        detail.style.opacity = '0';
        detail.style.overflow = 'hidden';
        chevron.style.transform = 'rotate(0deg)';
    } else {
        // Open
        card.classList.add('expanded');
        detail.style.maxHeight = detail.scrollHeight + 'px';
        detail.style.opacity = '1';
        detail.style.overflow = 'visible';
        chevron.style.transform = 'rotate(180deg)';
    }
}
```

### How It Works

1. **Check Current State**: Looks for `.expanded` class
2. **Get Elements**: Finds the detail section and chevron icon
3. **On Close**:
   - Remove `.expanded` class
   - Set `max-height` to 0
   - Fade out with `opacity: 0`
   - Rotate chevron back to 0°

4. **On Open**:
   - Add `.expanded` class
   - Set `max-height` to current `scrollHeight` (auto-height)
   - Fade in with `opacity: 1`
   - Rotate chevron 180°

---

## 4. Data Processing (PHP)

### Filtering Books by Status

```php
<?php 
// Total Peminjaman - All books
$allBooks = $borrowingHistory;

// Sedang Dipinjam - Only borrowed books
$borrowedBooksList = array_filter($borrowingHistory, fn($b) => $b['status'] === 'borrowed');

// Sudah Dikembalikan - Only returned books
$returnedBooksList = array_filter($borrowingHistory, fn($b) => $b['status'] === 'returned');

// Telat Dikembalikan - Only overdue books
$overdueBooksList = array_filter($borrowingHistory, fn($b) => $b['status'] === 'overdue');
?>
```

### Date Formatting

```php
<?php
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d M Y H:i', strtotime($date));
}
?>
```

---

## 5. Color Variables

```css
:root {
    --primary: #3A7FF2;          /* Blue - Total */
    --warning: #f59e0b;          /* Amber - Sedang Dipinjam */
    --success: #10B981;          /* Green - Sudah Dikembalikan */
    --danger: #EF4444;           /* Red - Telat Dikembalikan */
    
    --card: #FFFFFF;             /* Card background */
    --bg: #F6F9FF;               /* Page background */
    --muted: #F3F7FB;            /* Muted background */
    --border: #E6EEF8;           /* Border color */
    --text: #0F172A;             /* Text color */
    --text-muted: #50607A;       /* Muted text */
}
```

---

## Summary

This implementation provides:
- ✅ 4 interactive stat cards
- ✅ Smooth expand/collapse animations
- ✅ Responsive grid layout
- ✅ Color-coded statistics
- ✅ Detailed book listings
- ✅ Empty state handling
- ✅ Hover effects
- ✅ Mobile-friendly design

**Total Lines Added:**
- HTML: ~300 lines
- CSS: ~150 lines
- JavaScript: ~30 lines
