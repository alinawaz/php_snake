<!-- Sidebar -->
<div class="sidebar">
  <h2 style="color: white;">🏦 HomeBank</h2>
  <ul>
    <li><a class="{{ (url() == '/customer/dashboard' ? 'active': '') }}" href="/customer/dashboard">📊 Dashboard</a></li>
    <li><a class="{{ (url() == '/customer/accounts' ? 'active': '') }}" href="/customer/accounts">💰 Accounts</a></li>
    <li><a class="{{ (url() == '/customer/apps' ? 'active': '') }}" href="/customer/apps">💻 Apps</a></li>
    <li><a class="{{ (url() == '/customer/cards' ? 'active': '') }}" href="/customer/cards">💳 Cards</a></li>
    <hr>
    <li><a href="/logout">🚪 Logout</a></li>
  </ul>
</div>