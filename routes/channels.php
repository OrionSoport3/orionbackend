<?php

use Illuminate\Support\Facades\Broadcast;
use Tymon\JWTAuth\Facades\JWTAuth;


/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });
// // Broadcast::channel('', ::class);

// Broadcast::channel('activities-channel', function ($user) {
//     // Aquí puedes validar la autenticación del usuario si es necesario
//     return $user; // El canal está disponible para el usuario autenticado
// });

Broadcast::channel('activities-channel.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id; // Permite acceso solo si el ID del usuario coincide
});