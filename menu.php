<!-- Sidebar -->
  <div class="sidebar">
    <h2 style="color: white;">🏦 HomeBank</h2>
    <ul>
      <li><a class="<?php echo ($page == 'dashboard' ? 'active' : '') ?>" href="/dashboard.php">📊 Dashboard</a></li>
      <li><a class="<?php echo ($page == 'accounts' ? 'active' : '') ?>" href="/accounts">💰 Accounts</a></li>
      <li><a class="<?php echo ($page == 'apps' ? 'active' : '') ?>" href="/user_apps.php">💻 Apps</a></li>
      <li><a class="<?php echo ($page == 'cards' ? 'active' : '') ?>" href="/user_cards.php">💳 Cards</a></li>
      <?php if ($role === 'admin'): ?>
        <li><a class="<?php echo ($page == 'users' ? 'active' : '') ?>" href="/admin_users.php">💰 Users</a></li>
        <li><a href="./sync.php">📝 Sync</a></li>
        <li><a href="./admin_bank_charges.php">📝 Bank Charges</a></li>
      <?php endif; ?>
      <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
  </div>