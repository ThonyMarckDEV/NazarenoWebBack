<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
        // Validar que el nombre de usuario y la contraseña están presentes
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);
    
        // Obtener las credenciales de usuario y contraseña
        $credentials = [
            'username' => $request->input('username'),
            'password' => $request->input('password')
        ];
    
        try {
            // Buscar el usuario por nombre de usuario
            $usuario = Usuario::where('username', $credentials['username'])->first();
    
            // Verificar si el usuario existe
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
    
            // Verificar si el usuario ya está logueado
            if ($usuario->status === 'loggedOn') {
                return response()->json(['message' => 'Usuario ya logueado'], 409);
            }
    
            // Intentar autenticar y generar el token JWT usando el campo 'username'
            if (!$token = JWTAuth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }
    
            // Actualizar el estado del usuario a "loggedOn"
            $usuario->update(['status' => 'loggedOn']);
    
            return response()->json(compact('token'));
        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo crear el token'], 500);
        }
    }

    /**
     * Logout del usuario y revocación del token JWT.
     */
    public function logout(Request $request)
    {
        
        $request->validate([
            'idUsuario' => 'required|integer',
        ]);
    
      
        $user = Usuario::where('idUsuario', $request->idUsuario)->first();
    
        if ($user) {
            try {
                
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
            $oldToken = JWTAuth::getToken();

         
            Log::info('Refrescando token: Token recibido', ['token' => (string) $oldToken]);

            
            $newToken = JWTAuth::refresh($oldToken);

          
            Log::info('Token refrescado: Nuevo token', ['newToken' => $newToken]);

            return response()->json(['accessToken' => $newToken], 200);
        } catch (JWTException $e) {
            
            Log::error('Error al refrescar el token', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'No se pudo refrescar el token'], 500);
        }
    }


    public function updateLastActivity(Request $request)
    {
        
        $request->validate([
            'idUsuario' => 'required|integer',
        ]);
        
        
        $user = Usuario::find($request->idUsuario);
        
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        
       
        $user->activity()->updateOrCreate(
            ['idUsuario' => $user->idUsuario],
            ['last_activity' => now()]
        );
        
        return response()->json(['message' => 'Last activity updated'], 200);
    }


    public function checkStatus(Request $request)
    {
        $idUsuario = $request->input('idUsuario');
    
        // Obtener el token del encabezado Authorization
        $authHeader = $request->header('Authorization');
    
        // Extraer el token de la cadena 'Bearer [token]'
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            // Si no se encuentra el token, responde con error
            return response()->json(['status' => 'invalidToken'], 401);
        }
    
        if (!$idUsuario || !$token) {
            // Sin idUsuario o token, responde como inválido
            return response()->json(['status' => 'invalidToken'], 401);
        }
    
        // Busca el usuario por id
        $user = Usuario::find($idUsuario);
    
        // Verifica si el token es válido
        $isTokenValid = $this->validateToken($token, $idUsuario);
    
        // Responde según el estado y validez del token
        if (!$user) {
            // Usuario no encontrado en la BD
            return response()->json(['status' => 'loggedOff'], 401);
        }
    
        if ($user && !$isTokenValid) {
            // Usuario existe pero el token es inválido
            return response()->json(['status' => 'loggedOnInvalidToken'], 401);
        }
    
        if ($user->status === 'loggedOff') {
            // Usuario existe pero está marcado como `loggedOff` en la BD
            return response()->json(['status' => 'loggedOff'], 401);
        }
    
        // Usuario está activo y el token es válido
        return response()->json(['status' => 'loggedOn', 'isTokenValid' => true], 200);
    }
    
    // Valida el token JWT y su expiración
    private function validateToken($token, $idUsuario)
    {
        try {
            // Decodificar y verificar el token JWT
            $payload = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
    
            $expiration = $payload->exp;
            $tokenUserId = $payload->idUsuario ?? null;
    
            // Verifica que el token no esté expirado y que el idUsuario coincida
            return $expiration > time() && $tokenUserId == $idUsuario;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    
}
