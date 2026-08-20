# 📦 KORAT ESPORT - PROJECT CONTEXT PACK & ARCHITECTURE GUIDE

> **Repository:** `Koratesport-Project`  
> **Last Updated:** August 2026  
> **Target Audience:** Engineering Team, Lead Developers, Onboarding Developers  

---

## 1. 🎯 วัตถุประสงค์และภาพรวมของระบบ (Project Purpose & Overview)

**Korat Esport** คือระบบเว็บแอปพลิเคชันสำหรับบริหารจัดการการแข่งขันกีฬาอีสปอร์ต (Esports Tournament Platform) แบบครบวงจรของจังหวัดนครราชสีมา มีหน้าที่หลัก 5 ด้าน:
1. **Tournament Operations & Automation:** จัดการแข่งขันตั้งแต่การเปิดรับสมัคร, แยกประเภทเพศ/อายุ (Male, Female, Open), กำหนดระบบ Best-of-N, สร้างสายการแข่งขันอัตโนมัติ (Single Elimination, Double Elimination, Round Robin), และส่งต่อผู้ชนะ/ผู้แพ้ (Bracket Progression Engine)
2. **Athlete & Team Ecosystem:** ฐานข้อมูลนักกีฬา สโมสรทีม การโอนย้ายสมาชิก การส่งต่อหัวหน้าทีม และระบบเคลมโปรไฟล์นักกีฬาเดิม (Profile Claiming)
3. **Real-time On-site Check-in (QR Code):** ระบบออก QR Token และสแกนเช็คอินนักกีฬาหน้างานจริง พร้อมเก็บบันทึกสถิตินักกีฬาตัวจริง (Confirmed Athletes) ถาวร
4. **Leaderboard & Ranking Engine:** คำนวณคะแนนสะสมแบบเรียลไทม์ ชนะ +3, เสมอ +1, แพ้ +0 แยกตามหมวดเกมและประเภท
5. **Hospitality & Media Hub:** แนะนำโรงแรม/ที่พักใกล้สนามแข่งขันพร้อมพิกัด Google Maps, ข่าวสารประชาสัมพันธ์ และแกลลอรี่ภาพกิจกรรม

---

## 2. 🛠️ สถาปัตยกรรมและเทคโนโลยี (Tech Stack & Architecture)

- **Backend:** PHP (Procedural + PDO Layer), รองรับ PHP 7.4+ และ PHP 8.x
- **Database:** MySQL / MariaDB (InnoDB, UTF-8 mb4 Unicode)
- **Frontend:** HTML5, Tailwind CSS (via CDN), Vanilla CSS (`assets/css/style.css`), FontAwesome 6, Google Fonts (Kanit, Orbitron, Share Tech Mono)
- **Client Scripting & Effects:** Vanilla JS (`assets/js/main.js`), AOS (Animate on Scroll), Vanilla Tilt JS, QR Server API
- **Web Server:** Apache (XAMPP Environment)
- **Security Mechanisms:**
  - PDO Prepared Statements 100% ป้องกัน SQL Injection
  - CSRF Token Verification ทุก POST Actions (`verifyCsrfToken()`)
  - Password Hashing ผ่าน `password_hash($pwd, PASSWORD_DEFAULT)` (BCrypt)
  - Role-based Access Control Guard (`requireRole('admin')`, `requireLogin()`)
  - Session Regeneration เมื่อผู้ใช้เข้าสู่ระบบ (`session_regenerate_id(true)`)
  - Normalized Security Questions ป้องกันปัญหา Case Sensitivity ตอนรีเซ็ตรหัสผ่าน

---

## 3. 📂 แผนผังโครงสร้างไฟล์และโฟลเดอร์ (File & Directory Map)

```plaintext
Koratesport-Project/
├── index.php                  # Root entry point (Redirects to pages/index.php)
├── gen.php                    # Tool สุ่ม hash รหัสผ่านสำหรับ Admin setup
├── README.md                  # คำแนะนำการติดตั้งระบบเบื้องต้น
├── PROJECT_CONTEXT_PACK.md    # เอกสาร Context Pack สถาปัตยกรรมระบบฉบับสมบูรณ์
│
├── 📁 admin/                  # [Admin Panel] หน้าจัดการสำหรับผู้ดูแลระบบ
│   ├── dashboard.php          # แดชบอร์ดวิเคราะห์สถิตินักกีฬาจริง/ทีม/ทัวร์นาเมนต์/กรองรายปี
│   ├── manage-tournament.php  # CRUD ทัวร์นาเมนต์, สร้างสายแข่ง, Export CSV ผลการแข่งขัน
│   ├── record-match.php       # บันทึกคะแนนแมตช์, เกมย่อย BO3/BO5, ส่งต่อสายแข่ง
│   ├── manage-score.php       # ตรวจสอบและบันทึกคะแนนแมตช์ทั่วไป
│   ├── manage-teams.php       # จัดการทีมที่สมัคร, อนุมัติ/ปฏิเสธ, เพิ่มเดี่ยว/ทีมแบบ Manual
│   ├── checkin-teams.php      # สแกน QR Code เช็คอินนักกีฬาหน้างาน
│   ├── manage-members.php     # จัดการผู้ใช้งาน, ระงับบัญชี (Suspend), รีเซ็ตรหัสผ่านชั่วคราว
│   ├── manage-news.php        # สร้าง/แก้ไข/ลบ ข่าวสาร พร้อมระบบอัปโหลดภาพปก
│   ├── manage-gallery.php     # จัดการอัลบั้มและอัปโหลดรูปภาพกิจกรรมแบบ Batch
│   └── recommended-lodging.php# จัดการข้อมูลที่พักแนะนำสำหรับนักกีฬา พร้อมรูปและระยะทาง
│
├── 📁 auth/                   # [Authentication] ระบบสิทธิ์และสมาชิก
│   ├── login.php              # เข้าสู่ระบบ
│   ├── register.php           # สมัครสมาชิกนักกีฬาใหม่ (Default Role: athlete)
│   ├── logout.php             # ออกจากระบบและล้าง Session
│   ├── forgot-password.php    # กู้คืนรหัสผ่านผ่านคำถามกันลืม
│   └── forgot-username.php    # ค้นหา Username ผ่านอีเมล
│
├── 📁 config/                 # [Configuration] ตั้งค่าระบบ
│   └── db.php                 # PDO Connection & ฟังก์ชันตรวจสอบเกมเดี่ยว `isSoloGame()`
│
├── 📁 includes/               # [Core Engines & Helpers] ฟังก์ชันแกนกลาง
│   ├── auth.php               # Helper ยืนยันตัวตน, CSRF Token, Role Guard
│   ├── bracket.php            # Engine สร้างสายแข่ง Single/Double Elimination, Seed, Bye
│   ├── round_robin.php        # Engine สร้างตารางแข่งแบบพบกันหมด Circle Method
│   ├── ranking.php            # Engine คำนวณคะแนนสะสมและ Leaderboard อัตโนมัติ
│   ├── upload.php             # Helper ตรวจสอบประเภทไฟล์และอัปโหลดรูปภาพ
│   ├── public_nav.php         # Navbar สำหรับหน้าผู้ใช้งานทั่วไป
│   └── admin_nav.php          # Navbar สำหรับ Admin Panel
│
├── 📁 pages/                  # [Public & Athlete Portal] หน้าฝั่งผู้ใช้งาน
│   ├── index.php              # หน้าแรก (Hero, ทัวร์นาเมนต์สด, Top 5 Leaderboard, Stats)
│   ├── tournaments.php        # รายการทัวร์นาเมนต์ทั้งหมด
│   ├── tournament-detail.php  # หน้ารายละเอียดทัวร์นาเมนต์ สายการแข่งขัน และผลสกอร์
│   ├── register-tournament.php# หน้าสมัครเข้าร่วมทัวร์นาเมนต์ (ทีม หรือ เดี่ยว)
│   ├── claim-profile.php      # หน้าเคลมโปรไฟล์เก่านักกีฬา RoV หรือสร้างโปรไฟล์ใหม่
│   ├── profile.php            # โปรไฟล์ส่วนตัว, เปลี่ยนรหัสผ่าน, จัดการทีมของฉัน
│   ├── my-team.php            # ทางลัดเปิดทีมที่ตนเองสังกัด
│   ├── my-checkin.php         # หน้าแสดง QR Code ประจำตัว/ทีม สำหรับเช็คอินหน้างาน
│   ├── create-team.php        # สร้างทีมสโมสรใหม่
│   ├── team-manage.php        # กัปตันจัดการลูกทีม ปรับบทบาท ส่งต่อสิทธิ์กัปตัน
│   ├── team-profile.php       # ดูข้อมูลทีม สถิติ และไลน์อัพสมาชิก
│   ├── teams.php              # รายชื่อทีมสโมสรทั้งหมด
│   ├── player-profile.php     # ข้อมูลนักกีฬารายบุคคล สถิติ และประวัติการแข่ง
│   ├── players.php            # รายชื่อนักกีฬาทั้งหมดในระบบ
│   ├── ranking.php            # ตารางคะแนนสะสม Leaderboard แยกตามเกมและเพศ/ประเภท
│   ├── news.php               # ข่าวสารอีสปอร์ตทั้งหมด
│   ├── news-detail.php        # เนื้อหาข่าวฉบับเต็ม
│   ├── gallery.php            # แกลลอรี่ภาพกิจกรรมและอัลบั้ม
│   └── lodging.php            # ที่พักแนะนำรอบโคราชสำหรับนักกีฬา
│
└── 📁 assets/                 # [Static Assets]
    ├── css/style.css          # Core Styling & Theme
    ├── js/main.js             # Client Scripting (AOS, CountUp, Particles, Nav Scroll)
    ├── img/                   # ภาพโลโก้และ Placeholder
    └── uploads/               # โฟลเดอร์เก็บไฟล์ภาพอัปโหลด (Auto-generated)
```

---

## 4. 🗄️ โครงสร้างฐานข้อมูล (Database Schema & Relationships)

### รายละเอียดตารางหลัก (Core Tables Summary)

| ตาราง (Table) | หน้าที่และความสำคัญ |
| :--- | :--- |
| **`users`** | บัญชีผู้ใช้งานระบบ (`user_id`, `username`, `email`, `password_hash`, `role`, `status`, `security_question`, `security_answer_hash`) |
| **`players`** | ข้อมูลโปรไฟล์นักกีฬา (`player_id`, `user_id`, `display_name`, `avatar_path`, `bio`, `gender`, `category`) เชื่อมต่อแบบ 1:1 กับ `users` |
| **`teams`** | ข้อมูลทีมสโมสร (`team_id`, `name`, `game_id`, `captain_player_id`, `logo_path`, `category`, `is_solo_wrapper`) |
| **`team_members`** | สมาชิกภายในทีม (`team_member_id`, `team_id`, `player_id`, `role`, `is_active`, `joined_at`) |
| **`games`** | ข้อมูลชนิดเกม (`game_id`, `name`, `play_mode` ['solo' / 'team'], `is_active`) |
| **`tournaments`** | ทัวร์นาเมนต์ (`tournament_id`, `name`, `game_id`, `format`, `best_of`, `max_teams`, `prize_pool`, `registration_start`, `registration_end`, `start_date`, `status`) |
| **`tournament_registrations`** | การสมัครแข่ง (`tournament_registration_id`, `tournament_id`, `team_id`, `player_id`, `category`, `status`, `qr_code_token`, `checkin_status`) |
| **`matches`** | แมตช์การแข่งขัน (`match_id`, `tournament_id`, `round_number`, `match_index`, `team1_id`, `team2_id`, `team1_score`, `team2_score`, `winner_team_id`, `status`, `bracket_type`, `best_of`) |
| **`match_games`** | สกอร์ของแต่ละเกมในซีรีส์ Best-of-N (`match_game_id`, `match_id`, `game_number`, `team1_score`, `team2_score`, `winner_team_id`) |
| **`bracket_edges`** | กราฟเส้นทางส่งต่อสายการแข่งขัน (`match_id`, `next_match_id`, `next_slot`, `loser_next_match_id`, `loser_next_slot`) |
| **`player_checkin_history`** | ประวัติการเช็คอินของนักกีฬาจริงแบบถาวร เพื่อใช้คำนวณ Confirmed Athletes |
| **`team_rankings` / `player_rankings`** | ตารางคะแนนสะสม Leaderboard (`game_id`, `team_id`/`player_id`, `category`, `points`, `wins`, `losses`) |
| **`news`** | ข่าวสารและประชาสัมพันธ์ (`news_id`, `title`, `content`, `image_path`, `status`, `created_by`, `created_at`) |
| **`gallery_albums` / `gallery`** | อัลบั้มและภาพกิจกรรม (`album_id`, `title`, `description`, `image_path`) |
| **`accommodations`** | ที่พักแนะนำรอบสนามแข่งขัน (`accommodation_id`, `name`, `address`, `image_path`, `distance`, `contact`, `map_url`) |

---

## 5. 🔄 กระบวนการทำงานสำคัญ (Core Logic & Workflow Engines)

### 5.1 ระบบสร้างสายการแข่งขัน (Bracket Engine Logic - `includes/bracket.php`)
1. **Seeding:** ดึงรายชื่อทีมที่ได้รับอนุมัติ/เช็คอิน จัดเรียงตามคะแนนสะสมใน Leaderboard หรือลำดับการสมัคร
2. **Category Separation:** แยกรุ่นการแข่งขัน (Male, Female, Open) เป็นสายอิสระต่อกันโดยอัตโนมัติ
3. **Power of Two & Bye Handling:** ขยายขนาดสายแข่งเป็น $2^n$ (เช่น 4, 8, 16, 32, 64, 128) และแก้ปัญหาทีมบาย (`resolveByeIfNeeded()`) หากคู่แข่งไม่มีตัวตน ระบบจะส่งผู้มีตัวตนชนะบายขึ้นรอบต่อไปทันที
4. **Double Elimination Routing:** บันทึกเส้นทางของผู้ชนะขึ้นสายบน และส่งผู้แพ้ไปยังสายล่างผ่านตาราง `bracket_edges` รองรับรอบ Grand Final และ Reset Match

### 5.2 ระบบคำนวณคะแนนและจัดอันดับ (Ranking Engine - `includes/ranking.php`)
- เมื่อผลแมตช์ถูกบันทึกเป็น `completed` หรือ `walkover`:
  - **ผู้ชนะ (Winner):** ได้รับ **+3 คะแนน** (Win +1)
  - **เสมอ (Draw):** ได้รับ **+1 คะแนน** (Draw +1)
  - **ผู้แพ้ (Loser):** ได้รับ **+0 คะแนน** (Loss +1)
- ระบบอัปเดตคะแนนลงตาราง `team_rankings` (ระดับทีม), `player_rankings` (ระดับผู้เล่นเดี่ยว และสมาชิกทุกคนในทีม) แบบ `ON DUPLICATE KEY UPDATE`

---

## 6. 🚀 วิธีการติดตั้งและรันระบบในเครื่อง Local (Setup & Execution Guide)

1. **ดาวน์โหลด/ติดตั้ง XAMPP:**
   - ตรวจสอบให้แน่ใจว่า Apache และ MySQL ทำงานปกติผ่าน XAMPP Control Panel
2. **วางโฟลเดอร์ใน `htdocs`:**
   - โฟลเดอร์ของโปรเจกต์อยู่ที่: `C:\xampp\htdocs\Koratesport-Project\`
3. **สร้างฐานข้อมูล MySQL:**
   - เปิดบราวเซอร์ไปที่ `http://localhost/phpmyadmin/`
   - สร้างฐานข้อมูลชื่อ: `korat_esport` (Collation: `utf8mb4_unicode_ci`)
   - Import ไฟล์ SQL Schema ของระบบ
4. **การเข้าใช้งาน:**
   - หน้าหลักผู้ใช้งาน: `http://localhost/Koratesport-Project/` (จะ Forward ไปที่ `pages/index.php` อัตโนมัติ)
   - หน้าผู้ดูแลระบบ: `http://localhost/Koratesport-Project/admin/dashboard.php`
   - หน้าสมัครสมาชิก: `http://localhost/Koratesport-Project/auth/register.php`
5. **สร้างบัญชี Admin คนแรก:**
   - สมัครสมาชิกปกติผ่านหน้าเว็บ
   - เข้า phpMyAdmin ไปที่ตาราง `users` เปลี่ยนค่า `role` ของบัญชีนั้นจาก `'athlete'` เป็น `'admin'`

---

## 7. ⚠️ ข้อควรระวังและแนวทางในการพัฒนาต่อ (Guidelines for Future Features)

1. **Dynamic Base Path:** ทุกการเชื่อมโยงลิงก์ในหน้าเว็บควรใช้ Path ที่ยืดหยุ่น (Dynamic/Relative) เพื่อไม่ให้เกิดปัญหาเมื่อเปลี่ยนชื่อโฟลเดอร์โปรเจกต์
2. **CSRF Protection:** ทุกหน้าที่มีแบบฟอร์ม POST ต้องเรียก `generateCsrfToken()` ในฟอร์ม และ `verifyCsrfToken()` ใน Controller เสมอ
3. **Prepared Statements:** ห้ามต่อ SQL string ตรงๆ ให้ใช้ Parameterized Query ผ่าน `$pdo->prepare()` เสมอ
4. **Solo Game Handling:** หากมีการเพิ่มเกมเดี่ยวใหม่ ให้ไปเพิ่มชื่อคีย์เวิร์ดในฟังก์ชัน `isSoloGame()` ที่ [config/db.php](file:///c:/xampp/htdocs/Koratesport-Project/config/db.php)
