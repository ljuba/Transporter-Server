<?php
define("ROOT", "../");

require ROOT . 'www/header.php';

$message = "";
?>

<h2>Edit Override files</h2>

<p><a href="index.php">Home</a></p>

<form action="" method="post">
    <select id="file_select" name="file">
        <option>Select a file</option>
        <option value="directions">Directions</option>
        <option value="reverseStops">Reverse Stops</option>
        <option value="sf-muni-vehicles">SF Muni Vehicles</option>
    </select>

    <br /><br />

    <textarea id="file_contents" cols="100" rows="30"></textarea>

    <br />
    <button id="edit_file" type="button">Update</button>
</form>

<?php
require 'footer.php';
