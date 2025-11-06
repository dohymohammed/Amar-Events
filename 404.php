<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
<title>404 Not Found</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}
.animate-float {
  animation: float 3s ease-in-out infinite;
}
</style>
</head>
<body class="bg-gray-900 text-gray-100 flex items-center justify-center min-h-screen relative overflow-hidden">

<div class="absolute w-[400px] h-[400px] md:w-[600px] md:h-[600px] bg-blue-600 opacity-20 rounded-full -top-64 -left-64 animate-float"></div>
<div class="absolute w-[250px] h-[250px] md:w-[400px] md:h-[400px] bg-purple-600 opacity-20 rounded-full -bottom-32 -right-32 animate-float"></div>

<div class="z-10 text-center px-4">
    <h1 class="text-6xl sm:text-7xl md:text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500 mb-4">404</h1>
    <h2 class="text-2xl sm:text-3xl md:text-4xl font-semibold mb-4">Oops! Page Not Found</h2>
    <p class="text-gray-300 mb-6 md:mb-8 max-w-md md:max-w-xl mx-auto text-sm sm:text-base md:text-lg">
        The page you are looking for doesn’t exist, has been moved, or is temporarily unavailable.
    </p>
    <div class="flex justify-center gap-4 flex-wrap">
        <a href="/" class="bg-gray-700 hover:bg-gray-600 text-white px-5 py-2 sm:px-6 sm:py-3 rounded-lg transition text-sm sm:text-base">Go Home</a>
        <a href="/events" class="bg-purple-600 hover:bg-purple-500 text-white px-5 py-2 sm:px-6 sm:py-3 rounded-lg transition text-sm sm:text-base">Events</a>
    </div>
</div>

<div class="absolute w-3 h-3 md:w-4 md:h-4 bg-white rounded-full top-16 left-8 opacity-30 animate-float"></div>
<div class="absolute w-2.5 h-2.5 md:w-3 md:h-3 bg-white rounded-full bottom-10 right-20 md:right-32 opacity-20 animate-float"></div>
<div class="absolute w-4 h-4 md:w-5 md:h-5 bg-white rounded-full top-1/2 left-1/4 md:left-1/3 opacity-10 animate-float"></div>

</body>
</html>
