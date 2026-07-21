<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>🌱 GreenPin — Smart Crop Suggestion System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="styles.css" />

  <link rel="stylesheet" href="leaflet.css" />
</head>
<body>
  <header class="topbar">
    <h1>🌿 GreenPin</h1>
    <nav>
      <?php if ($loggedIn): ?>
        <span>Welcome, <?= htmlspecialchars($username) ?>!</span>
        <a href="logout.php" class="btn">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn">Login</a>
        <a href="register.php" class="btn">Register</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">
    <section class="controls">
      <h2>📍 Pin a location & get crop suggestions</h2>
      <p>Click on the map to place a pin. The app will detect the climate. You just choose the land details.</p>

      <form id="suggestForm" onsubmit="return false;">
        <div class="row">
          <label>Soil type
            <select id="soil">
              <option value="loamy">Loamy</option>
              <option value="sandy">Sandy</option>
              <option value="clay">Clay</option>
              <option value="silty">Silty</option>
              <option value="peaty">Peaty</option>
            </select>
          </label>

          <label>Water availability
            <select id="water">
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </label>
        </div>

        <div class="row">
          <label>Irrigation available?
            <select id="irrigation">
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </label>

          </div>

        <input type="hidden" id="lat" />
        <input type="hidden" id="lng" />

        <button id="getSuggest" class="btn primary">Get Suggestion</button>
        <?php if ($loggedIn): ?>
          <button id="savePinBtn" class="btn">Save Pin</button>
        <?php else: ?>
          <small>Log in to save pins to your account.</small>
        <?php endif; ?>
      </form>

      <div id="suggestions" class="card">
        <h3>Suggestions</h3>
        <div id="suggestList">Click on the map to start.</div>
      </div>
    </section>

    <section class="mapwrap">
      <div id="map"></div>
    </section>
  </main>

  <footer class="footer">Made with 🌾 — GreenPin demo</footer>

  <script src="leaflet.js"></script>
  
  <script>
    const LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
  </script>
  <script src="scripts.js"></script>
</body>
</html>