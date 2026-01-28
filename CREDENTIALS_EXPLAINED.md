# 🔑 CREDENTIALS FLAG EXPLANATION

## The Problem in Depth

### What is a Cookie?
A cookie adalah small data file yang browser simpan. Ketika user login, server buat session dan simpan session ID di cookie. Browser otomatis kirim cookie ini dengan setiap request ke server yang sama.

### Normal Request Flow (Browser Navigation)
```
User: Click link ke halaman baru
  ↓
Browser: "GET /page" 
         (automatically include cookies)
  ↓
Server: Terima request + cookies
        Parse session dari cookie
        Check: User sudah login? ✅
        Return: Halaman untuk user
```

### AJAX Request Problem (Sebelum Fix)
```
JavaScript: fetch('/api/endpoint')
  ↓
Browser: Send request
         ❌ DON'T include cookies by default!
  ↓
Server: Terima request (tapi tanpa cookies)
        Parse session dari cookie
        Check: $_SESSION['user'] ada? ❌
        Action: Redirect ke login!
        Return: 302 Redirect response
  ↓
JavaScript: Dapat 302 response
            Parse sebagai error
            Display error kepada user
```

This adalah yang menyebabkan modal tidak muncul data!

---

## The Solution: `credentials: 'include'`

### What It Does
```javascript
fetch(url, {
    credentials: 'include'  // ← This flag!
})
```

Ini memberitahu browser: "Include cookies dengan AJAX request ini"

### AJAX Request Flow (Setelah Fix)
```
JavaScript: fetch('/api/endpoint', {credentials: 'include'})
  ↓
Browser: Send request
         ✅ INCLUDE cookies!
  ↓
Server: Terima request + cookies
        Parse session dari cookie
        Check: $_SESSION['user'] ada? ✅
        Query database
        Return: JSON response
  ↓
JavaScript: Dapat 200 response with JSON
            Parse JSON
            Display data kepada user
```

---

## Browser Security & CORS

### Why Not Always Include Cookies?
Jika browser selalu kirim cookies otomatis, ini bisa security risk (CSRF attack):

```
1. User logged in ke bank.com (punya cookie di browser)
2. User buka attacker.com di tab lain (tetap logged in ke bank)
3. attacker.com punya script: fetch('https://bank.com/transfer')
4. Jika cookies included otomatis → bisa hack bank account!
```

### Solution: Explicit Flag
Browser hanya include credentials jika:
1. Script explicitly set `credentials: 'include'`
2. Target URL is same-origin (same protocol, domain, port)

In our case:
- URL: `/perpustakaan-online/public/api/get-stats-books.php`
- Current page: `/perpustakaan-online/public/index.php`
- Same origin? ✅ YES (both http://localhost)
- Safe to include credentials? ✅ YES

---

## Implementation Detail

### Three Options for credentials

#### Option 1: `credentials: 'omit'` (default)
```javascript
fetch(url)  // Default behavior
// Same as fetch(url, { credentials: 'omit' })

// RESULT: Cookies NOT sent ❌
```

#### Option 2: `credentials: 'same-origin'`
```javascript
fetch(url, {
    credentials: 'same-origin'
})

// RESULT: Cookies sent ONLY if same-origin
// Good for security, but less compatible
```

#### Option 3: `credentials: 'include'` (Our Choice)
```javascript
fetch(url, {
    credentials: 'include'
})

// RESULT: Cookies sent always (even cross-origin in some cases)
// Less strict, better compatibility
// Safe because target is trusted endpoint
```

### Why We Chose `include`?
- ✅ Works reliably across browsers
- ✅ Our API is same-origin (safe)
- ✅ All modern browsers support
- ✅ No CORS config needed

---

## Real Example: Our Implementation

### Before Fix
```javascript
// stats-modal.js line 97 (BROKEN)
const response = await fetch(url);
// Result: Server gets no session → 302 redirect
```

Endpoint receives no session:
```php
// get-stats-books.php
requireAuth();  // Checks $_SESSION['user']
// $_SESSION is empty because cookie not sent!
// → Redirects to login → AJAX fails
```

### After Fix
```javascript
// stats-modal.js line 97 (WORKING)
const response = await fetch(url, {
    credentials: 'include',
    method: 'GET'
});
// Result: Server gets session via cookie → returns JSON
```

Endpoint receives session:
```php
// get-stats-books.php
requireAuth();  // Checks $_SESSION['user']
// $_SESSION has user because cookie included!
// → Continue with query → Return JSON success
```

---

## Debugging Credentials Issues

### How to Check if Cookies Sent

#### Method 1: Network Tab (F12)
1. Open Dashboard
2. Press F12 → Network tab
3. Click card to trigger AJAX
4. Find request to `get-stats-books.php`
5. Click request → look for "Cookie" header
   - ✅ Present = Credentials sent
   - ❌ Missing = Credentials not sent

#### Method 2: Server Log
Add logging in endpoint:
```php
// In get-stats-books.php (line 5)
error_log('Session data: ' . json_encode($_SESSION));
```

Check if `$_SESSION['user']` logged (means received)

#### Method 3: Response Status
Look in Network tab:
- Status 200 + JSON response = Credentials working ✅
- Status 302 + Location header = No credentials ❌
- Status 500 = Query error (credentials OK, database issue)

---

## Common Credentials Issues & Fixes

### Issue 1: CORS Errors
```
Error: "Blocked by CORS policy"
Reason: Cross-origin request without credentials
Fix: Set credentials: 'include'
```

### Issue 2: Session Lost
```
Error: "Session not found" or "Login required"
Reason: Cookies not sent with request
Fix: Add credentials: 'include'
```

### Issue 3: Cookie Not Sent (Same-origin)
```
Error: Still getting 302 after adding credentials
Reason: Path might be wrong
Fix: Verify URL is correct and accessible
```

### Issue 4: Cookie Expired
```
Error: "Login required" even with credentials: 'include'
Reason: Session cookie expired
Fix: User needs to logout and login again
```

---

## Best Practices

### ✅ DO:
- Include credentials for same-origin API calls
- Use absolute paths to avoid ambiguity
- Check Network tab when debugging
- Add console logging for requests

### ❌ DON'T:
- Forget credentials flag for auth-required endpoints
- Use relative paths (ambiguous)
- Ignore Network/Console tabs
- Assume cookies always sent

---

## Related Reading

### Fetch API Specification
- MDN: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API
- credentials options: https://fetch.spec.whatwg.org/#concept-http-credentials-mode

### CORS & Security
- MDN CORS: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
- Same-origin policy: https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy

### PHP Session Handling
- PHP Session docs: https://www.php.net/manual/en/book.session.php
- Session security: https://www.php.net/manual/en/session.security.php

---

## Summary

| Aspect | Detail |
|--------|--------|
| Problem | AJAX requests don't send cookies by default |
| Impact | Server can't find session → denies access |
| Solution | Add `credentials: 'include'` to fetch options |
| Security | Safe because we control both request and endpoint |
| Browser Support | All modern browsers (IE11+) |
| Error Sign | 302 redirect or "unauthorized" response |
| Debug Method | F12 Network tab → check Cookie header |

---

**Key Takeaway:** Always use `credentials: 'include'` when fetching from API endpoints that require session authentication!

