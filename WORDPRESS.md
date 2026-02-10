# WordPress Plugin Development Guidelines

Dette dokument definerer standards og best practices for Simple Job Board WordPress-pluginet.

---

## 🎯 Projekt Kontext

- **Type:** WordPress Plugin
- **Minimum WordPress Version:** 6.0+
- **Minimum PHP Version:** 8.1+
- **Dependencies:**
  - Contact Form 7 (required)
  - Advanced Custom Fields (ACF) - Pro or Free (required)
- **Post Type:** `jobopslag`
- **Slug:** `/jobs/`
- **Text Domain:** `simple-job-board` (for translations)

---

## 🚫 VIGTIG: Hvad VIRKER IKKE i WordPress

### ❌ Absolut forbudt

1. **PHP Features over 8.1 (ikke tilgængelige)**
   - ❌ Readonly classes (PHP 8.2+)
   - ❌ DNF types (PHP 8.2+)
   - ❌ `#[\Override]` attribute (PHP 8.3+)
   - ❌ Typed class constants (PHP 8.3+)
   - **Grund:** Minimum PHP 8.1 er target
   - **Regel:** Brug kun PHP 8.1 syntax og features

   **Tilladte PHP 8.1 features:**
   - ✅ Match expressions
   - ✅ Named arguments
   - ✅ Union types
   - ✅ Enums
   - ✅ Readonly properties
   - ✅ Intersection types
   - ✅ Fibers
   - ✅ `str_contains()`, `str_starts_with()`, `str_ends_with()`

2. **Direkte Database Queries**
   - ❌ `mysqli_` funktioner direkte
   - ❌ SQL uden `wpdb->prepare()`
   - **Grund:** SQL injection vulnerability
   - **Løsning:** Altid bruge `$wpdb->prepare()` og `get_posts()` / `WP_Query`

3. **Direkte API Requests uden Nonce**
   - ❌ AJAX uden `wp_verify_nonce()`
   - ❌ Forms uden CSRF protection
   - **Grund:** Sikkerhedshul
   - **Løsning:** Altid bruge `wp_create_nonce()` og `wp_verify_nonce()`

4. **Hardcoded Paths**
   - ❌ `/wp-content/plugins/...` hardcoded i kode
   - ❌ Absolut sti uden `__DIR__` eller `plugin_dir_path()`
   - **Grund:** Multi-site og andre installationer bryder
   - **Løsning:** Bruge `plugin_dir_path(__FILE__)`, `plugin_dir_url(__FILE__)`

5. **Global Functions uden Prefix**
   - ❌ `function get_job() {}`
   - ❌ `function save_data() {}`
   - **Grund:** Namespace collision, plugin crashes
   - **Løsning:** **Altid** prefiks: `function sjb_get_job() {}`

6. **Inline Styles & Scripts**
   - ❌ `<style>` direkte i HTML
   - ❌ `<script>` i output
   - **Grund:** CSP violations, WordPress standards
   - **Løsning:** `wp_enqueue_style()` og `wp_enqueue_script()`

7. **Activation Hooks uden Checks**
   - ❌ Registrering af custom post types i activation hook alene
   - **Grund:** Post types skal registreres på `init` hook
   - **Løsning:** Registrer på `init`, schedule cron i activation

---

## ✅ VIGTIG: Best Practices som SKAL følges

### 1. Prefix Convention
```php
// ✅ KORREKT
define( 'SJB_PATH', plugin_dir_path( __FILE__ ) );
function sjb_get_jobs() {}
$sjb_custom_var = ...;

// ❌ FORKERT
define( 'PATH', plugin_dir_path( __FILE__ ) );
function get_jobs() {}
$custom_var = ...;
```

### 2. Plugin Header
Skal **altid** indeholde:
```php
<?php
/*
Plugin Name: Simple Job Board
Description: Jobopslag via CF7, cron‑udløb og arkiv på /jobs/alle/.
Version:     1.0.0
Author:      mhoDK
License:     GPL‑2.0+
Requires at least: 6.0
Requires PHP: 8.1
Update URI: false
*/
```

### 3. Security Headers
Alle filer **skal** starte med:
```php
if ( ! defined( 'ABSPATH' ) ) { exit; }
```

### 4. Hook Integration
Hooks som **SKAL** bruges:
- `init` – Register post types, taxonomies
- `wp_enqueue_scripts` – Enqueue frontend assets
- `admin_enqueue_scripts` – Enqueue admin assets
- `wpcf7_mail_sent` – CF7 form submission handling
- `wp_loaded` – After all WordPress is loaded
- `plugins_loaded` – After all plugins are loaded

### 5. Custom Post Types
**Regel:** Altid registrer på `init` hook, aldrig i activation:
```php
add_action( 'init', 'sjb_register_post_types' );

function sjb_register_post_types() {
    register_post_type( 'jobopslag', array(
        'label'  => 'Jobopslag',
        'public' => true,
        'supports' => array( 'title', 'editor', 'thumbnail' ),
        'has_archive' => true,
        'rewrite' => array( 'slug' => 'jobs' ),
    ));
}
```

### 6. ACF Integration
**Regel:** Aldrig rely på manual ACF field creation i theme:
- Alle ACF-felter skal enten:
  - Være dokumenteret i README
  - Eller programmeret via ACF PHP API
- Ved field queries, altid bruge ACF funktioner:
  ```php
  get_field( 'job_deadline', $post_id ); // ✅
  get_post_meta( $post_id, 'job_deadline', true ); // ❌ undgå direkte meta
  ```

### 7. Sanitering & Validation
**Regel:** Sanitize input, escape output:
```php
// ✅ KORREKT
$email = sanitize_email( $_POST['email'] );
$url = esc_url( get_field( 'kontakt_web', $post_id ) );
$content = wp_kses_post( $post->post_content );

// ❌ FORKERT
$email = $_POST['email'];
$url = get_field( 'kontakt_web', $post_id );
echo $post->post_content;
```

### 8. Nonce Protection
**Regel:** Alle form submissions skal have nonce:
```php
// I form:
wp_nonce_field( 'sjb_action', 'sjb_nonce' );

// In handler:
if ( ! isset( $_POST['sjb_nonce'] ) ||
     ! wp_verify_nonce( $_POST['sjb_nonce'], 'sjb_action' ) ) {
    wp_die( 'Security check failed' );
}
```

### 9. REST API
**Regel:** Custom post types skal være REST-enabled hvis brugt via API:
```php
'rest_base' => 'jobopslag',
'show_in_rest' => true,
```

### 10. Cron Jobs
**Regel:** Schedule i activation, unschedule i deactivation:
```php
// Activation
register_activation_hook( __FILE__, 'sjb_activate_plugin' );
function sjb_activate_plugin() {
    if ( ! wp_next_scheduled( 'sjb_daily_expiry_check' ) ) {
        wp_schedule_event( time(), 'daily', 'sjb_daily_expiry_check' );
    }
}

// Deactivation
register_deactivation_hook( __FILE__, 'sjb_deactivate_plugin' );
function sjb_deactivate_plugin() {
    wp_unschedule_event( wp_next_scheduled( 'sjb_daily_expiry_check' ), 'sjb_daily_expiry_check' );
}

// Handle event
add_action( 'sjb_daily_expiry_check', 'sjb_check_job_expiry' );
function sjb_check_job_expiry() {
    // Logic her
}
```

---

## 📋 Kodestandards

### Indentation
- **Tabs** (WordPress standard)
- Ikke spaces

### Naming Conventions
| Element | Format | Eksempel |
|---------|--------|----------|
| Konstanter | UPPER_SNAKE_CASE | `SJB_PATH` |
| Funktioner | lower_snake_case + prefix | `sjb_get_jobs()` |
| Variabler | lower_snake_case | `$job_title` |
| Classes | PascalCase + prefix | `SJB_JobHandler` |
| Hooks | lower_snake_case + prefix | `sjb_job_created` |

### Comments
```php
// ✅ KORREKT
// Get all active jobs for display
$jobs = get_posts( array( 'post_type' => 'jobopslag' ) );

// ❌ FORKERT
// Get jobs
$j = get_posts( array( 'post_type' => 'jobopslag' ) );
```

---

## 🧪 Testing Requirements

### Dev Checklist før push:
- [ ] Kode følger WordPress coding standards
- [ ] Alle funktioner har `sjb_` prefix
- [ ] Ingen globale variabler uden prefix
- [ ] Input sanitizet med `sanitize_*()` funktioner
- [ ] Output escaped med `esc_*()` funktioner
- [ ] Nonce verificeret for forms
- [ ] Tested på minimum WordPress 6.0+ (seneste anbefalet)
- [ ] Tested med ACF Pro og Free
- [ ] Tested med Contact Form 7
- [ ] Ingen PHP notices/warnings
- [ ] Ingen JavaScript console errors
- [ ] Cron jobs testes manuelt

### Performance Checklist:
- [ ] Ingen N+1 queries
- [ ] Transients brugt for API/tunge queries
- [ ] Scripts async/deferred hvis muligt
- [ ] Meta queries optimeret med `meta_key` index where needed

---

## 📁 Filstruktur

```
simple-job-board/
├── simple-job-board.php          (Main plugin file - plugin header & bootstrap)
├── includes/
│   ├── post-types.php            (Register post types & taxonomies)
│   ├── cf7-integration.php       (CF7 form handling)
│   ├── cron.php                  (Scheduled events)
│   └── query-mods.php            (URL rewrites & query filters)
├── README.md
├── WORDPRESS.md                  (This file)
└── .gitignore
```

---

## 🔒 Security Hardening

### Obligatorisk:
1. ✅ Plugin header med `Update URI: false` hvis ikke auto-update
2. ✅ `if ( ! defined( 'ABSPATH' ) ) { exit; }` i alle filer
3. ✅ `wp_nonce_field()` og `wp_verify_nonce()` for alt data
4. ✅ `sanitize_*()` for all user input
5. ✅ `esc_*()` for all HTML output
6. ✅ Capability checks: `current_user_can( 'manage_options' )`
7. ✅ No direct database access uden `$wpdb->prepare()`
8. ✅ No `eval()`, no `$GLOBALS` manipulation, no `extract()`

### ACF Specifikt:
- ✅ Aldrig trust ACF data uden sanitering
- ✅ Aldrig output ACF data uden escaping
- ✅ Bruge `get_field()` med post_id explicitly

### CF7 Specifikt:
- ✅ Verify `wpcf7_mail_sent` actually fired
- ✅ Check that `sjb_post_type=jobopslag` hidden field exists
- ✅ Validate all form data før database insert

---

## 🐛 Common WordPress Pitfalls to Avoid

| Pitfall | Problem | Løsning |
|---------|---------|---------|
| Using `include()` | Can fail if file not found | Use `require_once` eller include via constant path |
| ACF meta vs post meta | `get_post_meta()` kræver `_` prefix for ACF felter | Brug altid `get_field()` for ACF data |
| Permalink issues | Rewrite rules not flushed | Flush on activation: `flush_rewrite_rules()` |
| ACF field not found | Field key ≠ field name | Use exact field key from ACF, not label |
| CF7 hook not firing | Hook name typo | Double-check: `wpcf7_mail_sent` |
| Cron not running | No site traffic to trigger | Test with WP-CLI: `wp cron test` |
| Multisite issues | Single-site logic breaks on multisite | Use `get_blog_option()` if needed |
| Plugin conflicts | Other plugins override functions | Always use namespace/prefix to avoid conflicts |

---

## 📝 Code Review Checklist

Før hver commit, tjek:

- [ ] Alle nye funktioner starter med `sjb_` prefix
- [ ] Ingen variabler i globalt scope uden prefix
- [ ] Alle posts oprettes med `wp_insert_post()`, ikke direkt SQL
- [ ] Alle ACF felter bruger `get_field()`, ikke `get_post_meta()`
- [ ] Alle forms har nonce felter
- [ ] Alle user inputs er sanitized
- [ ] Alle HTML outputs er escaped
- [ ] Ingen `echo` uden escaping
- [ ] Ingen hardcoded paths
- [ ] Ingen `wp_die()` uden hjælp-besked
- [ ] Cron logik er testable
- [ ] Ingen console.log() i production JS
- [ ] README opdateret hvis nyt feature

---

## 🎓 Resources

- **WordPress Coding Standards:** https://developer.wordpress.org/plugins/wordpress-org/how-your-plugin-gets-hosted/
- **WordPress Security:** https://developer.wordpress.org/plugins/security/
- **ACF Documentation:** https://www.advancedcustomfields.com/resources/
- **CF7 Documentation:** https://contactform7.com/

---

**Sidst opdateret:** 2026-02-10
**Gyldig for:** Simple Job Board v1.0+
