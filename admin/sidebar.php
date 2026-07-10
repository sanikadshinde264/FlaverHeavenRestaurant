<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <div class="brand">Flaver Heaven</div>
  <div class="nav">
    <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
    <a href="users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">User Management</a>
    <a href="menu.php" class="<?php echo $currentPage === 'menu.php' || $currentPage === 'menu_form.php' ? 'active' : ''; ?>">Menu Management</a>
    <a href="orders.php" class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">Order Management</a>
    <a href="reservations.php" class="<?php echo $currentPage === 'reservations.php' ? 'active' : ''; ?>">Reservation Management</a>
    <a href="payments.php" class="<?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">Payment Management</a>
    <a href="reports.php" class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">Reports</a>
    <a href="logout.php">Logout</a>
  </div>
</aside>