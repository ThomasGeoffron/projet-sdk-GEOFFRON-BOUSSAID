<?php
const CLIENT_ID = "234218832452-vchpu8079urgcmp9askc6cum5oej4au1.apps.googleusercontent.com";
const CLIENT_SECRET = "ZlOPVwl1-V39Cc_1OlGx5p8Y";
const CLIENT_FBID = "4117744681651255";
const CLIENT_FBSECRET = "754f0d9bfb888e92a6a72450ce95c66f";
const STATE = "fdzefzefze";

session_start();
function handleLogin()
{
    $_SESSION['state'] = uniqid();
    // http://.../auth?response_type=code&client_id=...&scope=...&state=...

    $urlGoogle = "https://accounts.google.com/o/oauth2/v2/auth?"
        . http_build_query([
            'redirect_uri' => 'https://localhost/redirect-google',
            'response_type' => 'code',
            'client_id' => CLIENT_ID,
            'scope' => 'https://www.googleapis.com/auth/userinfo.email',
            'state' => $_SESSION['state'],
            'access_type' => 'offline'
        ]);

    $urlFB = "https://www.facebook.com/v11.0/dialog/oauth?"
        . http_build_query([
            'client_id' => CLIENT_FBID,
            'redirect_uri' => 'https://localhost/redirect-fb',
            'state' => $_SESSION['state']
        ]);
    $urlDiscord = "";
    $urlMicrosoft = "";

    echo "<h1>Login with :</h1>";
    echo "<a href='". $urlGoogle ."'><img src='http://assets.stickpng.com/thumbs/5847f9cbcef1014c0b5e48c8.png' width='50px' height='50px'></a>";
    echo "<a href='". $urlFB ."'><img src='http://assets.stickpng.com/thumbs/584ac2d03ac3a570f94a666d.png' width='50px' height='50px'></a>";
    echo "<a href='https://www.facebook.com/v2.10/dialog/oauth?response_type=code"
        . "&client_id=" . CLIENT_FBID
        . "&scope=email"
        . "&state=" . $_SESSION['state']
        . "&redirect_uri=https://localhost/fbauth-success'><img src='https://www.freepnglogos.com/uploads/discord-logo-png/concours-discord-cartes-voeux-fortnite-france-6.png' width='50px' height='50px'></a>";
    echo "<a href='https://www.facebook.com/v2.10/dialog/oauth?response_type=code"
        . "&client_id=" . CLIENT_FBID
        . "&scope=email"
        . "&state=" . $_SESSION['state']
        . "&redirect_uri=https://localhost/fbauth-success'><img src='https://flowerdocs.com/img/documentation/microsoft.png' width='50px' height='50px'></a>";

    if (isset($_SESSION['google_user'])) {
        echo '<h2>Salut ' . $_SESSION['google_user'] . " !</h2>";

        echo "<a href='/disconnect'>Deconnexion</a>";
    }
    if (isset($_SESSION['fb_user'])) {
        echo '<h2>Salut ' . $_SESSION['fb_user'] . " !</h2>";

        echo "<a href='/disconnect'>Deconnexion</a>";
    }
}

function handleError()
{
    ["state" => $state] = $_GET;
    echo "{$state} : Request cancelled";
}

function redirectGoogle() {
    if (isset($_GET['code'])) {

        $httpquery = http_build_query([
            'redirect_uri' => 'https://localhost/redirect-google',
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'code' => $_GET['code'],
            'grant_type' => 'authorization_code']);

        $url = "https://oauth2.googleapis.com/token?" . $httpquery;

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'user_agent' => 'Google OAuth Client',
                'header' => 'Accept: application/json\r\n' .
                    'Content-type: application/x-www-form-urlencoded\r\n' .
                    'Content-Length: '.strlen($httpquery) . '\r\n',
                'content' => $httpquery
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        $response = $response ? json_decode($response) : $response;
        $accessTokenGoogle = $response->access_token ?? false;

        if ($accessTokenGoogle) {

            $url = "https://www.googleapis.com/oauth2/v2/userinfo";

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Authorization: Bearer ' . $accessTokenGoogle,
                    ]
                ]
            ]);

            $user = json_decode(file_get_contents($url, false, $context));

            $_SESSION['google_user'] = $user->email;

            header('Location: /login');
        }
        die();

    } else {
        echo 'no';
    }

}

function redirectFb()
{
    $url = "https://graph.facebook.com/v11.0/oauth/access_token?" . http_build_query([
        'client_id' => CLIENT_FBID,
        'redirect_uri' => 'https://localhost/redirect-fb',
        'client_secret' => CLIENT_FBSECRET,
        'code' => $_GET['code'],
        'grant_type' => 'authorization_code']);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json\r\n'
                . 'Content-type: application/x-www-form-urlencoded\r\n'
        ]
    ]);

    $response = file_get_contents($url, false, $context);
    $response = $response ? json_decode($response) : $response;
    $accessTokenFb = $response->access_token ?? false;

    if ($accessTokenFb) {

        $url = "https://graph.facebook.com/me?scope=email";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Authorization: Bearer ' . $accessTokenFb,
                ]
            ]
        ]);

        $user = json_decode(file_get_contents($url, false, $context));

        $_SESSION['fb_user'] = $user->name;

        header('Location: /login');

    }

    /*["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) {
        throw new RuntimeException("{$state} : invalid state");
    }
    // https://auth-server/token?grant_type=authorization_code&code=...&client_id=..&client_secret=...
    $url = "https://graph.facebook.com/oauth/access_token?grant_type=authorization_code&code={$code}&client_id=" . CLIENT_FBID . "&client_secret=" . CLIENT_FBSECRET."&redirect_uri=https://localhost/fbauth-success";
    $result = file_get_contents($url);
    $resultDecoded = json_decode($result, true);
    ["access_token"=> $token] = $resultDecoded;
    $userUrl = "https://graph.facebook.com/me?fields=id,name,email";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);
    echo file_get_contents($userUrl, false, $context);*/
}

function getUser($params)
{
    $url = "http://oauth-server:8081/token?client_id=" . CLIENT_ID . "&client_secret=" . CLIENT_SECRET . "&" . http_build_query($params);
    $result = file_get_contents($url);
    $result = json_decode($result, true);
    $token = $result['access_token'];

    $apiUrl = "http://oauth-server:8081/me";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);
    echo file_get_contents($apiUrl, false, $context);
}

/**
 * AUTH CODE WORKFLOW
 * => Generate link (/login)
 * => Get Code (/auth-success)
 * => Exchange Code <> Token (/auth-success)
 * => Exchange Token <> User info (/auth-success)
 */
$route = strtok($_SERVER["REQUEST_URI"], "?");
switch ($route) {
    case '/login':
        handleLogin();
        break;
    case '/auth-success':
        handleSuccess();
        break;
    case '/redirect-google':
        redirectGoogle();
        break;
    case '/redirect-fb':
        redirectFb();
        break;
    case '/auth-cancel':
        handleError();
        break;
    case '/password':
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            echo '<form method="POST">';
            echo '<input name="username">';
            echo '<input name="password">';
            echo '<input type="submit" value="Submit">';
            echo '</form>';
        } else {
            ["username" => $username, "password" => $password] = $_POST;
            getUser([
                'grant_type' => "password",
                "username" => $username,
                "password" => $password
            ]);
        }
        break;
    case '/disconnect':
        session_destroy();
        header('Location: /login');
        break;
    default:
        http_response_code(404);
        break;
}
