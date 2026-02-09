# HTML injection source check (spam / "Commentary for Support")

Response bodies were sometimes prefixed with HTML (comment + hidden div with spam links like kursuskerja.org, lizminelli.com, "slot gacor"), breaking Livewire JSON and causing `Unexpected token '<'`.

## What was checked on this server

### 1. Nginx

- **Files checked:** `/etc/nginx/sites-available/hris`, `/etc/nginx/sites-available/novulutions`, `nginx.conf`, `snippets/fastcgi-php.conf`, `conf.d/`, `modules-enabled/`
- **Result:** No `sub_filter`, `add_before_body`, `add_after_body`, or other directives that inject HTML. No injection content found in nginx config.

### 2. PHP

- **CLI:** `/etc/php/8.3/cli/php.ini` — `auto_prepend_file` and `auto_append_file` are empty.
- **FPM:** `/etc/php/8.3/fpm/php.ini` — same; no prepend/append.
- **Pool:** `/etc/php/8.3/fpm/pool.d/www.conf` — no `php_value` / `php_admin_value` for prepend/append.
- **Result:** No PHP-level auto prepend/append.

### 3. Per-directory PHP (.user.ini)

- **Checked:** `/var/www/html/hris_novulutions/public/` (document root).
- **Result:** No `.user.ini` found.

### 4. Laravel / project code

- **Searched for:** "Commentary", "kursuskerja", "lizminelli", "slot gacor", and the injection HTML pattern.
- **Result:** No matches in app code, views, or config (only in the middleware that *removes* the junk).

## Conclusion

The injection was **not** found in:

- Nginx config on this server  
- PHP `php.ini` (CLI/FPM) or pool config  
- `.user.ini` in the app document root  
- Laravel / hris_novulutions codebase  

So the source is likely **outside** this server (see below).

## Where to look next (recommended)

1. **Hosting control panel (cPanel, Plesk, etc.)**  
   - Look for: "Ad injection", "Footer injection", "Banner injection", "Custom HTML", "Site builder injection", or similar.  
   - Turn off any such feature for `ess.novulutions.com` (and hris/careers if same app).

2. **CDN / proxy (Cloudflare, etc.)**  
   - Check "Apps", "Page Rules", "Transform Rules", "Inject Code", or "Custom HTML" (or similar).  
   - Disable any rule that injects HTML into responses for this domain.

3. **Reverse proxy / load balancer**  
   - If nginx/Apache here sits behind another proxy or LB, check its config for response modification (e.g. `sub_filter`, custom HTML, or ad-injection modules).

4. **Security / WAF**  
   - Some WAFs or security layers inject a comment or HTML; check their dashboard or config for this domain.

## Mitigation in this app (already in place)

- **`StripHtmlFromLivewireResponse`** middleware runs for Livewire update requests (`X-Livewire`).  
- It strips any leading non-JSON (e.g. the injected HTML) so the client receives valid JSON.  
- This prevents "Unexpected token '<'" for Livewire even if the external injection is still present.

Once you find and disable the injection at the source (hosting/CDN/proxy), you can leave the middleware in place as a safeguard or remove it if you prefer.
