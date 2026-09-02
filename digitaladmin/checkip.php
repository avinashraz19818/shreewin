<?php
	session_start();
	if(empty($_SESSION['unohs'])){
		header("location:index.php?msg=unauthorized");
	}
include("api/conn.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
  <link rel="shortcut icon" href="images/favicon.png" />
 
  <style>
      body {
          font-family: 'Poppins', sans-serif;
          background-color: #f4f4f9;
          margin: 0;
          padding: 0;
      }
      .container {
          max-width: 900px;
          margin: 50px auto;
          background: #ffffff;
          border-radius: 8px;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          overflow: hidden;
      }
      .header {
          background-color: #5a67d8;
          color: white;
          padding: 20px;
          text-align: center;
          font-size: 22px;
          font-weight: bold;
      }
      .content {
          padding: 20px;
          font-size: 16px;
          line-height: 1.6;
      }
      .ip-list {
          list-style: none;
          padding: 0;
      }
      .ip-list li {
          background: #f8f9fa;
          border-left: 5px solid #5a67d8;
          padding: 12px;
          margin: 8px 0;
          border-radius: 5px;
          font-weight: 500;
      }
      .footer {
          text-align: center;
          padding: 15px;
          background-color: #5a67d8;
          color: white;
          font-size: 14px;
      }
  </style>
</head>
<body>
    <div class="container">
        <div class="header">Duplicate IP Checker</div>
        <div class="content">
            <ul class="ip-list">
            <?php
            $duplicateIPsQuery = "SELECT ishonup FROM shonu_subjects GROUP BY ishonup HAVING COUNT(ishonup) > 1";
            $result = $conn->query($duplicateIPsQuery);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $duplicateIP = $row['ishonup'];
                    $fetchIdsQuery = "SELECT id FROM shonu_subjects WHERE ishonup = ?";
                    $stmtFetch = $conn->prepare($fetchIdsQuery);
                    $stmtFetch->bind_param("s", $duplicateIP);
                    $stmtFetch->execute();
                    $resultIds = $stmtFetch->get_result();
                    echo "<li>Duplicate IP: <strong>$duplicateIP</strong><br>User IDs: ";
                    while ($idRow = $resultIds->fetch_assoc()) {
                        echo "<span style='background:#5a67d8;color:white;padding:3px 6px;border-radius:3px;'>" . $idRow['id'] . "</span> ";
                    }
                    echo "</li>";
                    $stmtFetch->close();
                }
            }
            $conn->close();
            ?>
            </ul>
        </div>
    </div>
    <footer class="footer">Copyright &copy; AB Coders</footer>
</body>
</html>