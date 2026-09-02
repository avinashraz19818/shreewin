<?php
include '../conn.php'; // Include your database connection file

header('Content-Type: application/json');

$ownCode = $_GET['ownCode'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$Query = mysqli_query($conn, "WITH AgentTeams AS (
    -- Find users under the agent at different levels
    SELECT s.id AS team_member_id, 1 AS level FROM shonu_subjects s WHERE s.code = '$ownCode'
    UNION ALL
    SELECT s.id AS team_member_id, 2 AS level FROM shonu_subjects s WHERE s.code1 = '$ownCode'
    UNION ALL
    SELECT s.id AS team_member_id, 3 AS level FROM shonu_subjects s WHERE s.code2 = '$ownCode'
    UNION ALL
    SELECT s.id AS team_member_id, 4 AS level FROM shonu_subjects s WHERE s.code3 = '$ownCode'
    UNION ALL
    SELECT s.id AS team_member_id, 5 AS level FROM shonu_subjects s WHERE s.code4 = '$ownCode'
    UNION ALL
    SELECT s.id AS team_member_id, 6 AS level FROM shonu_subjects s WHERE s.code5 = '$ownCode'
), 
AllBets AS (
    -- Fetch all bets of team members with their bet amounts (ketebida)
    SELECT b.byabaharkarta, b.ketebida, atm.level
    FROM (
        SELECT byabaharkarta, ketebida FROM bajikattuttate WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_drei WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_funf WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_zehn WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_kemuru WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_kemuru_drei WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_kemuru_funf WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_kemuru_zehn WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_aidudi WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_aidudi_drei WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_aidudi_funf WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_aidudi_zehn WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_trx WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_trx3 WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_trx5 WHERE DATE(tiarikala) = '$date'
        UNION ALL
        SELECT byabaharkarta, ketebida FROM bajikattuttate_trx10 WHERE DATE(tiarikala) = '$date'
    ) AS b
    JOIN AgentTeams atm ON b.byabaharkarta = atm.team_member_id  -- Ensure bets belong to the agent’s team members
), 
BetSums AS (
    -- Sum `ketebida` per level
    SELECT 
        atm.level,
        SUM(b.ketebida) AS total_bet_amount
    FROM AgentTeams atm
    JOIN AllBets b ON b.byabaharkarta = atm.team_member_id  -- Ensure user has placed a bet
    GROUP BY atm.level
)
-- Final SELECT statement
SELECT 
    COALESCE(SUM(CASE WHEN level = 1 THEN total_bet_amount END), 0) AS level1_total_bet,
    COALESCE(SUM(CASE WHEN level = 2 THEN total_bet_amount END), 0) AS level2_total_bet,
    COALESCE(SUM(CASE WHEN level = 3 THEN total_bet_amount END), 0) AS level3_total_bet,
    COALESCE(SUM(CASE WHEN level = 4 THEN total_bet_amount END), 0) AS level4_total_bet,
    COALESCE(SUM(CASE WHEN level = 5 THEN total_bet_amount END), 0) AS level5_total_bet,
    COALESCE(SUM(CASE WHEN level = 6 THEN total_bet_amount END), 0) AS level6_total_bet
FROM BetSums;
");

$row = mysqli_fetch_assoc($Query);
echo json_encode($row);
?>
