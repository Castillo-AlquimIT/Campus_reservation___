<?php
// index.php
require_once 'db.php';
if (!isset($_SESSION['user'])) {
  header('Location: login.html');
  exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Campus Reservation Dashboard</title>
  <link rel="stylesheet" href="styles.css" />
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body>
  <header class="topbar">
    <div>
      <strong>Campus Reservation</strong>
    </div>
    <div>
      <span class="muted">Logged in as <?php echo htmlspecialchars($user['name'].' ('.$user['role'].')'); ?></span>
      <button id="logoutBtn">Logout</button>
    </div>
  </header>

  <main class="grid">
    
<section class="panel">
  <h2>Calendar</h2>
  <!-- Legend -->
  <div class="legend">
    <span class="status-approved">Approved</span>
    <span class="status-pending">Pending</span>
    <span class="status-rejected">Rejected</span>
    <span class="status-cancelled">Cancelled</span>
  </div>
  <div id="calendar"></div>
</section>

    </section>

    <section class="panel">
      <h2>Create reservation</h2>
      <form id="resForm">
        <label>Title</label>
        <input type="text" name="title" required />

        <label>Target type</label>
        <select name="targetType" required>
          <option value="room">Room</option>
          <option value="equipment">Equipment</option>
        </select>

        <label>Resource</label>
        <select name="targetId" id="resourceSelect" required></select>

        <label>Start</label>
        <input type="datetime-local" name="start" required />
        <label>End</label>
        <input type="datetime-local" name="end" required />

        <label>Notes</label>
        <textarea name="notes" rows="2"></textarea>

        <button type="submit">Submit</button>
        <p id="resMsg" class="muted"></p>
      </form>

      <?php if ($user['role'] !== 'student'): ?>
      <h3>Approve / update status</h3>
      <form id="approveForm">
        <label>Reservation ID</label>
        <input type="number" name="reservationId" required />
        <label>New status</label>
        <select name="toStatus" required>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <label>Remarks</label>
        <input type="text" name="remarks" />
        <button type="submit">Update</button>
        <p id="approveMsg" class="muted"></p>
      </form>
      <?php endif; ?>

      <?php if ($user['role'] !== 'student'): ?>
      <h3>Utilization report</h3>
      <form id="reportForm">
        <label>Start</label>
        <input type="date" name="start" />
        <label>End</label>
        <input type="date" name="end" />
        <label>Room ID</label>
        <input type="number" name="room_id" />
        <label>User ID</label>
        <input type="number" name="user_id" />
        <button type="submit">Generate</button>
        <pre id="reportOutput"></pre>
      </form>
      <?php endif; ?>
    </section>
  </main>
<!-- Context menu -->
<ul id="eventMenu" class="context-menu">
  <li data-action="archive">Archive</li>
  <li data-action="delete">Delete</li>
</ul>

  <script src="app.js"></script>
</body>
</html>
