<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="2">
    <title>Telegram Debug</title>
</head>
<body>
    <h1>Latest Telegram update</h1>

    <pre>{{ json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) }}</pre>
</body>
</html>