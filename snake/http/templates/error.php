<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center px-4 sm:px-6 lg:px-8">

        <!-- Vector Icon -->
        <div class="mx-auto w-32 h-32 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-full h-full text-red-500" fill="none" viewBox="0 0 64 64" stroke="currentColor">
                <circle cx="32" cy="32" r="30" stroke-width="4" class="opacity-20" />
                <line x1="20" y1="20" x2="44" y2="44" stroke-width="4" stroke-linecap="round" />
                <line x1="44" y1="20" x2="20" y2="44" stroke-width="4" stroke-linecap="round" />
            </svg>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl font-bold text-gray-800 mb-4"><?php echo $code; ?></h1>

        <!-- Error Message -->
        <p class="text-xl text-gray-600 mb-6"><?php echo $message; ?></p>

    </div>
</body>

</html>