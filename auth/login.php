<?php
// auth/login.php
require_once '../config/db.php';
require_once '../includes/auth.php';

$error = trim($_GET['error'] ?? '');
$justRegistered = isset($_GET['registered']);
$resetSuccess = isset($_GET['reset_success']);
$savedLogin = $_COOKIE['remembered_login'] ?? '';

// ดึงข้อมูลสถิติสำหรับ Infographic ฝั่งซ้าย
$totalTeamsLogin = $pdo->query("
    SELECT COUNT(*) FROM teams t
    JOIN players p ON p.player_id = t.captain_player_id
    WHERE p.user_id IS NOT NULL
")->fetchColumn();

$totalTournamentsLogin = $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$totalGamesLogin = $pdo->query("SELECT COUNT(*) FROM games WHERE is_active = 1")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);

        try {
            $user = loginUser($pdo, $username, $password);

            // จัดการคุกกี้จดจำชื่อผู้ใช้/อีเมล
            if ($rememberMe) {
                setcookie('remembered_login', $username, time() + (86400 * 30), "/");
            } else {
                if (isset($_COOKIE['remembered_login'])) {
                    setcookie('remembered_login', '', time() - 3600, "/");
                }
            }

            // ส่งแต่ละ role ไปหน้าที่เหมาะกับตัวเอง
            if ($user['role'] == 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../pages/index.php');
            }
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="th" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Korat Esport</title>
    <!-- Google Fonts & FontAwesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,300;0,400;0,600;0,700;1,800&family=Orbitron:wght@700;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#FF5500',
                            glow: '#FF7700',
                            dark: '#0A0A0C',
                        }
                    },
                    fontFamily: {
                        sans: ['Kanit', 'sans-serif'],
                        display: ['Orbitron', 'sans-serif']
                    },
                    boxShadow: {
                        'orange-glow': '0 4px 20px rgba(255, 85, 0, 0.4)',
                        'orange-glow-lg': '0 8px 30px rgba(255, 85, 0, 0.6)'
                    }
                }
            }
        }
    </script>

    <style>
        ::-webkit-scrollbar { display: none; }
        html, body { -ms-overflow-style: none; scrollbar-width: none; }
        
        @keyframes kenBurnsLogin {
            0% { transform: scale(1); }
            50% { transform: scale(1.07); }
            100% { transform: scale(1); }
        }

        .bg-esports-arena {
            background: linear-gradient(to right, rgba(15, 17, 23, 0.45), rgba(15, 17, 23, 0.25)),
                url('https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            animation: kenBurnsLogin 20s infinite alternate ease-in-out;
        }

        .glass-panel-left {
            background: rgba(10, 10, 12, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-panel-light {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .glass-input-light {
            background: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            transition: all 0.25s ease;
        }

        .glass-input-light:focus-within {
            background: #FFFFFF;
            border-color: #FF5500;
            box-shadow: 0 0 12px rgba(255, 85, 0, 0.25);
        }

        .shimmer-line {
            background: linear-gradient(90deg, transparent, #FF5500, #ffaa33, #FF5500, transparent);
            background-size: 200% 100%;
            animation: shimmerAccent 4s infinite linear;
        }

        @keyframes shimmerAccent {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .shine-btn { position: relative; overflow: hidden; }
        .shine-btn::after {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: linear-gradient(60deg, transparent 30%, rgba(255, 255, 255, 0.4) 50%, transparent 70%);
            transform: rotate(30deg) translateX(-100%); transition: transform 0.7s ease;
        }
        .shine-btn:hover::after { transform: rotate(30deg) translateX(100%); }
    </style>
</head>

<body class="bg-slate-900 text-gray-100 font-sans h-full min-h-screen overflow-x-hidden antialiased">

    <div class="fixed inset-0 bg-esports-arena z-0"></div>

    <div class="relative z-10 min-h-screen flex flex-col lg:flex-row">

        <!-- LEFT SIDE -->
        <div class="hidden lg:flex flex-1 flex-col justify-between p-12 relative overflow-hidden">
            <a href="../pages/index.php" class="flex items-center gap-4 group w-fit">
                <img src="../assets/img/logo.png" alt="Korat Esport" class="h-14 w-auto drop-shadow-lg">
                <div>
                    <h1 class="font-display font-black text-2xl tracking-wider text-white">KORAT <span class="text-brand-orange">ESPORT</span></h1>
                    <p class="text-xs tracking-widest text-gray-200 uppercase font-semibold">Official Arena & Hub</p>
                </div>
            </a>

            <div class="my-auto max-w-xl space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-orange/20 border border-brand-orange/50 text-brand-orange text-xs font-bold uppercase tracking-widest backdrop-blur-md">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-orange animate-ping"></span>
                    Nakhon Ratchasima Gaming Hub
                </div>
                <h2 class="text-5xl font-black text-white leading-tight uppercase font-display">ศูนย์กลางการแข่งขัน <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-orange to-white">อีสปอร์ตระดับมืออาชีพ</span></h2>
                <p class="text-gray-300 text-sm leading-relaxed">
                    ยกระดับมาตรฐานการแข่งขันอีสปอร์ตจังหวัดนครราชสีมาอย่างเป็นทางการ ติดตามสายการแข่งขัน ผลคะแนนสด และทำเนียบนักกีฬาระดับแนวหน้า
                </p>
                
                <!-- Statistics Grid Infographics -->
                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-white/10">
                    <div class="glass-panel-left p-4 rounded-2xl border-l-4 border-l-brand-orange shadow-lg">
                        <div class="text-xs text-gray-200 font-bold uppercase tracking-wider">TEAMS REGISTERED</div>
                        <div class="text-2xl font-black font-display text-brand-orange mt-1 flex items-center gap-1">
                            <span data-countup="<?php echo $totalTeamsLogin; ?>">0</span><span class="suffix-span opacity-0 transition-opacity duration-300 text-xs font-normal">CLUBS</span>
                        </div>
                        <div class="text-[11px] text-gray-300 font-semibold mt-1">สโมสรอีสปอร์ต</div>
                    </div>

                    <div class="glass-panel-left p-4 rounded-2xl border-l-4 border-l-cyan-400 shadow-lg">
                        <div class="text-xs text-gray-200 font-bold uppercase tracking-wider">TOURNAMENTS</div>
                        <div class="text-2xl font-black font-display text-cyan-300 mt-1 flex items-center gap-1">
                            <span data-countup="<?php echo $totalTournamentsLogin; ?>">0</span><span class="suffix-span opacity-0 transition-opacity duration-300 text-xs font-normal">CUPS</span>
                        </div>
                        <div class="text-[11px] text-cyan-400/80 font-semibold mt-1">ทัวร์นาเมนต์ทางการ</div>
                    </div>

                    <div class="glass-panel-left p-4 rounded-2xl border-l-4 border-l-purple-500 shadow-lg">
                        <div class="text-xs text-gray-200 font-bold uppercase tracking-wider">GAMES TITLE</div>
                        <div class="text-2xl font-black font-display text-purple-300 mt-1 flex items-center gap-1">
                            <span data-countup="<?php echo $totalGamesLogin; ?>">0</span><span class="suffix-span opacity-0 transition-opacity duration-300 text-xs font-normal">TITLES</span>
                        </div>
                        <div class="text-[11px] text-purple-400/80 font-semibold mt-1">Mobile & PC</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Login Form -->
        <div class="w-full lg:w-[500px] flex items-center justify-center p-6 sm:p-10 z-10 my-auto min-h-screen">
            <div class="w-full glass-panel-light p-10 rounded-3xl relative overflow-hidden text-slate-800">
                <div class="absolute top-0 left-0 right-0 h-1.5 shimmer-line"></div>

                <div class="mb-8">
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">เข้าสู่ระบบ</h2>
                    <p class="text-sm text-slate-500 mt-2 font-medium">กรอกชื่อผู้ใช้หรืออีเมลเพื่อเข้าสู่บัญชีของคุณ</p>
                </div>

                <?php if ($resetSuccess): ?>
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-lg text-emerald-500"></i>
                        <span>เปลี่ยนรหัสผ่านสำเร็จแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่</span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold flex items-center gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-lg text-rose-500 shrink-0"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div>
                        <label for="username_input" class="block text-xs font-bold text-slate-700 uppercase mb-2">ชื่อผู้ใช้ หรือ อีเมล</label>
                        <div class="glass-input-light rounded-xl overflow-hidden flex items-center">
                            <span class="pl-4 text-slate-400"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="username" id="username_input" autocomplete="username" required 
                                value="<?php echo htmlspecialchars($savedLogin); ?>"
                                class="w-full bg-transparent px-4 py-3.5 text-sm text-slate-900 focus:outline-none font-medium" placeholder="Username หรือ Email">
                        </div>
                    </div>

                    <div>
                        <label for="password_input" class="block text-xs font-bold text-slate-700 uppercase mb-2">รหัสผ่าน</label>
                        <div class="glass-input-light rounded-xl overflow-hidden flex items-center">
                            <span class="pl-4 text-slate-400"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="password_input" autocomplete="current-password" required 
                                class="w-full bg-transparent px-4 py-3.5 text-sm text-slate-900 focus:outline-none font-medium" placeholder="••••••••">
                            <button type="button" onclick="togglePasswordVisibility()" class="px-4 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer" title="แสดง/ซ่อนรหัสผ่าน">
                                <i id="password_eye_icon" class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 text-slate-600 cursor-pointer select-none">
                            <input type="checkbox" name="remember_me" value="1" <?php echo !empty($savedLogin) ? 'checked' : ''; ?> 
                                class="w-4 h-4 rounded border-slate-300 text-brand-orange focus:ring-brand-orange accent-[#FF5500]">
                            <span class="font-medium">จดจำการเข้าสู่ระบบ</span>
                        </label>
                        <a href="forgot-password.php" class="font-bold text-brand-orange hover:underline">ลืมรหัสผ่าน?</a>
                    </div>

                    <button type="submit" class="shine-btn w-full py-4 rounded-xl font-bold text-white uppercase bg-brand-orange hover:bg-brand-glow transition-all shadow-md cursor-pointer">
                        เข้าสู่ระบบ
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t text-center text-sm text-slate-600">
                    ยังไม่มีบัญชี? <a href="register.php" class="text-brand-orange font-bold hover:underline">สมัครสมาชิกที่นี่</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชันสลับการมองเห็นรหัสผ่าน
        function togglePasswordVisibility() {
            const input = document.getElementById('password_input');
            const icon = document.getElementById('password_eye_icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Count-Up Animation
        document.addEventListener('DOMContentLoaded', () => {
            const counters = document.querySelectorAll('[data-countup]');
            counters.forEach(c => {
                const target = +c.getAttribute('data-countup');
                let count = 0;
                const increment = Math.max(1, Math.ceil(target / 20));
                const updateCount = () => {
                    count += increment;
                    if (count < target) {
                        c.innerText = count;
                        setTimeout(updateCount, 40);
                    } else {
                        c.innerText = target;
                        const suffix = c.closest('div').querySelector('.suffix-span');
                        if (suffix) suffix.classList.remove('opacity-0');
                    }
                };
                updateCount();
            });
        });
    </script>
</body>
</html>