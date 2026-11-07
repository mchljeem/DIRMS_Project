# DIRMS — Disaster Incident & Response Management System

Local multi-page PHP 8.2+ / MySQL 8 or MariaDB website for **San Pablo City, Laguna**, based on the previous Bantay Sakuna project. This is a school/demo application, **not an official emergency dispatch or live shelter booking service**.

**Important:** The app preserves your existing database (`bantay_sakuna`) by default. You can rename the database in `config.php` for an entirely new installation, but that will NOT copy old reports/accounts unless you import their data.

## Installation with XAMPP

1. Back up your current `bantay_sakuna` database in phpMyAdmin → Export, and back up `C:\xampp\htdocs\bantay-sakuna\`.
2. Extract the complete `dirms` folder to `C:\xampp\htdocs\dirms\`.
3. Copy the **contents** of the old `bantay-sakuna\uploads\` folder into the new `dirms\uploads\` folder if you need previous report photos. Keep `uploads/.htaccess`.
4. If you used custom MySQL credentials, copy the database settings (but not necessarily the whole file) from your old `config.php` into DIRMS `config.php`. The default MySQL database is **`bantay_sakuna`**, username `root`, blank password, port 3306.
5. Start **Apache** and **MySQL** in XAMPP.
6. Open `http://localhost/dirms/upgrade.php` to migrate an existing database. This adds a unique username column and center update timestamps; it does not delete reports, users, or hashed passwords. If an admin account already exists, the upgrader requires your existing admin credentials. **Back up first.**
7. **If your `users` table is empty / you have no administrator:** open `http://localhost/dirms/setup.php` and choose the first admin username, email, and a 12+ character password. You can also open setup directly on a first-time install; it creates the tables and upgrades their schema.
8. Open `http://localhost/dirms/login.php`. Administrator sign-in goes to `admin.php`; resident sign-in/registration goes to `resident.php`. Users can sign in with their **username or email**.
9. Once the system is installed, delete or restrict `setup.php` and `upgrade.php` on a publicly accessible server. These pages are limited to `localhost` by code, but defense in depth is advisable.

**If you have no user records** as in your phpMyAdmin screenshot, **you need to create the first admin using `setup.php`**. An empty `users` table cannot contain an admin role value.

## Pages

| Public / Resident | URL | Administrator | URL |
|---|---|---|---|
| Home | `index.php` | Admin dashboard (admin role only) | `admin.php` |
| Alerts | `alerts.php` | Alert editor | Admin → Manage Alerts |
| Incident submission | `report-incident.php` | Review incident submissions | Admin → Incident Reports |
| Evacuation centers & map | `evacuation-centers.php` | Shelter locations, occupancy, GPS | Admin → Evacuation Centers |
| Hotlines | `hotlines.php` | Hotline directory editor | Admin → Hotlines |
| Disaster preparedness | `tips.php` | Tips editor | Admin → News & Tips |
| News | `news.php` | News editor | Admin → News & Tips |
| Sign in / create account | `login.php`, `register.php` | Account permissions & status | Admin → Users |
| Resident dashboard | `resident.php` | Site settings | Admin → Settings |
| My incident reports | `my-reports.php` | | |

## Authentication, permissions & database

- `users` SQL table stores **full_name, username, email, password_hash, role, is_active, created_at**. Plain-text passwords are **not stored**, and a password cannot be viewed after registration. PHP uses `password_hash()` on sign-up and `password_verify()` on login; PDO prepared statements are used throughout.
- Public registration ALWAYS creates a **resident** account. Only an existing admin can grant/revoke admin role on the Users page. A resident cannot call administrator update endpoints, even by typing an API URL manually.
- Login regenerates the session ID, and write operations check a CSRF token. In production, enable HTTPS and protect the database; a local XAMPP demo isn't a production emergency service.
- Existing old accounts are assigned migration usernames like `legacy_1`, `legacy_2` and can still log in using their original **email and password**. They can see their legacy username in phpMyAdmin or the admin users listing.
- The eye button only changes a password field between hidden and visible **in the current browser**. It does not reveal hashed passwords stored in MySQL.

## Evacuation maps, capacity and available slots

- The public center page contains a real **OpenStreetMap city overview**, visible when online. Each center includes its own clickable preview and an **Open Google Maps** link.
- For an **administrator-supplied latitude and longitude pair**, an exact-coordinate marker appears in the preview; the Google Maps link requests a close zoom on those coordinates. If GPS is missing, the preview shows **only the San Pablo City area**, and Google Maps runs an **address search**, not a verified precise pin.
- Admin → Evacuation Centers → Add/Edit: enter **Center Name**, barangay, address, total capacity, **current occupancy** and optionally **both GPS coordinates** (latitude and longitude). Availability is calculated as `max(0, capacity - occupancy)`; over-capacity entries are rejected. Updated figures appear on public cards after refresh.
- `capacity = 0` means **unconfirmed**, not zero available places. A center is marked **Full** at zero slots, **Low slots** at 10% or less, or **Available** otherwise. The timestamp is updated on admin edits.
- `Demo / Unverified record` is ON by default for new centers and preserved when editing old samples. Turn it OFF **only once the center, capacity and occupancy have actually been verified** by the local authorities.
- **These records are manual, not live real-time occupancy data.** No SMS alerts, shelter booking, route safety verification or emergency service dispatch are provided. Call the center or CDRRMO before traveling.

## Verified contact directory for San Pablo City and the Philippines

The directory is seeded or supplemented through `setup.php` / `upgrade.php`. **Phone numbers must be re-verified by administrators regularly**, as they may change. In an immediate emergency use **911**.

- **San Pablo City CDRRMO emergency:** 0998 540 7171 — official city website
- **San Pablo City CDRRM office:** (049) 800 0405 — official city website
- **City Information Office:** (049) 561 1483 — official city website (NOT dispatch)
- **City Administration:** (049) 521 0307 — official city website (NOT dispatch)
- **San Pablo Colleges Medical Center Emergency Room:** 0967 364 1460 — hospital emergency page
- **San Pablo Colleges Medical Center trunkline:** (049) 310 1100 — hospital website
- **National emergency:** 911 (police, fire, ambulance, rescue)
- **Philippine Red Cross:** 143; **PNP national line:** 117; **BFP central:** (02) 8426 0219
- **PAGASA:** (02) 8284 0800; **PHIVOLCS:** (02) 8426 1468; **DSWD Disaster Response:** (02) 8856 3665

Sources:
- https://www.sanpablocity.gov.ph/
- https://spcmc.com.ph/services/emergency
- https://ehotlines.e.gov.ph/

The city or hospital contacts are not necessarily 24-hour emergency dispatch hotlines; use 911 for immediate assistance. The upgrader disables one previously shipped **unverified** provincial sample number (`0919 417 3698`) without modifying any other locally managed contact records.

## Disaster tips

12 prepared-safety entries cover: before and during typhoons, flood protection, earthquake safety, aftershocks, fire escape, go bag supplies, landslide warnings, family emergency planning, evacuating safely and post-disaster precautions. The administrator may add/edit/delete tips. Users may only read them.

Guidance sources:
- PAGASA flood rules: https://pagasa.dost.gov.ph/learning-tools/floods
- PAGASA storm information: https://www.pagasa.dost.gov.ph/information/storm-surge
- National hotline list: https://ehotlines.e.gov.ph/

## Manual verification checklist (run in your local XAMPP browser)

1. With an empty `users` table, use `setup.php` to make the admin. Check phpMyAdmin `bantay_sakuna.users`: admin row and `password_hash` beginning with a password-hash identifier, not plain text.
2. Register a resident, toggle **Show Password**, sign in by username, then sign in by email. Both should go to `resident.php`.
3. Try going to `/dirms/admin.php` as a resident: redirected to resident page. Try POST `api.php?action=admin_save` as a resident: should receive **403** (with a valid CSRF token).
4. Admin signs in and goes to `admin.php`. Add a new alert; refresh `alerts.php` and verify it appears.
5. Admin edits shelter capacity 100 and occupancy 65: public shelter card should show **35 available slots**. Change occupancy 100: card should say **FULL**. Try occupancy 101: server must reject it.
6. Admin enters two GPS coordinates, saves, views the center preview and follows **View Map** to Google Maps. Check the pin, not just its general city location.
7. Send a resident incident report. Review/change its status in admin dashboard; check it in the resident's My Reports page.
8. Update a hotline and a preparedness tip as admin; verify both change publicly. Check resident has no editing controls.
9. Test on a mobile phone and desktop and verify that image uploads work when `uploads/` is writable.

### Preview pages

`preview.html`, `preview-evacuation-centers.html`, `preview-login.html`, `preview-resident.html` etc. are **static interactive demonstrations** only; they do not connect to MySQL. Use PHP pages for real data. `admin-preview.html` is likewise only a demo UI.

### Troubleshooting

- **MySQL connection error:** start MySQL in XAMPP and check database settings in `config.php`; ensure the PHP **PDO MySQL** extension is enabled.
- **Unknown column `username`:** open `/dirms/upgrade.php`, complete the migration, then refresh.
- **Can't find admin role:** the `role` column isn't an account. Create the first admin using `/dirms/setup.php` when `users` is empty.
- **No map tiles:** map previews require internet. Open the Google Maps link directly; enter a verified GPS pair in admin for an accurate location marker.
- **Old incident photos missing:** copy old `uploads/` image files into `dirms/uploads/`.
- **Files still say Bantay Sakuna:** press **Ctrl + Shift + R** to clear stale browser files. If your database `site_settings.site_name` was set to a custom value, update it in Admin → Settings.
