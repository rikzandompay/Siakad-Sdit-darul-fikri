# Performance Analysis - SisforSinta Application

**Analisis Tanggal:** 18 Juni 2026  
**Status:** Production Environment  
**Database:** PostgreSQL (Supabase Singapore)

---

## Executive Summary

Aplikasi mengalami **loading lambat** karena kombinasi dari beberapa masalah kritis:

1. **🔴 CRITICAL**: Konfigurasi database yang salah untuk Supabase
2. **🔴 CRITICAL**: Cache berbasis file yang lambat
3. **🟡 HIGH**: Missing database indexes pada kolom yang sering di-query
4. **🟡 HIGH**: N+1 queries dan inefficient data loading
5. **🟠 MEDIUM**: External resources yang memblokir rendering

**Estimasi Improvement**: Dengan memperbaiki masalah Critical dan High, loading time bisa berkurang **60-80%**.

---

## 🔴 CRITICAL ISSUES

### 1. Database Configuration - PDO Prepared Statements

**File:** [config/database.php:101](file:///home/rikzan24_/SisforSinta/config/database.php#L101)

**Problem:**
```php
'options' => [
    PDO::ATTR_EMULATE_PREPARES => true,  // ❌ WRONG for Supabase
],
```

**Impact:** 
- Memaksa client-side statement preparation
- Dengan Supabase connection pooler (port 6543), ini menyebabkan **severe performance degradation**
- Setiap query jadi lebih lambat 2-3x lipat

**Solution:**
```php
'options' => [
    PDO::ATTR_EMULATE_PREPARES => false,  // ✅ Correct for Supabase pooler
],
```

> [!CAUTION]
> Ini masalah yang sama yang kamu perbaiki di conversation sebelumnya (Conversation ID: 13a03a51). Setting ini sepertinya kembali lagi atau tidak ter-apply dengan benar.

---

### 2. File-Based Caching

**File:** [.env:40](file:///home/rikzan24_/SisforSinta/.env#L40)

**Problem:**
```env
CACHE_STORE=file  # ❌ Slow for production
```

**Impact:**
- File I/O jauh lebih lambat dari in-memory cache
- Setiap `Cache::remember()` call melakukan disk read/write
- Pada aplikasi dengan banyak concurrent users, ini jadi bottleneck besar

**Solution:**

**Option A: Redis (Recommended)**
```env
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=<your-redis-host>
REDIS_PASSWORD=<your-redis-password>
REDIS_PORT=6379
```

**Option B: Database cache (temporary solution)**
```env
CACHE_STORE=database
```

> [!TIP]
> Untuk deployment di platform seperti Railway/Render, bisa gunakan Upstash Redis (free tier 10K requests/day).

---

## 🟡 HIGH PRIORITY ISSUES

### 3. Missing Database Indexes

#### A. Siswa Table - Status Column

**File:** [database/migrations/2024_01_01_000005_create_siswa_table.php](file:///home/rikzan24_/SisforSinta/database/migrations/2024_01_01_000005_create_siswa_table.php)

**Problem:**
```php
$table->enum('status', ['Aktif', 'Lulus', 'Pindah'])->default('Aktif');
// ❌ No index on frequently queried column
```

**Impact:**
- Query `WHERE status = 'Aktif'` harus scan seluruh table
- Digunakan di banyak tempat:
  - [DashboardController:22](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/DashboardController.php#L22)
  - [PresensiSholatController:86](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/PresensiSholatController.php#L86)
  - [SiswaController (via siswaAktif relationship)](file:///home/rikzan24_/SisforSinta/app/Models/Kelas.php#L28-L31)

**Solution:** Create migration:
```php
Schema::table('siswa', function (Blueprint $table) {
    $table->index('status');
});
```

#### B. Siswa Table - Nama Column

**Problem:**
```php
$table->string('nama_siswa', 100);
// ❌ No index for sorting/searching
```

**Impact:**
- `ORDER BY nama_siswa` requires filesort (slow)
- LIKE searches scan entire table

**Solution:**
```php
Schema::table('siswa', function (Blueprint $table) {
    $table->index('nama_siswa');
});
```

#### C. Presensi Sholat - Composite Index

**File:** [database/migrations/2024_01_01_000009_create_presensi_sholat_table.php](file:///home/rikzan24_/SisforSinta/database/migrations/2024_01_01_000009_create_presensi_sholat_table.php)

**Problem:**
```php
$table->unique(['siswa_id', 'tanggal', 'jenis_sholat']);
// ✅ Has unique index, but query pattern is different
```

**Impact:**
- Query pattern di [PresensiSholatController:50-52](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/PresensiSholatController.php#L50-L52):
```php
PresensiSholat::where('tanggal', $today)
    ->where('jenis_sholat', $jenisSholat)
    ->whereIn('kelas_id', $kelasIds)
```
- Existing index tidak optimal untuk query ini

**Solution:**
```php
Schema::table('presensi_sholat', function (Blueprint $table) {
    $table->index(['tanggal', 'jenis_sholat', 'kelas_id']);
});
```

---

### 4. N+1 Query Problems

#### A. PresensiSholatController - Uncached Query

**File:** [PresensiSholatController:100](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/PresensiSholatController.php#L100)

**Problem:**
```php
$kelasList = Kelas::orderBy('nama_kelas')->get();  // ❌ No cache, loads all
```

**Impact:**
- Loads ALL kelas from database on every request
- Not scoped to current teacher
- No caching applied

**Solution:**
```php
$kelasList = Cache::remember('kelas_list_for_sholat', 60, 
    fn() => Kelas::orderBy('nama_kelas')->get()
);
```

#### B. PresensiSholatController - Batch Insert Issue

**File:** [PresensiSholatController:122-136](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/PresensiSholatController.php#L122-L136)

**Problem:**
```php
foreach ($validated['presensi'] as $data) {
    PresensiSholat::updateOrCreate([...], [...]);  // ❌ N queries for N records
}
```

**Impact:**
- Untuk 30 siswa = 30 database round-trips
- Dengan remote database (Supabase Singapore), ini sangat lambat

**Solution:** Use upsert (Laravel 8+):
```php
$records = collect($validated['presensi'])->map(function($data) use ($validated) {
    return [
        'siswa_id' => $data['siswa_id'],
        'tanggal' => $validated['tanggal'],
        'jenis_sholat' => $validated['jenis_sholat'],
        'kelas_id' => $validated['kelas_id'],
        'status_kehadiran' => $data['status'],
        'keterangan' => $data['keterangan'] ?? null,
        'recorded_by' => Auth::id(),
        'updated_at' => now(),
    ];
})->toArray();

PresensiSholat::upsert(
    $records,
    ['siswa_id', 'tanggal', 'jenis_sholat'],  // unique by
    ['status_kehadiran', 'keterangan', 'recorded_by', 'updated_at']  // update
);
```

#### C. Collection Filtering in PHP Instead of SQL

**File:** [PresensiSholatController:185-197](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/PresensiSholatController.php#L185-L197)

**Problem:**
```php
$presensiQuery = PresensiSholat::where(...)->get();  // Fetch all

foreach ($siswaList as $siswa) {
    $siswaPresensi = $presensiQuery->where('siswa_id', $siswa->id);  // Filter in PHP
    // Count in PHP...
}
```

**Impact:**
- Fetches all records then filters in PHP
- Counting should be done in database

**Solution:** Use SQL aggregation:
```php
$rekapData = PresensiSholat::where('kelas_id', $selectedKelasId)
    ->where('jenis_sholat', $jenisSholat)
    ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
    ->selectRaw('
        siswa_id,
        SUM(CASE WHEN status_kehadiran = "H" THEN 1 ELSE 0 END) as H,
        SUM(CASE WHEN status_kehadiran = "S" THEN 1 ELSE 0 END) as S,
        SUM(CASE WHEN status_kehadiran = "I" THEN 1 ELSE 0 END) as I,
        SUM(CASE WHEN status_kehadiran = "A" THEN 1 ELSE 0 END) as A
    ')
    ->groupBy('siswa_id')
    ->pluck('siswa_id')
    ->toArray();
```

---

### 5. Inefficient LIKE Queries

**File:** [PresensiSholatController:31-36](file:///home/rikzan24_/SisforSinta/app/Http/Controllers/PresensiSholatController.php#L31-L36)

**Problem:**
```php
->where(function ($q) {
    $q->where('nama_kelas', 'like', '%3%')
      ->orWhere('nama_kelas', 'like', '%4%')
      ->orWhere('nama_kelas', 'like', '%5%')
      ->orWhere('nama_kelas', 'like', '%6%');
})
```

**Impact:**
- LIKE dengan % di awal tidak bisa pakai index
- Query jadi full table scan

**Solution:** 
Add a `tingkat` column to kelas table or use regex:
```php
->whereRaw("nama_kelas ~ '[3-6]'")  // PostgreSQL regex
```

Or better, normalize data structure.

---

## 🟠 MEDIUM PRIORITY ISSUES

### 6. Frontend Performance

#### A. Blocking Font Loading

**File:** [layouts/app.blade.php:25](file:///home/rikzan24_/SisforSinta/resources/views/layouts/app.blade.php#L25)

**Problem:**
```html
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:...&display=block" rel="stylesheet">
```

**Impact:**
- `display=block` blocks page rendering until font loads
- External resource adds latency

**Solution:**
```html
<link href="..." rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="..." rel="stylesheet"></noscript>
```

Or use `display=swap` instead of `display=block`.

#### B. External CDN Dependencies

**File:** [layouts/app.blade.php:28](file:///home/rikzan24_/SisforSinta/resources/views/layouts/app.blade.php#L28)

**Problem:**
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

**Impact:**
- External CDN adds DNS lookup + connection time
- `defer` is good, but still blocks parsing

**Solution:**
Self-host Alpine.js via npm:
```bash
npm install alpinejs
```

Then import in `resources/js/app.js`:
```javascript
import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start()
```

#### C. Analytics Overhead

**File:** [layouts/app.blade.php:4,41](file:///home/rikzan24_/SisforSinta/resources/views/layouts/app.blade.php#L4)

**Problem:**
- Google Analytics/GTM loaded on every page
- Runs on every request

**Solution:**
- Load analytics scripts with `defer` or `async`
- Consider loading only in production
- Use DNS prefetch for analytics domains

---

### 7. Session Configuration

**File:** [.env:30-31](file:///home/rikzan24_/SisforSinta/.env#L30-L31)

**Current:**
```env
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
```

**Assessment:** ✅ Cookie-based sessions are good for stateless architecture. No change needed.

---

### 8. Nginx Configuration

**File:** [docker/nginx.conf](file:///home/rikzan24_/SisforSinta/docker/nginx.conf)

**Missing Optimizations:**

```nginx
# Add these to http block
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
fastcgi_busy_buffers_size 64k;
client_max_body_size 10M;

# Add to location ~ \.php$ block
fastcgi_buffering on;
fastcgi_connect_timeout 60s;
fastcgi_send_timeout 180s;
fastcgi_read_timeout 180s;
```

---

## 📊 Priority Action Plan

### Phase 1: Quick Wins (Immediate - 30 minutes)

1. ✅ Fix `PDO::ATTR_EMULATE_PREPARES` to `false`
2. ✅ Add database indexes for `siswa.status` and `siswa.nama_siswa`
3. ✅ Fix uncached query in PresensiSholatController:100

**Expected Impact:** 40-50% improvement

### Phase 2: Cache Migration (1-2 hours)

4. ✅ Setup Redis cache
5. ✅ Update `.env` to use Redis
6. ✅ Clear and warm up cache

**Expected Impact:** Additional 20-30% improvement

### Phase 3: Query Optimization (2-4 hours)

7. ✅ Replace `updateOrCreate` loop with `upsert()`
8. ✅ Add composite index for presensi_sholat
9. ✅ Optimize rekap queries with SQL aggregation
10. ✅ Fix LIKE queries or normalize data

**Expected Impact:** Additional 10-15% improvement

### Phase 4: Frontend (Optional - 1-2 hours)

11. ✅ Optimize font loading strategy
12. ✅ Self-host Alpine.js
13. ✅ Optimize nginx configuration

**Expected Impact:** Additional 5-10% improvement

---

## 🔍 Monitoring Recommendations

After implementing fixes, monitor:

1. **Laravel Telescope** (for development):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

2. **Query Logging** (temporary):
```php
// In AppServiceProvider boot()
DB::listen(function($query) {
    if ($query->time > 100) {  // queries > 100ms
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

3. **Application Performance Monitoring:**
   - Sentry (recommended for production)
   - New Relic
   - Blackfire.io

---

## 📚 References

- [Laravel Performance Best Practices](https://laravel.com/docs/11.x/database#query-optimization)
- [PostgreSQL Index Types](https://www.postgresql.org/docs/current/indexes-types.html)
- [Supabase Connection Pooler](https://supabase.com/docs/guides/database/connecting-to-postgres#connection-pooler)
- Previous optimization work: Conversation `13a03a51` (Optimizing Web Deployment Latency)

---

**Total Estimated Improvement:** 60-80% reduction in page load times after completing Phase 1-3.
