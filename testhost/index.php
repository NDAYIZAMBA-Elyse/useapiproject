<?php
echo "<h1>TestHost is working ✅</h1>";
echo "<p>PHP is running successfully 🚀</p>";

echo "<hr>";
echo "<h3>Server Info:</h3>";

echo "Server Name: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "Server IP: " . $_SERVER['SERVER_ADDR'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<hr>";
phpinfo();
?>
