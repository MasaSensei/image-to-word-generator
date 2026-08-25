<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise DocGen | Image to Word</title>

    <!-- Font: editorial serif for headings, grotesk for UI/body -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,500;8..60,600;8..60,700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS (via CDN for instant styling, Vite handles JS) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['"Source Serif 4"', 'Georgia', 'serif'],
                    },
                    colors: {
                        paper: {
                            DEFAULT: '#F7F5F0',
                            card: '#FFFFFF',
                            line: '#E4E0D6',
                        },
                        ink: {
                            DEFAULT: '#1C2B3A',
                            soft: '#3D4F63',
                            muted: '#8A8578',
                        },
                        brand: {
                            50: '#FBEDE7',
                            600: '#B5482A',
                            700: '#9B3B21',
                            900: '#5C2313',
                        }
                    },
                    boxShadow: {
                        paper: '0 1px 2px rgba(28,43,58,0.06), 0 1px 0 rgba(28,43,58,0.04)',
                    }
                }
            }
        }
    </script>

    <!-- Load compiled assets from Vite FIRST -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Then Alpine.js and Sortable.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>

<body class="bg-paper text-ink font-sans antialiased min-h-screen flex flex-col">

    <!-- Navigation: thin, ink navy, no heavy shadow -->
    <nav class="bg-ink text-paper">
        <div class="max-w-5xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span class="font-serif text-lg font-semibold tracking-wide">Enterprise DocGen</span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow max-w-5xl mx-auto w-full py-12 px-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-paper-line py-6 mt-auto">
        <div class="max-w-5xl mx-auto px-6 text-center text-xs text-ink-muted tracking-wide">
            &copy; {{ date('Y') }} Enterprise Systems. All rights reserved.
        </div>
    </footer>
</body>

</html>
