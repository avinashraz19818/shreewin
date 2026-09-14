<?php
include '../conn.php'; // Include your database connection file

header('Content-Type: application/json');

$ownCode = $_GET['ownCode'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$Query = mysqli_query($conn, "WITH AgentTeams AS (
                    -- Find users under the agent at different levels
                    SELECT 
                        s.id AS team_member_id,
                        1 AS level
                    FROM shonu_subjects s WHERE s.code = '$ownCode'
                    
                    UNION ALL
                
                    SELECT 
                        s.id AS team_member_id,
                        2 AS level
                    FROM shonu_subjects s WHERE s.code1 = '$ownCode'
                
                    UNION ALL
                
                    SELECT 
                        s.id AS team_member_id,
                        3 AS level
                    FROM shonu_subjects s WHERE s.code2 = '$ownCode'
                
                    UNION ALL
                
                    SELECT 
                        s.id AS team_member_id,
                        4 AS level
                    FROM shonu_subjects s WHERE s.code3 = '$ownCode'
                
                    UNION ALL
                
                    SELECT 
                        s.id AS team_member_id,
                        5 AS level
                    FROM shonu_subjects s WHERE s.code4 = '$ownCode'
                
                    UNION ALL
                
                    SELECT 
                        s.id AS team_member_id,
                        6 AS level
                    FROM shonu_subjects s WHERE s.code5 = '$ownCode'
                ), 
                AllBets AS (
                    -- Fetch bet details of team members only (no date filter)
                    SELECT b.byabaharkarta 
                    FROM (
                        SELECT byabaharkarta FROM bajikattuttate WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_drei WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_funf WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_zehn WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_kemuru WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_kemuru_drei WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_kemuru_funf WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_kemuru_zehn WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_aidudi WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_aidudi_drei WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_aidudi_funf WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_aidudi_zehn WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_trx WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_trx3 WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_trx5 WHERE ketebida >= 1
                        UNION ALL
                        SELECT byabaharkarta FROM bajikattuttate_trx10 WHERE ketebida >= 1
                    ) AS b
                    JOIN AgentTeams atm ON b.byabaharkarta = atm.team_member_id  -- Ensure bets belong to the agent’s team members
                ), 
                ActiveTeamMembers AS (
                    -- Count active team members per level (no date filter on deposits)
                    SELECT 
                        atm.level,
                        COUNT(DISTINCT atm.team_member_id) AS active_team_count
                    FROM AgentTeams atm
                    JOIN thevani t ON t.balakedara = atm.team_member_id 
                        AND t.motta >= 100  -- User must have deposited at least 100 INR
                        AND t.sthiti = 1  -- Active deposit status
                    JOIN AllBets b ON b.byabaharkarta = atm.team_member_id  -- User must have placed at least 1 INR bet
                    GROUP BY atm.level
                )

                -- Final SELECT statement
                SELECT 
                    COALESCE(SUM(CASE WHEN level = 1 THEN active_team_count END), 0) AS level1_active_users,
                    COALESCE(SUM(CASE WHEN level = 2 THEN active_team_count END), 0) AS level2_active_users,
                    COALESCE(SUM(CASE WHEN level = 3 THEN active_team_count END), 0) AS level3_active_users,
                    COALESCE(SUM(CASE WHEN level = 4 THEN active_team_count END), 0) AS level4_active_users,
                    COALESCE(SUM(CASE WHEN level = 5 THEN active_team_count END), 0) AS level5_active_users,
                    COALESCE(SUM(CASE WHEN level = 6 THEN active_team_count END), 0) AS level6_active_users
                FROM ActiveTeamMembers;");

$row = mysqli_fetch_assoc($Query);
echo json_encode($row);
?>
