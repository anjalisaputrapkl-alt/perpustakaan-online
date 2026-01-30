# Interactive Collapsible Statistics Cards - Quick Start Guide

## What Changed?

Your statistics section on the **Riwayat Peminjaman** page now has beautiful, interactive cards that you can click to expand!

## Visual Layout

### Desktop View (4 columns)
```
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ Total Peminjaman│ │ Sedang Dipinjam │ │ Sudah Dikembali │ │ Telat Dikembali │
│      12         │ │        3        │ │        8        │ │        1        │
│        ▼        │ │        ▼        │ │        ▼        │ │        ▼        │
└─────────────────┘ └─────────────────┘ └─────────────────┘ └─────────────────┘
```

### Tablet View (2 columns)
```
┌─────────────────┐ ┌─────────────────┐
│ Total Peminjaman│ │ Sedang Dipinjam │
│      12         │ │        3        │
└─────────────────┘ └─────────────────┘
┌─────────────────┐ ┌─────────────────┐
│ Sudah Dikembali │ │ Telat Dikembali │
│        8        │ │        1        │
└─────────────────┘ └─────────────────┘
```

### Mobile View (1 column)
```
┌─────────────────┐
│ Total Peminjaman│
│      12         │
└─────────────────┘
┌─────────────────┐
│ Sedang Dipinjam │
│        3        │
└─────────────────┘
```

## How to Use

### 1. Click on a Stat Card
Click on any of the 4 statistic cards:
- Total Peminjaman
- Sedang Dipinjam
- Sudah Dikembalikan
- Telat Dikembalikan

### 2. See Expanded Details
When you click, the card expands downward showing:
- 📚 Book cover image
- 📖 Title and author
- 📅 Borrow and due dates
- 🏷️ Status badge (color-coded)

### 3. Hover Effects
When hovering over a book in the expanded list:
- Background changes to a light blue
- Card slides slightly to the right
- Shows you can interact with it

### 4. Click Again to Collapse
Click the card again (or the chevron icon) to collapse the details.

## Features

✨ **Smooth Animations**
- Cards expand and collapse smoothly
- Chevron icon rotates 180°
- Subtle hover effects

🎨 **Color-Coded**
- Total: Blue (`#3A7FF2`)
- Sedang Dipinjam: Amber (`#f59e0b`)
- Sudah Dikembalikan: Green (`#10B981`)
- Telat Dikembalikan: Red (`#EF4444`)

📱 **Fully Responsive**
- Looks great on desktop, tablet, and mobile
- Grid adapts to screen size
- Touch-friendly on mobile devices

📊 **Smart Filtering**
Each card shows ONLY the books for that category:
- "Total Peminjaman" = All books
- "Sedang Dipinjam" = Books with status `borrowed`
- "Sudah Dikembalikan" = Books with status `returned`
- "Telat Dikembalikan" = Books with status `overdue`

## Code Structure

### Files Modified
1. **`public/student-borrowing-history.php`**
   - Replaced old `.stats-grid` HTML
   - Added new `.stats-grid-interactive` with 4 interactive cards
   - Added `toggleStatDetail()` JavaScript function

2. **`assets/css/student-borrowing-history.css`**
   - Added 150+ lines of new CSS for interactive cards
   - Added responsive styles for tablet and mobile
   - Added smooth animations and transitions

### No Changes To:
- Database queries
- Backend logic
- Other pages or functionality
- Data structure

## Technical Details

### CSS Classes (Key)
- `.stats-grid-interactive` - Main container
- `.stat-card-interactive` - Individual card
- `.stat-card-header` - Card header with value
- `.stat-card-detail` - Expandable detail section
- `.stat-detail-item` - Individual book item in list

### JavaScript Function
```javascript
toggleStatDetail(card, type)
```
- Toggles the `.expanded` class
- Animates `max-height` and `opacity`
- Rotates the chevron icon

## Browser Support
✅ Chrome 90+  
✅ Firefox 88+  
✅ Safari 14+  
✅ Edge 90+  
✅ Mobile browsers (iOS Safari, Chrome Android)

## Customization

Want to change the colors?
- Edit CSS variables in `:root`
- Modify color values in `.stat-card-value` classes

Want to change animation speed?
- Find `transition: all 0.4s cubic-bezier(...)` in CSS
- Change `0.4s` to desired duration

Want to modify the detail list layout?
- Edit `.stat-detail-item` flexbox properties
- Adjust width, gap, or padding values

---

**Created:** Interactive Collapsible Statistics Cards  
**Updated:** Riwayat Peminjaman (Borrowing History) Page  
**Version:** 1.0  
**Status:** ✅ Production Ready
