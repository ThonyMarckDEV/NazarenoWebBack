<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Login de usuario y generación de token JWT.
     */
    public function login(Request $request)
    {
        // Validar los campos de entrada
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);
    
        $credentials = $request->only('username', 'password');
    
        try {
            // Verificar si el usuario ya está logueado al obtener solo el campo `status`
            $status = Usuario::where('username', $credentials['username'])->value('status');
    
            // Si el usuario no existe
            if ($status === null) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
    
            // Verificar si el estado ya es `loggedOn`
            if ($status === 'loggedOn') {
                return response()->json(['message' => 'Usuario ya logueado'], 409);
            }
    
            // Validar las credenciales
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }
    
            // Cambiar el estado a 'loggedOn' solo si las credenciales son correctas
            Usuario::where('username', $credentials['username'])->update(['status' => 'loggedOn']);
    
        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo crear el token'], 500);
        }
    
        return response()->json(compact('token'));
    }

    /**
     * Logout del usuario y revocación del token JWT.
     */
    public function logout(Request $request)
    {
        // Validar que el campo idUsuario esté presente en la solicitud
        $request->validate([
            'idUsuario' => 'required|integer',
        ]);
    
        // Buscar al usuario por idUsuario
        $user = Usuario::where('idUsuario', $request->idUsuario)->first();
    
        if ($user) {
            try {
                // Cambiar el estado del usuario a 'loggedOff'
                $user->status = 'loggedOff';
                $user->save();
    
                return response()->json(['success' => true, 'message' => 'Usuario deslogueado correctamente'], 200);
            } catch (JWTException $e) {
                return response()->json(['error' => 'No se pudo desloguear al usuario'], 500);
            }
        }
    
        return response()->json(['success' => false, 'message' => 'No se pudo encontrar el usuario'], 404);
    }

    /**
     * Refrescar el token JWT.
     */
    public function refreshToken(Request $request)
    {
        try {
            $oldToken = JWTAuth::getToken(); // Captura el token actual antes de refrescarlo

            // Log del token recibido
            Log::info('Refrescando token: Token recibido', ['token' => (string) $oldToken]);

            // Refresca el token y devuelve uno nuevo
            $newToken = JWTAuth::refresh($oldToken);

            // Log del nuevo token
            Log::info('Token refrescado: Nuevo token', ['newToken' => $newToken]);

            return response()->json(['accessToken' => $newToken], 200);
        } catch (JWTException $e) {
            // Log del error si falla el refresco
            Log::error('Error al refrescar el token', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'No se pudo refrescar el token'], 500);
        }
    }


    public function updateLastActivity(Request $request)
    {
        // Validar que el campo idUsuario esté presente en la solicitud
        $request->validate([
            'idUsuario' => 'required|integer',
        ]);
        
        // Buscar al usuario por idUsuario
        $user = Usuario::find($request->idUsuario);
        
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        
        // Asumiendo que 'activity' es una relación definida en el modelo Usuario
        // y que quieres actualizar o crear un registro de actividad.
        $user->activity()->updateOrCreate(
            ['idUsuario' => $user->idUsuario],
            ['last_activity' => now()]
        );
        
        return response()->json(['message' => 'Last activity updated'], 200);
    }
    
}
