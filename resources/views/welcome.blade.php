<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Laravel 12</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #7e22ce 100%);
        }

        .card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover::before {
            opacity: 1;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 15px 15px -5px rgba(0, 0, 0, 0.1);
        }

        .icon-container {
            transition: all 0.3s ease;
        }

        .card:hover .icon-container {
            transform: scale(1.1);
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .glow {
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        /* Swagger Icon */
        .swagger-icon {
            fill: #4CAF50;
        }

        /* Horizon Icon */
        .horizon-icon {
            background: linear-gradient(135deg, #F4645F 0%, #E63946 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Log Viewer Icon */
        .log-viewer-icon {
            background: linear-gradient(135deg, #FFD166 0%, #F77F00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
<div class="max-w-6xl w-full">
    <!-- Header -->
    <div class="text-center mb-16">
        <div class="floating inline-block mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 38" class="w-16 h-16 mx-auto text-white">
                <path fill="currentColor" d="M25.385 0C11.336 0 0 11.337 0 25.385c0 14.048 11.336 25.385 25.385 25.385 14.048 0 25.385-11.337 25.385-25.385C50.77 11.337 39.433 0 25.385 0zm0 47.692c-12.314 0-22.308-9.994-22.308-22.307S13.07 3.077 25.385 3.077c12.313 0 22.307 9.994 22.307 22.308S37.698 47.692 25.385 47.692z"/>
                <path fill="currentColor" d="M25.385 12.308c-7.19 0-13.077 5.887-13.077 13.077s5.887 13.077 13.077 13.077 13.077-5.887 13.077-13.077-5.887-13.077-13.077-13.077zm0 23.077c-5.523 0-10-4.477-10-10s4.477-10 10-10 10 4.477 10 10-4.477 10-10 10z"/>
            </svg>
        </div>
        <h1 class="text-5xl md:text-6xl font-bold text-white mb-4 glow">Welcome to Laravel 12</h1>
        <p class="text-xl text-purple-100 max-w-2xl mx-auto">Development panels and tools for modern applications</p>
    </div>

    <!-- Panel Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Swagger Panel -->
        <a href="{{ url('/api/documentation') }}" class="card bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="icon-container bg-green-100 p-5 rounded-2xl mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" class="w-12 h-12 swagger-icon">
                        <path d="M218.9 128c0-50.1-40.8-90.9-90.9-90.9S37.1 77.9 37.1 128s40.8 90.9 90.9 90.9 90.9-40.8 90.9-90.9z"/>
                        <path fill="#fff" d="M128 37.1c-50.1 0-90.9 40.8-90.9 90.9s40.8 90.9 90.9 90.9 90.9-40.8 90.9-90.9-40.8-90.9-90.9-90.9zm0 173.5c-45.5 0-82.6-37.1-82.6-82.6s37.1-82.6 82.6-82.6 82.6 37.1 82.6 82.6-37.1 82.6-82.6 82.6z"/>
                        <path fill="#fff" d="M128 54.3c-40.7 0-73.7 33.1-73.7 73.7s33.1 73.7 73.7 73.7 73.7-33.1 73.7-73.7-33.1-73.7-73.7-73.7zm0 139.1c-36.1 0-65.4-29.3-65.4-65.4s29.3-65.4 65.4-65.4 65.4 29.3 65.4 65.4-29.3 65.4-65.4 65.4z"/>
                        <path fill="#fff" d="M128 71.5c-31.2 0-56.5 25.3-56.5 56.5s25.3 56.5 56.5 56.5 56.5-25.3 56.5-56.5-25.3-56.5-56.5-56.5zm0 104.7c-26.6 0-48.2-21.6-48.2-48.2s21.6-48.2 48.2-48.2 48.2 21.6 48.2 48.2-21.6 48.2-48.2 48.2z"/>
                        <path fill="#fff" d="M128 88.7c-21.7 0-39.3 17.6-39.3 39.3s17.6 39.3 39.3 39.3 39.3-17.6 39.3-39.3-17.6-39.3-39.3-39.3zm0 70.3c-17.1 0-31-13.9-31-31s13.9-31 31-31 31 13.9 31 31-13.9 31-31 31z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Swagger</h3>
                <p class="text-gray-600">API Documentation</p>
                <div class="mt-4 text-green-600 font-medium">
                    <i class="fas fa-arrow-left ml-2"></i> Access Panel
                </div>
            </div>
        </a>

        <!-- Horizon Panel -->
        <a href="{{ url('/horizon') }}" class="card bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="icon-container bg-blue-100 p-5 rounded-2xl mb-5">
                    <i class="fas fa-inbox horizon-icon text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Horizon</h3>
                <p class="text-gray-600">Queue Monitoring</p>
                <div class="mt-4 text-blue-600 font-medium">
                    <i class="fas fa-arrow-left ml-2"></i> Access Panel
                </div>
            </div>
        </a>

        <!-- Log Viewer Panel -->
        <a href="{{ url('/log-viewer') }}" class="card bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="icon-container bg-yellow-100 p-5 rounded-2xl mb-5">
                    <i class="fas fa-file-lines log-viewer-icon text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Log Viewer</h3>
                <p class="text-gray-600">Application Logs</p>
                <div class="mt-4 text-yellow-600 font-medium">
                    <i class="fas fa-arrow-left ml-2"></i> Access Panel
                </div>
            </div>
        </a>

        <!-- Reverb Test -->
        <a href="http://localhost:8080/reverb.php" target="_blank" class="card bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="icon-container bg-purple-100 p-5 rounded-2xl mb-5">
                    <i class="fas fa-broadcast-tower text-purple-600 text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Reverb Test</h3>
                <p class="text-gray-600">WebSockets</p>
                <div class="mt-4 text-purple-600 font-medium">
                    <i class="fas fa-arrow-left ml-2"></i> Test Now
                </div>
            </div>
        </a>
    </div>

    <!-- Footer -->
    <div class="mt-16 text-center">
        <div class="inline-block bg-white/10 backdrop-blur-sm px-6 py-3 rounded-full">
            <p class="text-purple-100 font-medium">Laravel Version {{ app()->version() }}</p>
        </div>
    </div>
</div>
</body>
</html>
