<?php
define("ROOT", "../");

require ROOT . 'www/header.php';

$message = "";

//Do we need to set a version as active?
if(isset($_GET['setactive'])) {
    if(ctype_digit($_GET['setactive'])) {
        $activeVersion = $_GET['setactive'];
        try {
            Version::setAsActive($activeVersion);
            $message = "Active version updated successfully";
        } catch (Exception $ex) {
            $message = $ex->getMessage();
        }
    }
}

$versions = Version::getVersions();
?>

<h2>Transporter Server</h2>

<p><a href="edit_files.php">Edit override files</a></p>
<p><button id="new_version" type="button">Create new version</button></p>

<?php print "<p><b>$message</b></p>"; ?>

<p>Clicking on the "Create new version" or "Generate" (image) buttons will start
    processes on the server that will take at least 3-4 minutes to complete.
    Do not refresh or navigate away from the page till you get a pop up message with the status.</p>
<table border="1">
    <tr>
        <th>Version</th>
        <th>Date Updated</th>
        <th>Active</th>
        <th>Was Active</th>
        <th>UseForUI</th>
        <th>Changes</th>
        <th>Generate Images</th>
        <th>Download</th>
    </tr>
<?php
foreach($versions as $v) {
    print '<tr>
            <td>'. $v->id .'</td>';

    print '<td>';
    print date("F j, Y, g:i a", strtotime($v->created_date));
    print '</td>';

    print '<td>'. (($v->active == true) ? '<b>Yes</b>' : '<button class="set_active"
                type="button" name="setactive" value="'.$v->id.'">Set as active</button>') .'</td>
           <td>'. (($v->was_active == true) ? 'Yes' : 'No') .'</td>
           <td> <a href="./useForUI.php?version='.$v->id.'">Check</a></td>
           <td>'. (($v->changes_present == true) ? '<a href="./data/change/'.
                    $v->id .'/changes.txt">View</a>' : 'None') .'</td>
           <td><button class="generate_image" type="button" name="generate" value="'.
                $v->id.'">'. (($v->images_generated == true) ? 'Re-generate' : 'Generate') .'</button></td>
           <td>'. (($v->images_generated == true) ? '<a href="./data/images/'.
                    $v->id .'/images.zip">Images</a> / <a href="./data/xml/'.
                    $v->id .'/data.zip">Data</a>' : '') .'</td>
           </tr>';
}
?>

</table>
        
<?php require 'footer.php'; ?>