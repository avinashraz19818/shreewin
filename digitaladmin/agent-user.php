<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
}
include("api/conn.php");
$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
?>
<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Agent Users</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php require_once("layout-menu.php"); ?>
            <div class="layout-page">
                <?php require_once("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="card">
                            <div class="table-responsive text-nowrap">
                                <div class="d-flex justify-content-between align-items-center px-4">
                                    <div class="d-flex align-items-center">
                                        <h4 class="m-5">Agent Data</h4>
                                        <input type="number" class="form-control" id="uid"
                                        placeholder="Enter UID" autofocus />
                                    </div>
                                    <form action="" method="get"
                                        class="d-flex gap-3 align-items-center">
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="<?= $date; ?>" max="<?= date('Y-m-d'); ?>">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>
                                </div>
                                <table class="table" id="example1">
                                    <thead>
                                        <tr>
                                            <th>Mobile</th>
                                            <th>Cust ID</th>
                                            <th>Total Active</th>
                                            <th>Total Recharge</th>
                                            <th>Total Withdraw</th>
                                            <th>P/L Report</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php
                                        $sql = "WITH AgentOwncode AS (
                                                    -- Get the owncode of each active agent
                                                    SELECT 
                                                        a.userid AS agent_id,
                                                        s.owncode AS agent_owncode
                                                    FROM tb_agent a
                                                    JOIN shonu_subjects s ON a.userid = s.id
                                                    WHERE a.status = 1  -- Filter agents with status = 1
                                                ), 
                                                AgentTeams AS (
                                                    -- Find users under the agent using the agent's owncode
                                                    SELECT 
                                                        ao.agent_id,
                                                        s.id AS team_member_id
                                                    FROM AgentOwncode ao
                                                    JOIN shonu_subjects s 
                                                        ON ao.agent_owncode = s.code 
                                                        OR ao.agent_owncode = s.code1 
                                                        OR ao.agent_owncode = s.code2 
                                                        OR ao.agent_owncode = s.code3 
                                                        OR ao.agent_owncode = s.code4 
                                                        OR ao.agent_owncode = s.code5
                                                ), 
                                                TeamDeposits AS (
                                                    -- Get total deposits for each team member (including the agent) for a specific date
                                                    SELECT 
                                                        atm.agent_id,
                                                        SUM(t.motta) AS total_recharge
                                                    FROM thevani t
                                                    JOIN AgentTeams atm ON t.balakedara = atm.team_member_id
                                                    WHERE DATE(t.dinankavannuracisi) = '$date'  -- Use PHP variable for date
                                                    AND t.sthiti = 1  -- Only include successful deposits
                                                    GROUP BY atm.agent_id
                                                ), 
                                                TeamWithdrawals AS (
                                                    -- Get total withdrawals for each team member (including the agent) for the same date
                                                    SELECT 
                                                        atm.agent_id,
                                                        SUM(h.motta) AS total_withdraw
                                                    FROM hintegedukolli h
                                                    JOIN AgentTeams atm ON h.balakedara = atm.team_member_id
                                                    WHERE DATE(h.dinankavannuracisi) = '$date'  -- Use PHP variable for date
                                                    AND h.sthiti = 1  -- Only include approved withdrawals
                                                    GROUP BY atm.agent_id
                                                ), 
                                                AllBets AS (
                                                -- Collect betting data from all tables without date filter
                                                SELECT byabaharkarta FROM bajikattuttate
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_drei
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_funf
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_zehn
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_kemuru
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_kemuru_drei
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_kemuru_funf
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_kemuru_zehn
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_aidudi
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_aidudi_drei
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_aidudi_funf
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_aidudi_zehn
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_trx
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_trx3
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_trx5
                                                UNION ALL
                                                SELECT byabaharkarta FROM bajikattuttate_trx10
                                            ), 
                                            ActiveTeamMembers AS (
                                                -- Count active team members (who deposited at least 100 INR and placed a bet)
                                                SELECT 
                                                    atm.agent_id,
                                                    COUNT(DISTINCT atm.team_member_id) AS active_team_count
                                                FROM AgentTeams atm
                                                JOIN thevani t ON t.balakedara = atm.team_member_id 
                                                    AND t.motta >= 100  -- User must have deposited at least 100 INR
                                                    AND t.sthiti = 1  -- Active deposit status
                                                JOIN AllBets b ON b.byabaharkarta = atm.team_member_id  -- User must have placed at least 1 INR bet
                                                GROUP BY atm.agent_id
                                            )
                                            -- Final output with deposits, withdrawals, and active team count
                                            SELECT 
                                                a.userid AS agent_id, a.mobile,
                                                COALESCE(td.total_recharge, 0) AS total_recharge,
                                                COALESCE(tw.total_withdraw, 0) AS total_withdraw,
                                                COALESCE(atm.active_team_count, 0) AS active_team_count
                                            FROM tb_agent a
                                            LEFT JOIN TeamDeposits td ON a.userid = td.agent_id
                                            LEFT JOIN TeamWithdrawals tw ON a.userid = tw.agent_id
                                            LEFT JOIN ActiveTeamMembers atm ON a.userid = atm.agent_id
                                            WHERE a.status = 1;";  // Ensure only active agents are shown
                                        

                                        $result = mysqli_query($conn, $sql);

                                        if (mysqli_num_rows($result) > 0) {
                                            // output data of each row
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $pl=number_format($row["total_recharge"] - $row["total_withdraw"], 2);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?= $row["mobile"]; ?>
                                                        <a href="agent-monthly-details.php?userid=<?= $row['agent_id']?>"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="Monthly Report"><i class="ri-information-2-fill ri-22px text-primary"></i></a>
                                                    </td>
                                                    <td>
                                                        <?= $row["agent_id"]; ?>
                                                        <a href="agent-details.php?userid=<?= $row['agent_id']?>&date=<?= $date?>"  class="update-person" style="color:#0E0E44; font-size:16px;" data-toggle="tooltip" title="User Deatil"><i class="ri-information-2-line ri-22px text-primary"></i></a>
                                                    </td>
                                                    <td><?= number_format($row["active_team_count"]); ?></td>
                                                    <td>₹ <?= number_format($row["total_recharge"], 2); ?></td>
                                                    <td>₹ <?= number_format($row["total_withdraw"], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-label-<?= $pl<0?'danger':'success' ?> rounded-pill">₹ <?= $pl ?></span>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            echo "0 results";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php require_once("footer.php"); ?>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(function () {
            var table = $('#example1').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": true,
                "pageLength": 50,
                "dom": '<"row"' + '<"col-md-12"<"d-flex align-items-center justify-content-md-end justify-content-center"<"px-4">>>' + '>t' + '<"row p-5"' + '<"col-sm-12 col-md-6"i>' + '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end gap-5"lp>' + '>',
                "language": {
                    sLengthMenu: 'Show _MENU_',
                    search: '',
                    searchPlaceholder: 'Search User',
                    paginate: {
                        next: '<i class="ri-arrow-right-s-line"></i>',
                        previous: '<i class="ri-arrow-left-s-line"></i>'
                    }
                }
            });
            $('#uid').on('keyup change', function () {
                var val = $(this).val();
                table.columns(1).search(val).draw(); // Change index as needed
            });
        });
    </script>
</body>

</html>