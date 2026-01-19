# 🎨 Design Quick Reference Guide

## Featured Sections - Visual Design Guide

---

## 🎯 Color Palette

### Primary Colors
```css
--accent:        #0B3D61 (Navy Blue)
--accent-light:  #E0F2FE (Sky Blue)
--text:          #0F1724 (Dark Gray)
--muted:         #6B7280 (Gray)
--bg:            #F8FAFC (Light Gray)
--card:          #FFFFFF (White)
--border:        #E2E8F0 (Light Border)
```

### Status Colors
```css
--success:   #10B981 (Green)  - Available
--danger:    #EF4444 (Red)    - Unavailable
--warning:   #F59E0B (Amber)  - Limited
```

### Book Cover Gradients
```css
Gradient 1: #667eea → #764ba2 (Purple)
Gradient 2: #f093fb → #f5576c (Pink-Red)
Gradient 3: #4facfe → #00f2fe (Cyan)

Applied: nth-child pattern (3n+1, 3n+2, 3n)
```

---

## 📐 Spacing System

### Featured Section Header
```
Padding:        24px 28px
Gap:            16px
Margin-bottom:  28px
Border-radius:  16px
Border-left:    6px
```

### Book Card Info
```
Padding:        18px
Gap:            10px
Border-radius:  12px
Border:         1px solid rgba(11, 61, 97, 0.06)
```

### Book Buttons
```
Padding:        11px 12px
Gap:            8px
Border-radius:  8px
Font-size:      12px
Font-weight:    700
```

### Status Badge
```
Padding:        6px 14px
Border-radius:  20px
Font-size:      10px
Font-weight:    700
Letter-spacing: 0.6px
```

---

## 🔤 Typography

### Section Header
```css
Font Size:      22px
Font Weight:    700
Color:          var(--text)
Margin-bottom:  0
```

### Book Title
```css
Font Size:      14px
Font Weight:    800 (bold!)
Color:          var(--text)
Line Height:    1.5
Letter Spacing: -0.3px
Clamp:          2 lines max
```

### Book Author
```css
Font Size:      12px
Color:          var(--muted)
Font Weight:    500
Overflow:       ellipsis
White Space:    nowrap
```

### Book Category
```css
Font Size:      11px
Color:          var(--accent)
Font Weight:    600
Text Transform: uppercase
Letter Spacing: 0.5px
```

### Book Rating
```css
Font Size:      13px
Color:          var(--text)
Font Weight:    500
Background:     linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05))
```

---

## 🎭 Shadows & Effects

### Shadow Levels
```css
Subtle:     0 2px 8px rgba(11, 61, 97, 0.15)
Medium:     0 4px 12px rgba(11, 61, 97, 0.3)
Elevated:   0 8px 20px rgba(11, 61, 97, 0.4)
Dark:       0 16px 32px rgba(0, 0, 0, 0.12)
```

### Applied To:
```
Subtle:   Icon containers, subtle effects
Medium:   Section headers, default state
Elevated: Buttons, active hover
Dark:     Book cards on strong hover
```

### Backdrop Filters
```css
Status Badge:  backdrop-filter: blur(8px)
Opacity:       0.95 (semi-transparent)
```

---

## ✨ Hover & Active States

### Book Card Hover
```css
Transform:  translateY(-8px) scale(1.02)
Shadow:     0 16px 32px rgba(0, 0, 0, 0.12)
Border:     accent color
Transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1)
```

### Book Cover Hover
```css
Transform: scale(1.08)
```

### Pinjam Button Hover
```css
Transform:   translateY(-2px)
Shadow:      0 8px 20px rgba(11, 61, 97, 0.4)
Gradient:    reversed
Animation:   shimmer effect (::before)
```

### Detail Button Hover
```css
Transform:     translateY(-2px)
Background:    var(--accent-light)
Shadow:        0 6px 16px rgba(11, 61, 97, 0.2)
```

---

## 📱 Responsive Sizing

### Featured Section Header

| Breakpoint | Padding | Icon Size | Title Size |
|-----------|---------|-----------|-----------|
| Desktop   | 24px 28px | 56px | 22px |
| Tablet    | 20px 20px | 48px | 18px |
| Mobile    | 18px 18px | 44px | 16px |
| Extra Small | 16px | 40px | 15px |

### Book Grid

| Breakpoint | Column Width | Gap | Books/Row |
|-----------|-------------|-----|-----------|
| Desktop   | minmax(145px, 1fr) | 24px | 5-6 |
| Tablet    | minmax(120px, 1fr) | 18px | 4-5 |
| Mobile    | minmax(100px, 1fr) | 14px | 3-4 |
| Extra Small | minmax(100px, 1fr) | 12px | 2-3 |

### Book Cover Height

| Breakpoint | Height |
|-----------|--------|
| Desktop   | 220px |
| Tablet    | 220px |
| Mobile    | 160px |
| Extra Small | 140px |

---

## 🎬 Animation Timing

### Staggered Delays
```css
Book Card 1:  0.35s
Book Card 2:  0.40s
Book Card 3:  0.45s
Book Card 4:  0.50s
Book Card 5:  0.55s
Book Card 6:  0.60s

Pattern: 50ms increment
```

### Transition Timing
```css
Default:  all 0.3s ease
Smooth:   all 0.3s cubic-bezier(0.4, 0, 0.2, 1)
Quick:    0.2s ease
Slow:     0.6s ease-out
```

### Animation Types
```css
slideDown:    from -30px translateY
slideInLeft:  from -40px translateX
slideInRight: from 40px translateX
fadeInUp:     from 30px translateY
scaleIn:      from 0.95 scale
```

---

## 🔘 Button Styles

### Pinjam Button (Primary)
```css
Background:  linear-gradient(135deg, accent → darker)
Color:       white
Shadow:      0 4px 12px rgba(11, 61, 97, 0.3)
Hover:       deeper gradient + shadow
Active:      pressed effect
Effects:     shimmer animation on hover
```

### Detail Button (Secondary)
```css
Background:  var(--bg)
Color:       var(--accent)
Border:      2px solid var(--accent)
Hover:       filled with accent-light
Effects:     smooth fill animation
```

---

## 🏷️ Badge Styles

### Status Badge
```css
Available:    rgba(16, 185, 129, 0.95)
Unavailable:  rgba(239, 68, 68, 0.95)
Limited:      rgba(245, 158, 11, 0.95)

Effects:      backdrop-blur(8px), shadow
```

### Book Count Badge
```css
Background:  var(--card)
Color:       var(--muted)
Styling:     padding 6px 14px, border-radius 20px
```

### Category Badge
```css
Color:       var(--accent)
Weight:      600
Text:        UPPERCASE
Letter Spacing: 0.5px
```

---

## 🎨 Gradient Reference

### Section Header
```css
linear-gradient(
  135deg,
  var(--accent-light) 0%,
  rgba(224, 242, 254, 0.5) 100%
)
```

### Book Covers (3 schemes)
```css
Scheme 1: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
Scheme 2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)
Scheme 3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)
```

### Rating Badge
```css
linear-gradient(
  135deg,
  rgba(245, 158, 11, 0.1) 0%,
  rgba(245, 158, 11, 0.05) 100%
)
```

### Divider Lines
```css
Left:  linear-gradient(to right, transparent, var(--accent), transparent)
Right: linear-gradient(to right, var(--accent), transparent)
```

---

## 📊 Border Styles

### Featured Section Header
```css
Border-left: 6px solid var(--accent)
Border-radius: 16px
```

### Book Card
```css
Border: 1px solid rgba(11, 61, 97, 0.06)
Border-radius: 12px
```

### Status Badge
```css
Border-radius: 20px
```

### Buttons
```css
Border-radius: 8px
```

---

## ⚙️ Transition Properties

### Smooth Transition
```css
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1)
```

### Quick Transition
```css
transition: 0.2s ease
```

### Slow Transition
```css
transition: 0.6s ease-out
```

---

## 🎯 Visual Hierarchy

```
Level 1: Featured Section
Level 2: Section Header (with icon badge)
Level 3: Book Grid Container
Level 4: Individual Book Card
Level 5: Book Details (title, author)
Level 6: Book Metadata (category, rating)
Level 7: Actions (buttons)
```

---

## ✅ Quality Checklist

- ✅ Consistent color usage
- ✅ Proper spacing ratios
- ✅ Smooth transitions
- ✅ Clear visual hierarchy
- ✅ Responsive breakpoints
- ✅ Accessible contrast
- ✅ Professional shadows
- ✅ Modern gradients
- ✅ Interactive feedback
- ✅ Performance optimized

---

## 🎓 Design Principles

1. **Clarity** - Clear visual hierarchy
2. **Consistency** - Unified design language
3. **Feedback** - Interactive responses
4. **Efficiency** - Quick scan-able design
5. **Aesthetics** - Modern, polished look
6. **Accessibility** - Readable for all
7. **Performance** - Fast, smooth
8. **Responsiveness** - All device sizes

---

## 📞 Quick Reference Links

- Full Details: `DESIGN_IMPROVEMENTS.md`
- Before/After: `BEFORE_AFTER_DESIGN.md`
- Features: `FEATURED_SECTIONS.md`
- Visual Layout: `VISUAL_GUIDE.md`
- Implementation: `student-dashboard.php`

---

**This is your design system reference guide for the featured sections!** 🎨

Use this guide for:
- Consistency checks
- Future enhancements
- Design updates
- Developer reference
- Designer communication
