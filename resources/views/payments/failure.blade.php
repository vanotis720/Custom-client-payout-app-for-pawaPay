<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-red-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-6 text-center">
        <h1 class="text-2xl font-semibold text-red-700 mb-3">Payment Failed</h1>
        <p class="text-gray-700 mb-3">{{ session('error', 'Payment could not be completed.') }}</p>
    </div>
</body>

</html>
