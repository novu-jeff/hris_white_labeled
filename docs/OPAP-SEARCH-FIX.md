# OPAP HRIS – Search field not working: fix checklist

Use this when search works on this (Novulutions) server but **not** on the OPAP HRIS server, and the codebases are almost the same.

---

## 1. **Livewire / front-end**

### 1.1 Input binding

Search inputs must be bound to the Livewire property and trigger updates.

**Working pattern (this repo):**

- **Admin/ESS list pages (Leave, Time Adjustments, Offset, etc.):**
  - Blade: `wire:model.live="search"`
  - Component: `public $search = '';`
  - On every keystroke the component re-renders and `render()` runs with the new `$this->search`.

**On OPAP, check:**

- Blade has **exactly**:
  ```html
  <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Search something...">
  ```
- Not `wire:model="search"` only (no `.live`) – then search runs only on form submit/blur.
- If Livewire is old and `.live` is not available, use:
  ```html
  wire:model.debounce.500ms="search"
  ```

### 1.2 Livewire scripts and URL

- Layout must include:
  ```blade
  @livewireStyles
  ...
  @livewireScripts
  ```
- In **.env** on OPAP set:
  - `APP_URL` = exact URL users use (e.g. `https://opap-hris.example.com`)
  - If you use a CDN or separate asset URL, set `ASSET_URL` accordingly.

If `APP_URL` is wrong, Livewire’s AJAX requests can go to the wrong host and search (and other Livewire features) will fail.

### 1.3 Duplicate `id="search"`

- Only one element on the page should have `id="search"`.
- If the same layout is used for multiple components, avoid duplicate IDs (e.g. `id="search-leave"`, `id="search-time-adjustments"`) so the correct input is bound.

---

## 2. **Backend: apply search in `render()`**

Search must **narrow the same query** that is later paginated. If you assign the filtered query to a different variable and then paginate the unfiltered one, search will look broken.

**Correct pattern (from this repo):**

```php
$model = EmployeeLeave::with(...)->where('status', $status)->where('isDeleted', false);

if ($this->search) {
    $this->resetPage();
    $model = $model->where(function ($query) {
        $query->where('employee_no', 'like', '%' . $this->search . '%')
            ->orWhereHas('employee', function ($subQuery) {
                $subQuery->whereRaw("CONCAT(COALESCE(firstname,''), ' ', COALESCE(lastname,'')) LIKE ?", ['%' . $this->search . '%']);
            });
    });
}

$records = $model->latest()->paginate($this->entries);
```

**Wrong (search never applied):**

```php
if ($this->search) {
    $records = $model->where(...);  // filtered builder assigned to $records
}
$records = $model->latest()->paginate($this->entries);  // overwrites with unfiltered!
```

On OPAP, open the same Livewire component (e.g. `App\Livewire\Admin\Ess\Leave\Index`) and ensure the block that applies `$this->search` **modifies the same `$model` (or `$query`) that you pass to `->paginate()`**, and that you don’t overwrite the filtered result with an unfiltered one.

---

## 3. **Relations and table names**

Search often uses:

- `employee_no` on the main table, and  
- `CONCAT(firstname, ' ', lastname)` via a relation (e.g. `employee` or `personal`).

On OPAP, confirm:

- The relation name matches: e.g. `employee()` or `personal()` and that it points to the table that has `firstname` and `lastname`.
- If OPAP renamed tables/columns, update the `whereRaw` and relation name accordingly.
- Using `COALESCE(firstname,'')` and `COALESCE(lastname,'')` avoids broken CONCAT when either column is null.

---

## 4. **Notifications search (different pattern)**

Notifications use:

- Property: `search_param`
- Blade: `wire:model="search_param"` and a **button** `wire:click="search"` (no `.live`).
- So the user types then clicks “Search”; `search()` runs and calls `loadNotifications($this->search_param)`.

If notifications search fails on OPAP:

- Check that the button has `wire:click="search"`.
- Check that `loadNotifications()` uses `$this->search_param` (or the argument passed to it) in the query.
- If MySQL is old, `JSON_UNQUOTE(data->'$.title')` might not be supported; then you need to adjust the query (e.g. different JSON functions or a different column) to match your DB.

---

## 5. **Quick copy-paste sync (from this repo to OPAP)**

1. **Blade (one example – Leave):**  
   In `resources/views/livewire/admin/ess/leave/index.blade.php`, the search input should be:
   ```blade
   <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Search something...">
   ```

2. **Component (Leave):**  
   In `app/Livewire/Admin/Ess/Leave/Index.php`, in `render()`:
   - There must be `public $search = '';`
   - The block that applies search must update the **same** query variable that is later used in `->paginate()`, as in the “Correct pattern” above.

Apply the same idea to other list pages (Time Adjustments, Offset, Business Slip, ATRO, HRIS Index, etc.): same `wire:model.live="search"` (or debounce) and same “apply search to the paginated query” logic.

---

## 6. **Environment and cache**

On OPAP after any code or .env change:

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

Then hard-refresh the browser (Ctrl+F5) so Livewire and JS are reloaded.

---

## 7. **If it still fails**

- Browser DevTools → Network: when you type in the search box, do you see a Livewire request (e.g. to the same page or a Livewire endpoint)? If not, the input is not bound or Livewire is not loaded.
- Browser DevTools → Console: any JavaScript errors (e.g. 404 for Livewire, or Alpine/Livewire errors) can prevent search from firing.
- Laravel log on OPAP: `storage/logs/laravel.log` – check for SQL or PHP errors when you search (e.g. wrong table/column or JSON syntax).

Once the correct `wire:model.live`, `APP_URL`, and “apply search to the same query you paginate” are in place on OPAP, search should behave like on this server.
