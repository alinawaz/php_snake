<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Bank Dashboard</title>
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