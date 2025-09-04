<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Bank Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="@assets(style.css)">
</head>

<body class="dash-body">

  @layout(admin.layout.menu)

  <!-- Main -->
  <div class="main-content">
    <div class="header">
      <h1>Welcome, <?= ucfirst(htmlspecialchars(auth()->user->username)) ?></h1>
      <p>Your role: <strong><?= htmlspecialchars(auth()->user->role) ?></strong></p>
    </div>