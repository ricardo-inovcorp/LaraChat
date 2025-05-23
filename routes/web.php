<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageReactionController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Página inicial
Route::get('/', function () {
    return view('welcome');
});

// Autenticação
Auth::routes();

// Salas
Route::middleware(['auth'])->group(function () {
    Route::get('/home', function() {
        return redirect()->route('rooms.index');
    })->name('home');
    
    // Rotas de Salas
    Route::resource('rooms', RoomController::class);
    Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::post('/rooms/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
    Route::post('/rooms/{room}/request-join', [RoomController::class, 'requestJoin'])->name('rooms.request-join');
    Route::get('/rooms/{room}/join-requests', [RoomController::class, 'showJoinRequests'])->name('rooms.join-requests');
    Route::post('/join-requests/{request}/approve', [RoomController::class, 'approveJoinRequest'])->name('join-requests.approve');
    Route::post('/join-requests/{request}/reject', [RoomController::class, 'rejectJoinRequest'])->name('join-requests.reject');
    
    // Rotas de Mensagens
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/conversation/{user}', [MessageController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead'])->name('messages.read');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    
    // Rotas de Reações às Mensagens
    // Rota para adicionar/remover reações (aceita GET e POST)
    Route::match(['get', 'post'], '/messages/{message}/reactions', [MessageReactionController::class, 'toggle'])->name('messages.reactions.toggle');
    
    // Rota para obter reações - deve vir depois para evitar conflito com a rota match
    Route::get('/messages/{message}/reactions/list', [MessageReactionController::class, 'getReactions'])->name('messages.reactions.get');
    
    // Rotas de Usuários
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/users/{user}/update-status', [UserController::class, 'updateUserStatus'])->name('users.update-status');
    
    // Rotas de Notificações
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
});
