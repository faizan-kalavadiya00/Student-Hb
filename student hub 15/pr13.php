<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
sh_require_role($pdo, 'admin');
$user = $_SESSION['user'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Hub - Practical 13</title><link rel="stylesheet" href="style.css">
<style>
.pr13-wrap{max-width:1100px;margin:2rem auto;padding:1rem}.pr13-card{background:var(--card-bg,#fff);border:1px solid #d6dce5;border-radius:12px;padding:1rem;margin:1rem 0}.pr13-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:.8rem}.pr13-form label{display:flex;flex-direction:column;gap:.3rem;font-weight:600}.pr13-form input,.pr13-form select,.pr13-form textarea,.pr13-controls input,.pr13-controls select{padding:.65rem;border:1px solid #aab4c2;border-radius:6px;font:inherit}.pr13-form textarea{min-height:80px}.pr13-controls{display:flex;gap:.8rem;flex-wrap:wrap;align-items:center}.pr13-controls input{flex:1;min-width:200px}.pr13-actions{display:flex;gap:.4rem;flex-wrap:wrap}.pr13-actions button,.pr13-button{padding:.55rem .8rem;border:0;border-radius:6px;cursor:pointer;background:#174ea6;color:#fff;font-weight:600}.pr13-actions .delete{background:#b42318}.pr13-actions .secondary{background:#586579}.pr13-table-wrap{overflow-x:auto}.pr13-table{width:100%;border-collapse:collapse;margin-top:.8rem}.pr13-table th,.pr13-table td{padding:.7rem;border-bottom:1px solid #dce1e8;text-align:left;vertical-align:top}.pr13-message{padding:.8rem;border-radius:7px;margin:.8rem 0}.pr13-message[hidden]{display:none}.pr13-message.success{background:#e7f7ed;color:#14532d}.pr13-message.error{background:#ffebe9;color:#8a1c13}.pr13-message.info{background:#eaf2ff;color:#17365d}.pr13-muted{color:#596579}.pr13-form-buttons{grid-column:1/-1;display:flex;gap:.6rem}.pr13-form-buttons button{padding:.65rem 1rem;border:0;border-radius:6px;cursor:pointer}.pr13-form-buttons button[type=submit]{background:#174ea6;color:#fff}@media(prefers-color-scheme:dark){.pr13-card{background:#202733;color:#eef2f8}.pr13-table th,.pr13-table td{border-color:#485363}}
</style>
<script src="script.js" defer></script><script src="js/pr13.js" defer></script>
</head>
<body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1>
<nav class="external-style-25"><a class="external-style-6" href="home.html">Home</a><a class="external-style-6" href="admin_dashboard.php">Admin Dashboard</a><a class="external-style-6" href="pr11.php">Practical 11</a><a class="external-style-6" href="pr12.php">Practical 12</a><a class="external-style-6" href="pr13.php">Practical 13</a><a class="external-style-6" href="logout.php">Logout</a></nav></header>
<main class="pr13-wrap">
<h1>PHP JSON API and Fetch-Based CRUD</h1>
<p class="pr13-muted">Manage student and event records asynchronously. Actions use JSON responses and update this page without a full reload.</p>
<div id="pr13-message" class="pr13-message info" role="status" aria-live="polite">Loading records…</div>
<section class="pr13-card">
<div class="pr13-controls"><label for="pr13-resource">Manage</label><select id="pr13-resource"><option value="students">Students</option><option value="events">Events</option></select><input id="pr13-search" type="search" placeholder="Search records (search updates as you type)" autocomplete="off"><button class="pr13-button" id="pr13-refresh" type="button">Refresh</button></div>
</section>
<section class="pr13-card">
<h2 id="pr13-form-title">Add student</h2>
<form id="pr13-form" class="pr13-form">
<input type="hidden" id="pr13-id">
<div id="pr13-fields"></div>
<div class="pr13-form-buttons"><button type="submit" id="pr13-save">Save record</button><button type="button" id="pr13-cancel" hidden>Cancel edit</button></div>
</form>
</section>
<section class="pr13-card">
<h2 id="pr13-list-title">Students</h2><p class="pr13-muted" id="pr13-count">Loading…</p>
<div class="pr13-table-wrap"><table class="pr13-table"><thead id="pr13-head"></thead><tbody id="pr13-body"></tbody></table></div>
</section>
</main>
</body></html>
