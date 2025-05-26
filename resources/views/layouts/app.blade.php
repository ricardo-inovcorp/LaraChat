<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Pusher Configuration -->
    <meta name="pusher-key" content="{{ env('PUSHER_APP_KEY') }}">
    <meta name="pusher-cluster" content="{{ env('PUSHER_APP_CLUSTER') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('rooms.index') }}">Salas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('messages.index') }}">Mensagens</a>
                            </li>
                            @if(Auth::user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('users.index') }}">Utilizadores</a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <!-- Notifications Dropdown -->
                            <li class="nav-item dropdown me-3">
                                @php
                                    $unreadNotifications = Auth::user()->unreadNotifications;
                                    $notificationCount = $unreadNotifications->count();
                                @endphp
                                <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell fs-5"></i>
                                    @if($notificationCount > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $notificationCount }}
                                        </span>
                                    @endif
                                </a>
                                
                                <ul class="dropdown-menu dropdown-menu-end shadow notification-dropdown" aria-labelledby="notificationsDropdown" style="width: 300px; max-height: 400px; overflow-y: auto;">
                                    <li><h6 class="dropdown-header">Notificações</h6></li>
                                    
                                    @if($notificationCount > 0)
                                        @foreach($unreadNotifications as $notification)
                                            <li>
                                                @if(isset($notification->data['room_id']) && !isset($notification->data['message_id']))
                                                    <!-- Notificação de convite para sala -->
                                                    <a class="dropdown-item notification-item" href="#" 
                                                       onclick="event.preventDefault(); 
                                                               document.getElementById('mark-notification-{{ $notification->id }}').submit();">
                                                        <div class="d-flex">
                                                            <div class="flex-shrink-0">
                                                                <i class="bi bi-envelope-paper text-primary"></i>
                                                            </div>
                                                            <div class="ms-2">
                                                                <p class="mb-0">{{ $notification->data['message'] }}</p>
                                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>
                                                    </a>
                                                    <form id="mark-notification-{{ $notification->id }}" 
                                                          action="{{ route('notifications.mark-read', $notification->id) }}" 
                                                          method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                @elseif(isset($notification->data['message_id']))
                                                    <!-- Notificação de reação a mensagem -->
                                                    @php
                                                        $routeName = isset($notification->data['is_room']) && $notification->data['is_room'] 
                                                            ? 'rooms.show' 
                                                            : 'messages.conversation';
                                                        $routeParam = isset($notification->data['is_room']) && $notification->data['is_room']
                                                            ? $notification->data['room_id']
                                                            : $notification->data['conversation_user_id'];
                                                    @endphp
                                                    <a class="dropdown-item notification-item" href="#" 
                                                       onclick="event.preventDefault(); 
                                                               document.getElementById('mark-notification-{{ $notification->id }}').submit();">
                                                        <div class="d-flex">
                                                            <div class="flex-shrink-0">
                                                                <i class="bi bi-emoji-smile text-warning"></i>
                                                            </div>
                                                            <div class="ms-2">
                                                                <p class="mb-0">{{ $notification->data['message'] }}</p>
                                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>
                                                    </a>
                                                    <form id="mark-notification-{{ $notification->id }}" 
                                                          action="{{ route('notifications.mark-read', $notification->id) }}" 
                                                          method="POST" class="d-none">
                                                        @csrf
                                                    </form>
                                                @else
                                                    <!-- Outras notificações -->
                                                    <div class="dropdown-item notification-item">
                                                        <div class="d-flex">
                                                            <div class="flex-shrink-0">
                                                                <i class="bi bi-info-circle text-info"></i>
                                                            </div>
                                                            <div class="ms-2">
                                                                <p class="mb-0">{{ $notification->data['message'] ?? 'Notificação' }}</p>
                                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-center">
                                                    <small>Marcar todas como lidas</small>
                                                </button>
                                            </form>
                                        </li>
                                    @else
                                        <li><p class="dropdown-item text-muted mb-0">Não há notificações novas</p></li>
                                    @endif
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-avatar">
                                        @if(Auth::user()->avatar)
                                            <img src="{{ Auth::user()->avatar }}?v={{ time() }}" alt="{{ Auth::user()->name }}" class="w-100 h-100">
                                        @else
                                            <div class="avatar-initials">
                                                {{ substr(Auth::user()->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <span>{{ Auth::user()->name }}</span>
                                </a>
                                
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                                    <li><h6 class="dropdown-header">Conta</h6></li>
                                    <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person-circle me-2"></i> Meu Perfil</a></li>
                                    <li><a class="dropdown-item" href="{{ route('messages.index') }}"><i class="bi bi-envelope me-2"></i> Minhas Mensagens</a></li>
                                    <li><a class="dropdown-item" href="{{ route('rooms.index') }}"><i class="bi bi-chat-dots me-2"></i> Salas Privadas</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Preferências</h6></li>
                                    <li><a class="dropdown-item" href="{{ route('profile') }}#password"><i class="bi bi-key me-2"></i> Alterar Senha</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}" 
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="bi bi-box-arrow-right me-2"></i> Sair
                                        </a>
                                    </li>
                                </ul>
                                
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4 container">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            @yield('content')
        </main>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('LaraChat initialized');
            
            // Debug de elementos dropdown
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            console.log('Dropdown toggles encontrados:', dropdownToggles.length);
            
            // Inicializar dropdowns manualmente
            dropdownToggles.forEach(function(element) {
                // Adicionar listeners de evento diretamente
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdownMenu = this.nextElementSibling;
                    if (dropdownMenu.classList.contains('show')) {
                        dropdownMenu.classList.remove('show');
                    } else {
                        // Fechar outros dropdowns abertos
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                            menu.classList.remove('show');
                        });
                        dropdownMenu.classList.add('show');
                    }
                });
                
                // Tentar inicializar com Bootstrap também
                try {
                    new bootstrap.Dropdown(element);
                } catch (error) {
                    console.warn('Não foi possível inicializar dropdown com Bootstrap:', error);
                }
            });
            
            // Fechar dropdowns ao clicar fora
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(element) {
                        element.classList.remove('show');
                    });
                }
            });
        });
    </script>
</body>
</html>
