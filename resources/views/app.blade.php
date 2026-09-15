<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-tema-categoria="{{ request()->attributes->get('temaCategoria', 'adultos') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- FOUC Prevention: set data-tema BEFORE CSS paints -->
        <script>
            (function(){
                var c = document.documentElement.dataset.temaCategoria || 'adultos';
                var h = new Date().getHours();
                var m = (h >= 6 && h < 18) ? 'dia' : 'noche';
                document.documentElement.dataset.tema = c + '-' + m;
            })();
        </script>

        <!-- Scripts & Styles -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
