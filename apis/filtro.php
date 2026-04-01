<?php
require_once "db.php";
$sql = "SELECT * FROM categoria WHERE parent_id IS NULL";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "<a href='catalogo.php?categoria=" . $row['id'] . "'>" . $row['nombre'] . "</a><br>";
}
?>
