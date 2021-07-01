<?php
const CLIENT_ID = "234218832452-vchpu8079urgcmp9askc6cum5oej4au1.apps.googleusercontent.com";
const CLIENT_FBID = "3648086378647793";
const CLIENT_SECRET = "ZlOPVwl1-V39Cc_1OlGx5p8Y";
const CLIENT_FBSECRET = "1b5d764e7a527c2b816259f575a59942";
const STATE = "fdzefzefze";
function handleLogin()
{
    // http://.../auth?response_type=code&client_id=...&scope=...&state=...
    echo "<h1>Login with OAUTH</h1>";
    echo "<a href='https://accounts.google.com/o/oauth2/v2/auth?redirect_uri=http://localhost/handle-redirect&response_type=code"
        . "&client_id=" . CLIENT_ID
        . "&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email"
        . "&access_type=offline'>Se connecter avec Oauth Server</a>&nbsp";
    echo "<a href='https://www.facebook.com/v2.10/dialog/oauth?response_type=code"
        . "&client_id=" . CLIENT_FBID
        . "&scope=email"
        . "&state=" . STATE
        . "&redirect_uri=https://localhost/fbauth-success'>Se connecter avec Facebook</a>";
}

function handleError()
{
    ["state" => $state] = $_GET;
    echo "{$state} : Request cancelled";
}

function handleSuccess()
{
    echo 'salut';
    /*["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) {
        throw new RuntimeException("{$state} : invalid state");
    }
    // https://auth-server/token?grant_type=authorization_code&code=...&client_id=..&client_secret=...
    getUser([
        'grant_type' => "authorization_code",
        "code" => $code,
    ]);*/
}

function handleRedirect() {
    if (isset($_GET['code'])) {

        $url = "https://oauth2.googleapis.com/token?"
            . "code=" . $_GET['code']
            . "&client_id=" . CLIENT_ID
            . "&client_secret=" . CLIENT_SECRET
            . "&redirect_uri=http://localhost/auth-success"
            . "&grant_type=authorization_code";


        header('Location: ' . $url);

    } else {
        echo 'no';
    }

}

function handleFbSuccess()
{
    ["state" => $state, "code" => $code] = $_GET;
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
    echo file_get_contents($userUrl, false, $context);
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
    case '/handle-redirect':
        handleRedirect();
        break;
    case '/fbauth-success':
        handleFbSuccess();
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
    default:
        http_response_code(404);
        break;
}
