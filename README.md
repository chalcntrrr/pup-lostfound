# PUPSRC Lost and Found (Laravel + XAMPP)

The campus Lost and Found system, moved from the original JavaFX desktop app to a web app built with **Laravel 12, PHP 8.2 and XAMPP's MySQL (MariaDB)**.

Found items are **surrendered to the Lost and Found office**: only admins log FOUND items, so the office always holds what it releases. Students and faculty (professors) report their own lost items, check the dashboard for their item, and claim it. People **without an account** can still have a lost item posted: an admin logs it for them as a **walk-in lost report**.

The screens keep the **original design**: same maroon navbar, LOST/FOUND item cards, "+" report menu and ☰ menu, LOST/FOUND ITEM REPORT form, FULL DETAILS, CLAIM VERIFICATION and ACCOUNT pages.

## What it does

There are four roles: **Student**, **Faculty** (professors/instructors, they sign up with an optional **Employee ID** instead of a Student ID), **Admin** (office staff) and **Super Admin** (head of the office, max 2). Faculty can do everything a student can (report lost items, claim found items). A Super Admin can do everything an Admin can, plus manage accounts.

In the table below, the **Student** column also applies to **Faculty**.

| | Student | Admin |
|---|---|---|
| Create an account | ✔ (students and faculty) | ✘ (added by a Super Admin) |
| Log in (email, student ID **or** employee ID) | ✔ | ✔ |
| Dashboard: browse, search, filter (All / LOST / FOUND / CLAIMED / RESOLVED) | ✔ | ✔ |
| Report a lost item (photo, location, date, time) | ✔ (own account) | ✔ **walk-in report**: for an owner with no account (owner name, ID no. and contact typed in by the admin, visible to admins only) |
| Log a surrendered (FOUND) item, with the finder's details or "Keep Anonymous" | ✘ (surrender it at the office) | ✔ (finder details visible to admins only) |
| Edit / delete **own** lost report while it's still open | ✔ | edit any report; delete only items not yet released |
| Claim a found item (Claim Verification: description + proof of claim photo) | ✔ | ✘ |
| My Reports, My Claims, Account page with history | ✔ | ✔ (no My Claims) |
| Approve / reject claims (admin enters the ID the claimant presented) | ✘ | ✔ |
| Claim Slip (admin copy + claimant's receipt, printable) | own approved claims | ✔ |
| RESOLVE a report | own lost item ("I got it back") | ✔, including "resolve with an existing found item" (ID checked + claim slip) |
| "Possible match found" on own lost reports | ✔ | ✔ |
| See a lost report's Student ID / contact | own reports only | ✔ |
| Users list | ✘ | ✔ (view) |
| Change own password (Account page) | ✔ | ✔ |
| **Super Admin only:** add Admin / Super Admin accounts, promote an Admin to Super Admin (max 2; **demote** one first when full), reset a forgotten password, delete accounts, view the **Activity Log** | ✘ | ✘ |

When an admin approves a claim, the item becomes **CLAIMED**, any other pending claims on it are rejected automatically, and a **Claim Slip** (same layout as the Java app's PDF) can be printed or saved as PDF from the browser.

**RESOLVE** works like the Java RESOLVE dialog: an admin can resolve a lost report **with an existing found item** (picked from the possible matches; the found item becomes CLAIMED and the two are linked), or **outside the system**. Resolving never deletes anything; it only changes the status.

FULL DETAILS also shows **possible matches**, using the matching logic from the Java app, and a student's lost report is flagged **POSSIBLE MATCH FOUND** in My Reports when a similar item is at the office. When a claim is approved, the claimant's matching lost report is closed automatically.

Found items show how many **days they have been at the office**; after 30 days the admin is reminded that the item can be resolved outside the system (donated or disposed, following office policy).

Accounts tied to official records (received items, logged found items, reviewed claims) **can't be deleted**, and neither can items already released to their owner, so claim slips and history stay intact.

An item's **status can't be edited by hand**: it changes only through claim approval or RESOLVE, which are both recorded. A student whose claim was rejected can't claim the same item again; they go to the office instead.

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

### Updating to the Faculty + Walk-in version
Copy your old `.env` into the new folder first, start MySQL, then run `php artisan migrate`. It adds the faculty role, `employee_number`, the walk-in owner columns and makes `claims.user_id` optional. Your accounts, reports and claims are kept. (The new sample faculty account and sample reports only appear after `php artisan migrate:fresh --seed`, which wipes the data.)

### Sample accounts

| Role | Login | Password |
|---|---|---|
| Super Admin | `admin@pup.edu.ph` | `admin123` |
| Admin | `staff@pup.edu.ph` | `staff123` |
| Student | `student@pup.edu.ph` (or `2023-00123-SR-0`) | `student123` |
| Faculty | `prof@pup.edu.ph` (or `FAC-2019-0042`) | `prof123` |

The Create Account page is for **students and faculty** (pick one at the top of the form). New **Admins** (and a second **Super Admin**) are added by a Super Admin through **Account → + Add Another Admin**. If someone forgets their password, a Super Admin can set a new one on the **Users** page; everyone can change their own password on the **Account** page. Super Admin accounts can't be deleted, and there can be at most 2.

When you upgrade an existing database, `php artisan migrate` makes the first admin account the Super Admin.

---

## The database

In phpMyAdmin, open `pup_lostfound`. The system's own tables:

- `users`: every account (`role` is `student`, `faculty`, `admin` or `superadmin`; `student_number` for students, `employee_number` for faculty; `password` is hashed)
- `items`: lost and found reports (`type` = lost/found, `status` = open/claimed/resolved, `resolution` = matched/outside, `matched_item_id`, `time_happened`, and for found items `is_anonymous` + `finder_name` / `finder_student_id` / `finder_contact`; for walk-in lost reports `owner_name` / `owner_id_number` / `owner_contact`, and `user_id` is then the admin who logged it)
- `claims`: claims (`status` = pending/approved/rejected, `id_presented` filled in by the admin on approval; `user_id` is empty when an item is released to a walk-in owner, whose name and ID number are then kept in `claimant_name` / `claimant_id_number`)
- `categories`: Electronics, Bags & Wallets, IDs & Documents, Clothing, Others
- `activity_logs`: what Admins and Super Admins did and when (student/faculty activity is not logged; only the latest 1,000 entries are kept)

The other tables (`sessions`, `cache`, `jobs`, `migrations`, …) are Laravel's own.

To wipe everything and start over with only the sample data:

```
php artisan migrate:fresh --seed
```

---

## Where things are in the code

```
routes/web.php                         All pages + who is allowed (guest / student / admin)
app/Http/Middleware/EnsureRole.php     Blocks students from admin pages and admins from claiming
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

- **Dashboard (everyone):** the **All** view shows only items that are still open (LOST and FOUND). CLAIMED and RESOLVED items leave the board. Students and faculty see only the All / LOST / FOUND filters; their own history is under **My Reports** and **My Claims**. Admins can still open the CLAIMED and RESOLVED filters.
- **Activity Log:** Super Admin only. It records Admin / Super Admin actions (log in, approve or reject a claim, log or resolve items, create / promote / demote / delete accounts, reset passwords). Student and faculty activity is not recorded, and only the latest 1,000 entries are kept.
- **Super Admins:** at most 2. To promote a third, a Super Admin first uses **DEMOTE TO ADMIN** on the other one (you can't demote yourself).
