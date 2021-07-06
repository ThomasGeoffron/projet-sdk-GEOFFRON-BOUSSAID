<?php
const CLIENT_ID = "234218832452-vchpu8079urgcmp9askc6cum5oej4au1.apps.googleusercontent.com";
const CLIENT_SECRET = "ZlOPVwl1-V39Cc_1OlGx5p8Y";
const CLIENT_FBID = "4117744681651255";
const CLIENT_FBSECRET = "754f0d9bfb888e92a6a72450ce95c66f";
const CLIENT_MICID = "81747747-54c6-4870-baa4-cb5955218b77";
const CLIENT_MICSECRET = "SC~7MYQ8.pFWD6X9o-1hTpY0_zSlW4y214";
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
    $urlMicrosoft = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?"
        . http_build_query([
            'redirect_uri' => 'https://localhost/redirect-microsoft',
            'response_type' => 'code',
            'client_id' => CLIENT_MICID,
            'scope' => 'https://graph.microsoft.com/user.read',
            'state' => $_SESSION['state']
        ]);

    echo "<h1>Login with :</h1>";
    echo "<a href='". $urlGoogle ."'><img src='http://assets.stickpng.com/thumbs/5847f9cbcef1014c0b5e48c8.png' width='50px' height='50px'></a>";
    echo "<a href='". $urlFB ."'><img src='http://assets.stickpng.com/thumbs/584ac2d03ac3a570f94a666d.png' width='50px' height='50px'></a>";
    echo "<a href='https://www.facebook.com/v2.10/dialog/oauth?response_type=code"
        . "&client_id=" . CLIENT_FBID
        . "&scope=email"
        . "&state=" . $_SESSION['state']
        . "&redirect_uri=https://localhost/fbauth-success'><img src='https://www.freepnglogos.com/uploads/discord-logo-png/concours-discord-cartes-voeux-fortnite-france-6.png' width='50px' height='50px'></a>";
    echo "<a href='". $urlMicrosoft ."'><img src='https://flowerdocs.com/img/documentation/microsoft.png' width='50px' height='50px'></a>";

    if (isset($_SESSION['google_user'])) {
        echo '<h2>Salut ' . $_SESSION['google_user'] . " !</h2>";

        echo "<a href='/disconnect'>Deconnexion</a>";
    }
    if (isset($_SESSION['fb_user'])) {
        echo '<h2>Salut ' . $_SESSION['fb_user'] . " !</h2>";

        echo "<a href='/disconnect'>Deconnexion</a>";
    }
    if (isset($_SESSION['mic_user'])) {
        echo '<h2>Salut ' . $_SESSION['mic_user'] . " !</h2>";

        echo "<a href='/disconnect'>Deconnexion</a>";
    }
}

function handleError()
{
    ["state" => $state] = $_GET;
    echo $state . " : Request cancelled";
    echo "<a href='/login'>Accueil</a>";
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
        header('Location: /auth-cancel');
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

    } else {
        header('Location: /auth-cancel');
    }
}

function redirectMicrosoft() {
    if (isset($_GET['code'])) {
        $httpquery = http_build_query([
            'redirect_uri' => 'https://localhost/redirect-microsoft',
            'client_id' => CLIENT_MICID,
            'client_secret' => CLIENT_MICSECRET,
            'code' => $_GET['code'],
            'grant_type' => 'authorization_code']);

        $url = "https://login.microsoftonline.com/common/oauth2/v2.0/token?" . $httpquery;

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'user_agent' => 'Microsoft OAuth Client',
                'header' => 'Accept: application/json\r\n' .
                    'Content-type: application/x-www-form-urlencoded\r\n' .
                    'Content-Length: '.strlen($httpquery) . '\r\n',
                'content' => $httpquery
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        $response = $response ? json_decode($response) : $response;
        $accessTokenMicrosoft = $response->access_token ?? false;

        if ($accessTokenMicrosoft) {
            $url = "https://graph.microsoft.com/v1.0/me";

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Authorization: Bearer ' . $accessTokenMicrosoft,
                    ]
                ]
            ]);

            $user = json_decode(file_get_contents($url, false, $context));

            $_SESSION['mic_user'] = $user->displayName;

            header('Location: /login');
        }
    } else {
        header('Location: /auth-cancel');
    }
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
    case '/redirect-google':
        redirectGoogle();
        break;
    case '/redirect-microsoft':
        redirectMicrosoft();
        break;
    case '/redirect-fb':
        redirectFb();
        break;
    case '/auth-cancel':
        handleError();
        break;
    case '/disconnect':
        session_destroy();
        header('Location: /login');
        break;
    default:
        http_response_code(404);
        break;
}
