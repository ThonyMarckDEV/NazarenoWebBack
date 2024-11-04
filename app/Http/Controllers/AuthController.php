<?php

// AuthController.php
namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
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
            if (!Auth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }
    
            // Cambiar el estado a 'loggedOn' solo si las credenciales son correctas
            Usuario::where('username', $credentials['username'])->update(['status' => 'loggedOn']);
    
            // Generar el token
            $user = Auth::user();
            $token = JWTAuth::fromUser($user);
    
        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo crear el token'], 500);
        }
    
        return response()->json(compact('token'));
    }

    public function logout(Request $request)
    {
        // Validar que el campo idUsuario esté presente en la solicitud
        $request->validate([
            'idUsuario' => 'required|integer',
        ]);
    
        // Buscar al usuario por idUsuario
        $user = Usuario::where('idUsuario', $request->idUsuario)->first();
    
        if ($user) {
            // Cambiar el estado del usuario a 'loggedOff'
            $user->status = 'loggedOff';
            $user->save();
    
            return response()->json(['success' => true, 'message' => 'Usuario deslogueado correctamente'], 200);
        }
    
        return response()->json(['success' => false, 'message' => 'No se pudo encontrar el usuario'], 404);
    }
}
