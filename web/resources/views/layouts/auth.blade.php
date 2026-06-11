<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "Login") - Kafe Satu Per Dua</title>
    @vite(["resources/css/app.css", "resources/js/app.js"])
</head>
<body class="h-full bg-gradient-to-br from-amber-800 to-amber-950 flex items-center justify-center p-4">
    @yield("content")
</body>
</html>