<?php
declare(strict_types=1);
require_once __DIR__ . '/db_connection.php';

$logoutUrl = !empty($_SESSION['is_admin']) ? '/hatrox-project/admin/logout.php' : '/hatrox-project/public/logout.php';

// Remember me (cookie)
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    if (is_string($token) && strlen($token) <= 255) {
        if ($stmt = $mysqli->prepare('SELECT id, full_name, email, is_admin, COALESCE(is_blocked,0) AS is_blocked FROM users WHERE remember_token = ?')) {
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if ((int)$row['is_admin'] !== 1 && (int)($row['is_blocked'] ?? 0) === 0) {
                    $_SESSION['user_id'] = (int)$row['id'];
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['is_admin'] = (int)$row['is_admin'];
                }
            }
            $stmt->close();
        }
    }
}

if (!empty($_SESSION['user_id']) && empty($_SESSION['is_admin'])) {
    if ($stmt = $mysqli->prepare('SELECT COALESCE(is_blocked,0) AS is_blocked FROM users WHERE id = ?')) {
        $uid = (int)$_SESSION['user_id'];
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->bind_result($is_blocked);
        if ($stmt->fetch() && (int)$is_blocked === 1) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    true
                );
            }
            session_unset();
            session_destroy();
            header('Location: /hatrox-project/public/login.php?blocked=1');
            exit;
        }
        $stmt->close();
    }
}

// Cart count
$cart_count = 0;
if (!empty($_SESSION['user_id'])) {
    if ($stmt = $mysqli->prepare('SELECT COALESCE(SUM(quantity),0) AS cnt FROM cart WHERE user_id = ?')) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($cart_count);
        $stmt->fetch();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HATRO:X</title>
    <meta name="description" content="HATRO:X - Luxury jewelry e-commerce" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- cdn  -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" referrerpolicy="no-referrer"></script>
    <script src="/hatrox-project/includes/header-gsap.js" defer></script>

    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              gold: '#d4af37',
              rose: '#b76e79',
              silver: '#c0c0c0',
            }
          }
        }
      }
    </script>
    <style>
      .sq-colon {
        display:inline-block;
        position:relative;
        width:0.6em;
        height:1em;
      }
      .sq-colon::before,
      .sq-colon::after {
        content:"";
        position:absolute;
        left:50%;
        transform:translateX(-50%);
        width:0.18em;
        height:0.18em;
        background:currentColor;
        border-radius:0;
      }
      .sq-colon::before { top:0.18em; }
      .sq-colon::after  { bottom:0.18em;}

      body { font-family: 'Montserrat', sans-serif; }
      .glass { backdrop-filter: blur(8px); background: rgba(0,0,0,0.5); }

      p, div { word-break: break-word; overflow-wrap: break-word; }
      .text-gray-300, .text-gray-400, .italic {
        max-width: 100%;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
      }
    </style>
</head>
<body class="bg-black text-gray-100">
<header class="fixed top-0 inset-x-0 z-50 glass border-b border-neutral-800">
  <!-- <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"> -->
  <div class="max-w-7xl mx-auto pl-1 sm:pl-2 md:pl-4 pr-4">
    <div class="flex h-16 items-center justify-between">

      <!-- LEFT Lide -->
      <div class="flex items-center space-x-10">
        <a href="<?= !empty($_SESSION['is_admin']) ? '/hatrox-project/admin/index.php' : '/hatrox-project/public/index.php' ?>" class="flex items-center space-x-2 group">
          <img src="/hatrox-project/assets/images/icons/icon.png" alt="HATROX Logo" class="w-10 h-10 object-cover transition group-hover:opacity-80">
        </a>

        <nav class="hidden md:flex items-center space-x-6">
          <a class="hover:text-gold transition" href="<?= !empty($_SESSION['is_admin']) ? '/hatrox-project/admin/shop.php' : '/hatrox-project/public/shop.php' ?>">Shop</a>
          <a class="hover:text-gold transition" href="<?= !empty($_SESSION['is_admin']) ? '/hatrox-project/admin/about.php' : '/hatrox-project/public/about.php' ?>">About</a>
          <?php if (!empty($_SESSION['is_admin'])): ?>
            <a class="hover:text-gold transition" href="/hatrox-project/admin/dashboard.php">Admin</a>
          <?php endif; ?>
        </nav>
      </div>

      <!-- RIGHT Side -->
      <div class="flex items-center">

        <?php if (!empty($_SESSION['user_id'])): ?>
          <span
            id="userMsg"
            class="hidden sm:inline text-md font-semibold text-[#b43b57] leading-none whitespace-nowrap text-right"
            style="min-width: 220px;">
          </span>

          <div class="relative" id="accountDropdown">
            <button id="accountBtn" class="flex items-center space-x-2 hover:text-gold transition pr-4" aria-expanded="false" aria-haspopup="true">
              <!-- Username -->
              <span class="hidden sm:inline text-md leading-none ">
              <span class="text-[#b43b57] font-semibold "> ,</span> <?= e($_SESSION['full_name'] ?? 'Account') ?>
              </span>

              <!-- Dropdown icon -->
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class=" w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </button>

            <div id="accountMenu" class="hidden absolute right-0 mt-2 w-44 bg-neutral-900 border border-neutral-800 rounded shadow-lg" role="menu" aria-labelledby="accountBtn">
              <?php if (empty($_SESSION['is_admin'])): ?>
                <a href="/hatrox-project/public/cart.php" class="block px-4 py-2 hover:bg-neutral-800">My Cart</a>
              <?php endif; ?>
              <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="/hatrox-project/admin/dashboard.php" class="block px-4 py-2 hover:bg-neutral-800">Admin</a>
              <?php endif; ?>
              <a href="<?= e($logoutUrl) ?>" class="block px-4 py-2 hover:bg-neutral-800 text-rose">Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a class="hover:text-gold transition pr-4" href="/hatrox-project/public/login.php">Login</a>
          <a class="hidden sm:inline hover:text-gold transition pr-4" href="/hatrox-project/public/register.php">Register</a>
        <?php endif; ?>

        <?php if (empty($_SESSION['is_admin'])): ?>
          <a href="/hatrox-project/public/cart.php" class="relative hover:text-gold transition hidden md:block" title="Cart">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 100 6 3 3 0 000-6zm9 0a3 3 0 100 6 3 3 0 000-6zm-12-9h15.75l-1.5 6H7.125m0 0L5.706 5.272M7.125 11.25H18.75" />
            </svg>
            <span class="absolute -top-2 -right-2 bg-gold text-black text-xs rounded-full px-1.5 py-0.5">
              <?= (int)$cart_count ?>
            </span>
          </a>
        <?php endif; ?>

        <button id="hamburger" class="md:hidden hover:text-gold transition" aria-label="Open Menu">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <div id="mobileMenu" class="md:hidden hidden border-t border-neutral-800">
    <nav class="px-4 py-3 space-y-2">
      <a class="block hover:text-gold transition" href="<?= !empty($_SESSION['is_admin']) ? '/hatrox-project/admin/shop.php' : '/hatrox-project/public/shop.php' ?>">Shop</a>
      <a class="block hover:text-gold transition" href="<?= !empty($_SESSION['is_admin']) ? '/hatrox-project/admin/about.php' : '/hatrox-project/public/about.php' ?>">About</a>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a class="block hover:text-gold transition" href="/hatrox-project/admin/dashboard.php">Admin</a>
      <?php endif; ?>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a class="block hover:text-gold transition" href="<?= e($logoutUrl) ?>">Logout</a>
      <?php else: ?>
        <a class="block hover:text-gold transition" href="/hatrox-project/public/login.php">Login</a>
        <a class="block hover:text-gold transition" href="/hatrox-project/public/register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<?php if (!empty($_SESSION['is_admin'])): ?>
<div class="fixed top-0 right-0 z-40 bg-rose-600 text-white px-4 py-2 text-xs font-medium rounded-bl-md">
  Admin Mode
  <a href="/hatrox-project/admin/dashboard.php" class="ml-2 text-white underline hover:no-underline">→ Dashboard</a>
</div>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('hamburger');
    const menu = document.getElementById('mobileMenu');
    if (btn && menu) {
      btn.addEventListener('click', () => menu.classList.toggle('hidden'));
    }

    const acc = document.getElementById('accountDropdown');
    const accBtn = document.getElementById('accountBtn');
    const accMenu = document.getElementById('accountMenu');
    let accCloseTimer = null;

    function clearAccTimer() {
      if (accCloseTimer) {
        clearTimeout(accCloseTimer);
        accCloseTimer = null;
      }
    }

    function scheduleAccClose() {
      clearAccTimer();
      accCloseTimer = setTimeout(closeAcc, 1000);
    }

    function closeAcc() {
      if (accMenu && !accMenu.classList.contains('hidden')) {
        accMenu.classList.add('hidden');
        accBtn?.setAttribute('aria-expanded', 'false');
      }
    }

    if (acc && accBtn && accMenu) {
      accBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        accMenu.classList.toggle('hidden');
        accBtn.setAttribute(
          'aria-expanded',
          accMenu.classList.contains('hidden') ? 'false' : 'true'
        );
      });

      document.addEventListener('click', (e) => {
        if (!acc.contains(e.target)) closeAcc();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAcc();
      });

      acc.addEventListener('mouseenter', clearAccTimer);
      acc.addEventListener('mouseleave', scheduleAccClose);
    }
  });
</script>

<main class="pt-[65px] min-h-screen">
