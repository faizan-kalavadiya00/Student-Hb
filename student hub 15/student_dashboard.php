<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
sh_require_role($pdo, 'student');
$user = $_SESSION['user'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Dashboard - Student Hub</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script>
<style>.role-banner{max-width:1000px;margin:1.5rem auto;padding:1rem 1.4rem;background:#eaf2ff;border-left:5px solid #174ea6;border-radius:8px;color:#17365d}.role-banner a{font-weight:700}.role-actions{display:flex;gap:1rem;flex-wrap:wrap;margin-top:.8rem}</style></head><body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1>
<nav class="external-style-25"><a class="external-style-6" href="home.html">Home</a><a class="external-style-6" href="dashboard.php">Dashboard</a><a class="external-style-6" href="profile.html">Profile</a><a class="external-style-6" href="faq.html">FAQs</a><a class="external-style-6" href="events.html">Events</a><a class="external-style-6" href="pr9.php">Registration</a><a class="external-style-6" href="logout.php">Logout</a></nav></header>
<main class="role-banner"><h2>Welcome, <?= sh_e((string)$user['full_name']) ?>!</h2><p>You are signed in as <strong>Student</strong> (role: <code><?= sh_e((string)$user['role']) ?></code>).</p>
<p>Last activity is tracked securely. Your session expires after 15 minutes of inactivity.</p><div class="role-actions"><a href="dashboard_content.php">Open Student Hub timetable and services</a> <a href="logout.php">Log out securely</a></div></main>
</body></html>
