<?php
include "../../conn.php";
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allow_origin = '';
if ($origin) {
    $stmt = $conn->prepare("SELECT domain FROM allowed_origins WHERE domain=? AND status=1");
    $stmt->bind_param("s", $origin);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $allow_origin = $origin;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($allow_origin) header("Access-Control-Allow-Origin: $allow_origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin, ar-real-ip, ar-session');
    exit(0);
}

if ($allow_origin) {
    header("Access-Control-Allow-Origin: $allow_origin");
}
date_default_timezone_set('Asia/Kolkata');
$serviceNowTimeFormatted = date('Y-m-d H:i:s');

$jsonData = '{
    "data": [
       {
            "vendorCode": "EVO_Video",
            "sort": 75,
            "childList": [
                {
                    "gameID": "8ef39602e589bf9f32fc351b1cbb338b",
                    "gameNameEn": "Blackjack VIP 3",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/o735cjzyaeasv4o6.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "8ef39602e589bf9f32fc351b1cbb338b",
                    "gameNameEn": "Salon Privé Blackjack B",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/olbibp3fylzaxvhb.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "8ef39602e589bf9f32fc351b1cbb338b",
                    "gameNameEn": "Blackjack VIP 6",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/o735fhvsaeaswamh.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "8ef39602e589bf9f32fc351b1cbb338b",
                    "gameNameEn": "Blackjack VIP 7",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/o735ggd5iwsswcz7.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "8ef39602e589bf9f32fc351b1cbb338b",
                    "gameNameEn": "Blackjack VIP 8",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/o735hfcqauecwjxp.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "8ef39602e589bf9f32fc351b1cbb338b",
                    "gameNameEn": "Blackjack VIP 4",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/o735di2eiwssv7eu.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "aed251dded03e8a1e665e52cdc4b6e80",
                    "gameNameEn": "Blackjack VIP 5",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/o735efxfaeasv666.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "aed251dded03e8a1e665e52cdc4b6e80",
                    "gameNameEn": "Salon Privé Blackjack D",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/olbinkuoylzayeoj.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "aed251dded03e8a1e665e52cdc4b6e80",
                    "gameNameEn": "Blackjack VIP 13",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/EVO_Video/pdk52e3rey6upyie.png",
                    "vendorId": 16,
                    "vendorCode": "EVO_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                }
            ]
        },
        {
            "vendorCode": "DG",
            "sort": 7,
            "childList": [
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "DragonTiger",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_3.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Three Cards",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_11.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Sedie",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_14.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "InBaccarat",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_2.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Three Face",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_16.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Baccarat",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_1.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Fish, shrimp and crab",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_15.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Quickness Sicbo",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_12.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "10d4993cda9b61540f5898e17a15f4b2",
                    "gameNameEn": "Roulette",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/DG/1_4.png",
                    "vendorId": 7,
                    "vendorCode": "DG",
                    "imgUrl2": null,
                    "customGameType": 0
                }
            ]
        },
        {
            "vendorCode": "SEXY_Video",
            "sort": 1,
            "childList": [
                {
                    "gameID": "5956fee9c7e1524f0e6310e75a368c81",
                    "gameNameEn": "DragonTiger",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-006.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "ab22f33340fac5c424ba87c259204002",
                    "gameNameEn": "Baccarat Insurance",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-009.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "2d5b06cf3cc2aa86777523de7df46a78",
                    "gameNameEn": "Baccarat Classic",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-014.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "e2b258c3076709d5bef791b5031b7bd2",
                    "gameNameEn": "Baccarat",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-002.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "828afebe8ddb20b96b670e471262c3d1",
                    "gameNameEn": "SicBo",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-007.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "ab22f33340fac5c424ba87c259204002",
                    "gameNameEn": "Roulette",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-009.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "828afebe8ddb20b96b670e471262c3d1",
                    "gameNameEn": "Red Blue Duel",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-010.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "828afebe8ddb20b96b670e471262c3d1",
                    "gameNameEn": "Extra Andar Bahar",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-012.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "2d5b06cf3cc2aa86777523de7df46a78",
                    "gameNameEn": "Thai Hi Lo",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/SEXY_Video/MX-LIVE-014.png",
                    "vendorId": 27,
                    "vendorCode": "SEXY_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                }
            ]
        },
        {
            "vendorCode": "MG_Video",
            "sort": 0,
            "childList": [
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Roulette ",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_Roulette.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Baccarat",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_MP_Baccarat.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Bonus Baccarat",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_Baccarat.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Baccarat - Playboy (NC)",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_BaccaratplayboyNC.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "No Commission Baccarat",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_BaccaratNC.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Baccarat - Playboy",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_Baccarat_Playboy.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Roulette - Playboy",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_Roulette_Playboy.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Sicbo",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGames_Sicbo.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                },
                {
                    "gameID": "a1a01855da3f29d09a848e705ad76ea1",
                    "gameNameEn": "Auto Roulette ",
                    "img": "https://ossimg.yuk87k786d.com/sikkim/gamelogo/MG_Video/SMG_titaniumLiveGamesAutoRoulette.png",
                    "vendorId": 38,
                    "vendorCode": "MG_Video",
                    "imgUrl2": null,
                    "customGameType": 0
                }
            ]
        }
    ],
    "code": 0,
    "msg": "Succeed",
    "msgCode": 0,
    "serviceNowTime": "' . $serviceNowTimeFormatted . '"
}';

$data = json_decode($jsonData, true);
$response = json_encode($data, JSON_PRETTY_PRINT);
header('Content-Type: application/json');
echo $response;
?>