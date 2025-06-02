<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($title) ? htmlspecialchars($title) : 'My PHP App' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif;
            padding: 1em;
            max-width: 800px;
            margin: auto;
        }
        nav {
            margin-bottom: 1em;
        }
        nav a {
            margin-right: 10px;
        }
        button {
            margin-top: 0.5em;
        }
    </style>
</head>
<body>


