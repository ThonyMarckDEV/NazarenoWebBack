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
   
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('username', 'password');

        try {
            
            $usuario = Usuario::where('username', $credentials['username'])->first();

           
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

          
            if ($usuario->status === 'loggedOn') {
                return response()->json(['message' => 'Usuario ya logueado'], 409);
            }

            
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

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
    
}
