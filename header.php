<?php
$pageTitle = $pageTitle ?? 'Adversarial Techniques and Research';
$pageDescription = $pageDescription ?? 'A collection of computer security resources including penetration testing cheat sheets, boot2root writeups, vulnerability research, and security tooling.';
echo '<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/img/favicon.ico" sizes="any">
    <title>' . htmlspecialchars($pageTitle) . ' | Hooper Labs</title>
    <meta name="description" content="' . htmlspecialchars($pageDescription) . '">
    <meta property="og:title" content="' . htmlspecialchars($pageTitle) . ' | Hooper Labs">
    <meta property="og:description" content="' . htmlspecialchars($pageDescription) . '">
    <meta property="og:type" content="website">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="/css/style.css" />
</head>';
?>
