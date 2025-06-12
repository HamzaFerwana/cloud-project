<!doctype html>
<html lang="en">

<head>
    <title>@yield('title')</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    @yield('styles')
</head>

<body>

    <div class="container py-5">
        <div class="topbar">
            <div class="d-flex gap-3 justify-content-end align-items-center">
                @auth
                    <button class="btn btn-primary">{{ Auth::user()->name }}</button>
                    <a onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        href="{{ route('logout') }}" class="btn btn-danger">Logout</a>

                    <form hidden id="logout-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                    </form>
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-secondary">Register</a>
                @endguest
            </div>
            <div class="d-flex align-items-center justify-content-center mb-5 flex-column gap-3">
                <h1>Welcome To Cloud Project!</h1>
                <div>
                    <a href="{{ route('cloud-project.index') }}" class="btn btn-sm btn-primary">Home</a>
                    <a href="{{ route('cloud-project.search-documents') }}" class="btn btn-sm btn-primary">Search In
                        Your
                        Uploaded Files</a>
                    <a href="{{ route('cloud-project.classify-doucments') }}" class="btn btn-sm btn-primary">Classify
                        Your Uploaded Documents</a>
                </div>
            </div>
        </div>
        @yield('content')
    </div>










    <!-- Bootstrap JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous">
    </script>
    @yield('scripts')
</body>

</html>
