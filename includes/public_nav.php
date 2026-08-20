<?php
// includes/public_nav.php
// ต้อง include includes/auth.php มาก่อนแล้ว (เพื่อให้ session_start() ทำงานและเช็ค login ได้)

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = rtrim(dirname($scriptDir), '/\\');
if ($basePath === '' || $basePath === '/' || $basePath === '\\') {
    $basePath = rtrim($scriptDir, '/\\');
}
?>
<nav class="<?php echo (isLoggedIn() && $_SESSION['role'] == 'admin') ? 'admin-nav' : 'public-nav'; ?>">
    <?php if (isLoggedIn() && $_SESSION['role'] == 'admin'): ?>
        <span>สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
        <a href="<?php echo $basePath; ?>/admin/dashboard.php">หน้าหลัก</a>
        <a href="<?php echo $basePath; ?>/admin/manage-tournament.php">จัดการทัวร์นาเมนต์</a>
        <a href="<?php echo $basePath; ?>/admin/manage-teams.php">จัดการทีมสมัคร</a>
        <a href="<?php echo $basePath; ?>/admin/manage-members.php">จัดการสมาชิก</a>
        <a href="<?php echo $basePath; ?>/admin/manage-news.php">จัดการข่าวสาร</a>
        <a href="<?php echo $basePath; ?>/admin/manage-gallery.php">จัดการแกลลอรี่</a>
        <a href="<?php echo $basePath; ?>/admin/recommended-lodging.php">ที่พักแนะนำ</a>
        <a href="<?php echo $basePath; ?>/admin/manage-score.php">บันทึกผลแมตช์</a>
        <a href="<?php echo $basePath; ?>/admin/checkin-teams.php">เช็คอินทีม</a>
        <a href="<?php echo $basePath; ?>/auth/logout.php">ออกจากระบบ</a>
    <?php else: ?>
        <a href="<?php echo $basePath; ?>/pages/index.php" class="logo">
            <img src="<?php echo $basePath; ?>/assets/img/logo.png" alt="Korat Esport" class="nav-logo-img">
            KORAT ESPORT
        </a>
        <a href="<?php echo $basePath; ?>/pages/tournaments.php">ทัวร์นาเมนต์</a>
        <a href="<?php echo $basePath; ?>/pages/news.php">ข่าวสาร</a>
        <a href="<?php echo $basePath; ?>/pages/gallery.php">แกลลอรี่</a>
        <a href="<?php echo $basePath; ?>/pages/lodging.php">ที่พักแนะนำ</a>
        <a href="<?php echo $basePath; ?>/pages/teams.php">ทีม</a>
        <a href="<?php echo $basePath; ?>/pages/players.php">นักกีฬา</a>
        <a href="<?php echo $basePath; ?>/pages/ranking.php">อันดับ</a>

        <?php if (isLoggedIn()): ?>
            <a href="<?php echo $basePath; ?>/pages/my-team.php">ทีมของฉัน</a>
            <a href="<?php echo $basePath; ?>/pages/my-checkin.php">QR Check-in</a>
            <a href="<?php echo $basePath; ?>/pages/profile.php">โปรไฟล์ของฉัน</a>
            <a href="<?php echo $basePath; ?>/auth/logout.php">ออกจากระบบ</a>
        <?php else: ?>
            <a href="<?php echo $basePath; ?>/auth/login.php">เข้าสู่ระบบ</a>
            <a href="<?php echo $basePath; ?>/auth/register.php">สมัครสมาชิก</a>
        <?php endif; ?>
    <?php endif; ?>
</nav>
<script src="<?php echo $basePath; ?>/assets/js/main.js" defer></script>