# PUPSRC Lost and Found (Laravel + XAMPP)

The campus Lost and Found system, moved from the original JavaFX desktop app to a web app built with **Laravel 12, PHP 8.2 and XAMPP's MySQL (MariaDB)**.

**This system is for the Lost and Found office only.** Only **Admin** and **Super Admin** accounts can log in. Students and faculty do not have accounts and do not see the item cards: they come to the office, and the admin does everything for them (logs a walk-in LOST report, finds a match, checks their ID and releases the item with a claim slip). This stops people from copying the public description of a found item and claiming something that is not theirs.

The screens keep the **original design**: same maroon navbar, LOST/FOUND item cards, "+" report menu and ☰ menu, LOST/FOUND ITEM REPORT form, FULL DETAILS, CLAIM VERIFICATION and ACCOUNT pages.

## What it does

There are two roles: **Admin** (office staff) and **Super Admin** (head of the office, max 2). A Super Admin can do everything an Admin can, plus manage accounts.

| | Admin | Super Admin |
|---|---|---|
| Log in (email + password) | ✔ | ✔ |
| Dashboard: browse, search, filter. **All** shows open items only; CLAIMED / RESOLVED have their own filters | ✔ | ✔ |
| Log a surrendered (FOUND) item, with the finder's details or "Keep Anonymous" | ✔ | ✔ |
| Log a **walk-in LOST report** for a person at the office (owner's name, ID number, contact; only admins see them) | ✔ | ✔ |
| **RELEASE TO CLAIMANT**: on an open FOUND item, type in who is claiming it, what they said about the item and the ID they showed; the item becomes CLAIMED and a claim slip is issued | ✔ | ✔ |
| RESOLVE a report, including "resolve with an existing found item" (ID checked + claim slip) | ✔ | ✔ |
| "Possible matches" on a report | ✔ | ✔ |
| Approve / reject claims (older claims made before the office-only change) | ✔ | ✔ |
| Edit any report; delete only items not yet released | ✔ | ✔ |
| My Reports (items you logged), Account page, change own password | ✔ | ✔ |
| Users list | ✔ (view) | ✔ |
| Add Admin accounts, promote an Admin to Super Admin (max 2; **demote** one first when full), reset a forgotten password, delete accounts, view the **Activity Log** | ✘ | ✔ |

When an admin approves a claim, the item becomes **CLAIMED**, any other pending claims on it are rejected automatically, and a **Claim Slip** (same layout as the Java app's PDF) can be printed or saved as PDF from the browser.

**RESOLVE** works like the Java RESOLVE dialog: an admin can resolve a lost report **with an existing found item** (picked from the possible matches; the found item becomes CLAIMED and the two are linked), or **outside the system**. Resolving never deletes anything; it only changes the status.

FULL DETAILS also shows **possible matches**, using the matching logic from the Java app. A walk-in LOST report is flagged **POSSIBLE MATCH FOUND** in My Reports when a similar item is at the office.

Found items show how many **days they have been at the office**; after 30 days the admin is reminded that the item can be resolved outside the system (donated or disposed, following office policy).

Accounts tied to official records (received items, logged found items, reviewed claims) **can't be deleted**, and neither can items already released to their owner, so claim slips and history stay intact.

An item's **status can't be edited by hand**: it changes only through claim approval or RESOLVE, which are both recorded. 

Passwords are stored hashed (bcrypt), never as plain text.

---

## How to run it (Windows + XAMPP)

You need **XAMPP with PHP 8.2 or newer**. Composer is **not** required, because the `vendor` folder is already included.

### 1. Start XAMPP
Open the XAMPP Control Panel and click **Start** on **Apache** and **MySQL**.

### 2. Create the database (first time only)
Open <http://localhost/phpmyadmin>, click **New**, name it `pup_lostfound`, and click **Create**.

### 3. Extract the project
Right-click the zip, choose **Extract All…**, and pick a folder such as `D:\xampp\htdocs`. You should end up with `D:\xampp\htdocs\pup-lostfound\artisan`.

### 4. Open the XAMPP Shell in the project folder

```
cd /d D:\xampp\htdocs\pup-lostfound
```

(Use `/d` when the project is on a different drive than the shell.)

### 5. Create the tables and sample data (first time only)

```
php artisan migrate --seed
```

### 6. Start the app

```
php artisan serve
```

Open **<http://127.0.0.1:8000>**. Keep that window open while you use the app; press `Ctrl + C` to stop it.

### Opening it again later
Start MySQL in XAMPP, then:

```
cd /d D:\xampp\htdocs\pup-lostfound
php artisan serve
```

Your data stays in MySQL, so nothing is lost between runs.

### Updating from the previous version
If you already ran the older version, replace the project folder with this one, but **copy your old `.env` file over first**, so the app key stays the same. Then run:

```
php artisan migrate
```

This only updates the tables (new columns, removes the old "number of claimants" column). Your accounts, reports and claims are kept.

### Updating from an older version
Copy your old `.env` into the new folder first, start MySQL, then run `php artisan migrate`. It adds the faculty role, `employee_number`, the walk-in owner columns and makes `claims.user_id` optional. Your accounts, reports and claims are kept. (The new sample faculty account and sample reports only appear after `php artisan migrate:fresh --seed`, which wipes the data.)

### Sample accounts

| Role | Login | Password |
|---|---|---|
| Super Admin | `admin@pup.edu.ph` | `admin123` |
| Admin | `staff@pup.edu.ph` | `staff123` |

There is no sign-up page. New **Admins** (and a second **Super Admin**) are added by a Super Admin through **Account → + Add Another Admin**. If someone forgets their password, a Super Admin can set a new one on the **Users** page; every admin can change their own password on the **Account** page. Super Admin accounts can't be deleted, and there can be at most 2.

When you upgrade an existing database, `php artisan migrate` makes the first admin account the Super Admin.

---

## The database

In phpMyAdmin, open `pup_lostfound`. The system's own tables:

- `users`: every account (`role` is `admin` or `superadmin`; `password` is hashed). Older `student` / `faculty` rows may still exist but cannot log in
- `items`: lost and found reports (`type` = lost/found, `status` = open/claimed/resolved, `resolution` = matched/outside, `matched_item_id`, `time_happened`, and for found items `is_anonymous` + `finder_name` / `finder_student_id` / `finder_contact`; for walk-in lost reports `owner_name` / `owner_id_number` / `owner_contact`, and `user_id` is then the admin who logged it)
- `claims`: claims (`status` = pending/approved/rejected, `id_presented` filled in by the admin on approval; `user_id` is empty when an item is released to a walk-in owner, whose name and ID number are then kept in `claimant_name` / `claimant_id_number`)
- `categories`: Electronics, Bags & Wallets, IDs & Documents, Clothing, Others
- `activity_logs`: what Admins and Super Admins did and when (only the latest 1,000 entries are kept)

The other tables (`sessions`, `cache`, `jobs`, `migrations`, …) are Laravel's own.

To wipe everything and start over with only the sample data:

```
php artisan migrate:fresh --seed
```

---

## Where things are in the code

```
routes/web.php                         All pages + who is allowed (guest / admin)
app/Http/Middleware/EnsureRole.php     Lets only admins / super admins into the system
app/Http/Controllers/
    AuthController.php                 Create account, login, logout
    ItemController.php                 Dashboard, report / edit / delete, Full Details, resolve, My Reports
    ClaimController.php                Claim Verification, My Claims, cancel claim, proof photo, Claim Slip
    AccountController.php              Account page + edit details
    AdminController.php                Approve / reject claims, add / promote / demote admins, users, activity log
app/Models/                            User, Item, Claim, Category, ActivityLog
database/migrations/                   Table definitions
database/seeders/DatabaseSeeder.php    Sample accounts, categories and reports
resources/views/                       Blade pages (welcome, auth, items, claims, account, admin)
public/css/app.css                     All styling, translated from the original JavaFX styles.css
public/images/                         Logo and icons from the original app
public/uploads/items/                  Uploaded item photos
storage/app/private/                   Claim proof photos (only the claimant + admins can view)
```

---

## Troubleshooting

**`Could not open input file: artisan`**: you're not in the project folder. Use `cd /d <path-to>\pup-lostfound`, and check with `dir` that `artisan` is listed as a file.

**`SQLSTATE[HY000] [2002] No connection could be made`**: MySQL isn't running. Start it in XAMPP.

**`Unknown database 'pup_lostfound'`**: create the database in phpMyAdmin (step 2).

**`Access denied for user 'root'`**: your MySQL root has a password. Put it in `.env` after `DB_PASSWORD=`, then run `php artisan config:clear`.

**`Unknown column 'is_anonymous'` (or similar)**: you're using an old database with the new version. Run `php artisan migrate`.

**Forgot a Super Admin password** (and no other Super Admin can help): in the XAMPP Shell, inside the project folder, run

```
php artisan lf:reset-password admin@pup.edu.ph
```

It asks for the new password. This works for any account.

**Port 8000 already in use**: run `php artisan serve --port=8080` and open <http://127.0.0.1:8080>.

**Photo upload fails with "must be an image"**: in `C:\xampp\php\php.ini` (or `D:\xampp\php\php.ini`), make sure `extension=fileinfo` has no `;` in front, then reopen the shell.

**No `.env` file / "No application encryption key"**: copy `.env.example` to `.env`, then run `php artisan key:generate`.


## Dashboard and Activity Log (rules)

- **Dashboard:** the **All** view shows only items that are still open (LOST and FOUND). CLAIMED and RESOLVED items leave the board and stay under their own filters.
- **Activity Log:** Super Admin only. It records Admin / Super Admin actions (log in, release or resolve items, approve or reject a claim, log items, create / promote / demote / delete accounts, reset passwords). Only the latest 1,000 entries are kept.
- **Super Admins:** at most 2. To promote a third, a Super Admin first uses **DEMOTE TO ADMIN** on the other one (you can't demote yourself).

## Firebase Realtime Database (copy of the data)

MySQL stays the main database. Every time an **item**, a **claim** or an **admin account** is saved or deleted, the system also writes it to the Firebase Realtime Database (`items/{id}`, `claims/{id}`, `users/{id}`), so the data can be seen live in the Firebase console.

- Needs `composer require kreait/laravel-firebase`, `FIREBASE_CREDENTIALS` and `FIREBASE_DATABASE_URL` in `.env`. Add `FIREBASE_HTTP_CLIENT_TIMEOUT=5` so a bad connection never makes the system hang. `FIREBASE_SYNC=false` switches the copy off.
- **First time (or to repair):** `php artisan firebase:sync` copies everything that is already in MySQL. Add `--clear` to empty the three nodes in Firebase first.
- **Never copied:** passwords, contact numbers, ID numbers, the finder's details, the claimant's description and photos. Old student/faculty accounts are not copied.
- If Firebase is unreachable the system keeps working; the problem is only written to `storage/logs/laravel.log`, and `php artisan firebase:sync` can catch up later.
- Lock the Firebase rules (Realtime Database > Rules: `.read` and `.write` = `false`). The server uses a service account key, so the system is not affected.
