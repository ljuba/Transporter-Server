<?php
define("ROOT", "../");

require ROOT . 'www/header.php';

$version = isset($_GET['version']) ? $_GET['version'] : "";
if( empty($version) || !ctype_digit($version) ) {
    print "Version value is absent or invalid.";
    exit;
}

function getRoutes (Agency $agency, $version) {
    $dbObj = DBPool::getInstance();
    $routeArray = array();
    $finalRouteArray = array();

    //Get the routes
    $dbObj->bindParams( array($version, $agency->getId()) );
    $routes = $dbObj->get_results("SELECT b.tag AS routetag, a.tag AS dirtag
                                    FROM direction as a, route as b
                                    WHERE a.route_id=b.id
                                        AND a.use_for_ui=true
                                        AND a.version=?
                                        AND b.agency_id=?
                                    ORDER BY b.tag;");

    if($dbObj->num_rows > 0) {
        foreach($routes as $r) {
            $routeTag = (string) $r->routetag;
            $routeArray[$routeTag][] = (string) $r->dirtag;
        }

        //Pick only the ones with more than 2 directions
        foreach ($routeArray as $routeTag => $dirs) {
            if(count($dirs) > 2) {
                $finalRouteArray[$routeTag] = $dirs;
            }
        }
    }

    return $finalRouteArray;
}

function displayTable(Array $routeArray) {
    print '<table border="1">
                <tr>
                    <th>Route</th>
                    <th>Directions</th>
                </tr>';
    foreach($routeArray as $routeTag => $dirTagArray) {
        print '<tr>
                <td>'. $routeTag .'</td>
                <td>'. implode(", ", $dirTagArray) .'</td>
               </tr>';
    }

    print '</table><br />';
}

$agencyArray = Agency::getAgencies();
?>

<h2>Routes with more than two directions having useForUI=true</h2>

<p><a href="index.php">Home</a></p>

<b>Version: <?php print $version; ?> </b>

<p><b>AC Transit</b></p>
<?php
$routeArray = getRoutes($agencyArray['actransit'], $version);
displayTable($routeArray);
?>



<p><b>SF-Muni</b></p>
<?php
$routeArray = getRoutes($agencyArray['sf-muni'], $version);
displayTable($routeArray);
?>

<?php require 'footer.php'; ?>
