# Simple Job Board

Et minimalistisk WordPress-plugin til administration af jobopslag via Contact Form 7, automatisk udløb og arkivering.

---

## 🎯 Funktionalitet

**Simple Job Board** administrerer jobopslag med følgende features:

- **Custom Post Type** – `jobopslag` med slug `/jobs/`
- **Contact Form 7 Integration** – Konverterer formularinput direkte til jobopslag
- **ACF Metafields** – Struktureret data (titel, kontakt, deadline, lokation, type)
- **Automatic Expiry** – Daglig cron-job markerer udløbne opslag som `expired`
- **Filtered Views** – `/jobs/` (aktive) og `/jobs/alle/` (alle inkl. udløbne)
- **REST API** – Opslag tilgængelige via WordPress REST API

---

## 📦 Installation

1. Upload plugin-mappen til `/wp-content/plugins/`
2. Aktivér pluginet i WordPress admin
3. Permalinks flushet automatisk ved aktivering
4. Opret CF7-formular med de nødvendige felter (se nedenfor)

### Forudsætninger

- WordPress 5.0+
- **Contact Form 7** plugin
- **Advanced Custom Fields (ACF)** Pro eller Free

---

## ⚙️ Funktionalitet

### 1. Custom Post Type (`post-types.php`)

Registrerer `jobopslag` post type med:
- Offentligt og søgbart
- Supporterer titel, indhold og thumbnail
- Arkivable med REST API enabled

**Slug:** `/jobs/`

### 2. Contact Form 7 Integration (`cf7-integration.php`)

Hooker på `wpcf7_mail_sent` event:
- Validerer formularens `sjb_post_type=jobopslag` hidden-felt
- Opretter post med `pending` status
- Mapper CF7-felter til ACF-felter
- Saniterer all data (email, URL, tekst)

**Påkrævet CF7-felter:**
```
job_titel           (tekst)
job_indhold         (wysiwyg)
job_overskrift      (tekst)
kontakt_navn        (tekst)
kontakt_email       (email)
kontakt_telefon     (telefon)
kontakt_web         (url)
job_type            (select)
job_deadline        (dato)
job_location        (tekst)
sjb_post_type       (hidden: "jobopslag")
```

### 3. Automatic Expiry (`cron.php`)

Daglig scheduled event:
- Køres via WordPress cron (afhænger af site-aktivitet)
- Finder jobs hvor `job_deadline` < dags dato
- Markerer dem med status `expired`

### 4. Query Modifications (`query-mods.php`)

Custom URL-rewrite + query filtering:
- Registrerer `/jobs/alle/` endpoint
- Filter: `/jobs/` viser kun `publish` posts
- Filter: `/jobs/alle/` viser `publish` + `expired` posts

---

## 📁 Mappestruktur

```
simple-job-board/
├── simple-job-board.php       (Plugin header & bootstrap)
├── includes/
│   ├── post-types.php         (Custom post type registrering)
│   ├── cf7-integration.php    (CF7 hook + post creation)
│   ├── cron.php               (Job expiry scheduling)
│   └── query-mods.php         (URL rewrite + filtering)
├── README.md                   (Denne fil)
└── .gitignore
```

---

## 🛠️ Opsætning

### ACF Felt-Mapping

Rediger `includes/cf7-integration.php` linje 36-46 og erstat felt-nøglerne med dine egne:

```php
$map = array(
    'job_overskrift'  => 'field_XXXXX',  // din ACF-nøgle
    'kontakt_navn'    => 'field_XXXXX',
    'kontakt_email'   => 'field_XXXXX',
    'kontakt_telefon' => 'field_XXXXX',
    'kontakt_web'     => 'field_XXXXX',
    'job_type'        => 'field_XXXXX',
    'job_deadline'    => 'field_XXXXX',
    'job_location'    => 'field_XXXXX',
);
```

**Sådan finder du ACF-nøgler:**
1. Gå til Custom Fields i WordPress admin
2. Rediger din felt-gruppe
3. Se "Field Key" under hvert felt

---

## 📖 Brugseksempel

### 1. Opret CF7-formular

```html
[text* job_titel placeholder "Job-titel"]
[textarea* job_indhold]
[text* job_overskrift]
[text* kontakt_navn]
[email* kontakt_email]
[tel kontakt_telefon]
[url kontakt_web]
[select* job_type "Fuldtid|Deltid"]
[date* job_deadline]
[text* job_location]
[hidden sjb_post_type "jobopslag"]
[submit "Opret jobopslag"]
```

### 2. Indsend formular

Formular submissionner bliver automatisk konverteret til `jobopslag` posts.

### 3. Se opslaget

- **Aktive:** `/jobs/` – viser kun `publish` posts
- **Alle:** `/jobs/alle/` – viser `publish` + `expired` posts

---

## 🔒 Sikkerhed

| Område | Implementering |
|--------|----------------|
| **Input-sanitering** | ✅ Email, URL, tekst valideres |
| **Nonce-validering** | ✅ CF7 håndterer dette |
| **Post-status validering** | ✅ Kun `sjb_post_type=jobopslag` accepteres |
| **Uautoriseret formularadgang** | ✅ Hidden-felt beskytter mod misbrug |

---

## 💾 Database

Jobopslag gemmes som custom post type:

| Felt | Type | Beskrivelse |
|------|------|-------------|
| `post_type` | `jobopslag` | Custom post type |
| `post_status` | `publish` \| `expired` | Status |
| `post_title` | String | Job-titel |
| `post_content` | String (WYSIWYG) | Job-beskrivelse |
| `post_thumbnail` | ID | Bannerbillede |
| **ACF Felter** | Metadata | Kontakt, deadline, osv. |

---

## 🚀 Udvikling

### Planlagte features
- [ ] Admin-indstillinger side for felt-mapping
- [ ] Frontend styling & CSS
- [ ] Job-filter (lokation, type, fuldtid/deltid)
- [ ] Bedre deadline-håndtering
- [ ] Email-notifikationer ved udløb
- [ ] Forbedret søgebarhed

### Bidrag

Bug-reports og PRs er velkomne!

---

## 🔧 Fejlsøgning

| Problem | Løsning |
|---------|---------|
| Posts oprettes ikke | Tjek at `sjb_post_type=jobopslag` i CF7-formular |
| ACF-felter er tomme | Verificer felt-nøglerne i `cf7-integration.php` |
| Cron-jobbet køres ikke | Tjek WordPress cron (site-aktivitet påkrævet) |
| `/jobs/alle/` returnerer 404 | Flush permalinks i WordPress admin |

---

## ℹ️ Aktivering & Deaktivering

**Aktivering:**
- Scheduler cron-jobbet for dagligt expiry-check
- Flusher rewrite-regler

**Deaktivering:**
- Unscheduler cron-jobbet
- Flusher rewrite-regler
- Opslag slettes **ikke** ved deaktivering

---

## 📜 License

GPL-2.0+

---

## 👤 Forfatter

**Udviklet for vandpjat.dk**

Kontakt vandpjat.dk for support og feature-requests.
