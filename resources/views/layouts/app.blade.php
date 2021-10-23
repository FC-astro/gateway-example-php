<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>JACKPOT</title>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body class="bg-black">
        <nav class="p-3 bg-red-500 flex justify-between mb-3 text-white">
            <ul class="flex items-center">
                @auth
                <li>
                    <a href="{{ route('transactions') }}" class="p-3">Dashboard</a>
                </li>
                @endauth
            </ul>

            <div class="items-center font-extrabold">
                JACKPOT
            </div>

            <ul class="flex items-center">
                @auth
                    <li>
                        <form action="{{ route('logout') }}" method="post" class="p-3 inline">
                            @csrf
                            <button type="submit">Logout</button>
                        </form>
                    </li>
                @endauth
                @guest
                    <li>
                        <a href="{{ route('login') }}" class="p-3">Login</a>
                    </li>
                    <li>
                        <a href="{{ route('register') }}" class="p-3">Register</a>
                    </li>
                @endguest
            </ul>
        </nav>
        @yield('content')
    </body>
</html>
