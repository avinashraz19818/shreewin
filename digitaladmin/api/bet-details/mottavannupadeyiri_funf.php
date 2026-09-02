<?php 
include("conn_funf.php");

$periodid = $_POST['periodid'];
$actiontype = $_POST['actiontype'];

// Define number mappings
$numbermappings = [
    0 => 'zero',
    1 => 'one',
    2 => 'two',
    3 => 'three',
    4 => 'four',
    5 => 'five',
    6 => 'six',
    7 => 'seven',
    8 => 'eight',
    9 => 'nine',
    10 => 'red',
    11 => 'green',
    12 => 'violet',
    13 => 'big',
    14 => 'small'
];

if($actiontype == 'getdata') {
    // First, fetch all numbers (0-9)
    $Query = mysqli_query($conn, "SELECT sankhye, banna FROM `hastacalita_phalitansa_funf` ORDER BY shonu"); 
    
    // Process numbers 0-9 - now showing 4 columns
    while($row = mysqli_fetch_array($Query)) {
        $totalusernumber = getusercount($conn, $periodid, $row['sankhye']);
        
        if($row['sankhye'] == 1 || $row['sankhye'] == 3 || $row['sankhye'] == 7 || $row['sankhye'] == 9) {
            $total = winner($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
            $real = rlamt($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
        }
        else if($row['sankhye'] == 2 || $row['sankhye'] == 4 || $row['sankhye'] == 6 || $row['sankhye'] == 8) {
            $total = winner($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
            $real = rlamt($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
        }
        else if($row['sankhye'] == 0) {
            $total = winner($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
            $real = rlamt($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');    
        }
        else if($row['sankhye'] == 5) {
            $total = winner($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
            $real = rlamt($conn, $periodid, $numbermappings[$row['sankhye']].'winamount');
        }
        ?>
        <tr>
            <td> <?= $row["banna"] ?></td>
            <td><?= $real ? number_format($real, 2) : '0.00' ?></td>
            <td><?= $totalusernumber ?></td>
            <td><?= number_format($total, 2) ?></td>
        </tr>
        <?php
    }

    // Special bet types (Red, Green, Violet, Big, Small) - now showing 4 columns
    $specialBets = [
        ['id' => 10, 'name' => 'Red', 'type' => 'red', 'class' => 'text-danger'],
        ['id' => 11, 'name' => 'Green', 'type' => 'green', 'class' => 'text-success'],
        ['id' => 12, 'name' => 'Violet', 'type' => 'violet', 'class' => 'text-purple'],
        ['id' => 13, 'name' => 'Big (5-9)', 'type' => 'big', 'class' => 'text-primary'],
        ['id' => 14, 'name' => 'Small (0-4)', 'type' => 'small', 'class' => 'text-warning']
    ];

    foreach ($specialBets as $bet) {
        $totalusernumber = getusercount($conn, $periodid, $bet['id']);
        $total = winner($conn, $periodid, $bet['type'].'winamount');
        $real = rlamt($conn, $periodid, $bet['type'].'winamount');
        ?>
        <tr class="<?= $bet['class'] ?>">
            <td><?= $bet['name'] ?></td>
            <td><?= $real ? number_format($real, 2) : '0.00' ?></td>
            <td><?= $totalusernumber ?></td>
            <td><?= number_format($total, 2) ?></td>
        </tr>
        <?php
    }
}
else if($actiontype == 'refreshtdata') {
    $sqlA = mysqli_query($conn, "UPDATE `hastacalita_phalitansa_funf` SET sthiti = '0'");            
    $samasye = mysqli_query($conn, "SELECT * FROM `hastacalita_phalitansa_funf`");
    $i = 0; 
    while($dhadi = mysqli_fetch_array($samasye)) {
        $i++;
        ?>
        <tr>
            <td><?= $dhadi["sankhye"] ?> <?= $dhadi["banna"] ?></td>
            <td class="text-orange">wait..</td>
            <td class="text-orange">wait..</td>
            <td class="text-orange">wait..</td>
        </tr>                       
        <?php 
    }
}
?>