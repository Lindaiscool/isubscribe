<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Register a new user in the system.
     *
     * @param Request $request The request object containing input data.
     * @return \Illuminate\Http\JsonResponse JSON response containing the user data and a token.
     */
    public function register(Request $request)
    {
        // Validate the incoming request data.
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
        ]);

        // Create the user in the database.
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password for security.
        ]);

        // Generate a new token for the user after registration.
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return a JSON response with user data and access token.
    return response()->json([
        'user' => $user,
        'token' => $token,
    ], 201);
    }

    /**
     * Authenticate a user and return a token if credentials are valid.
     *
     * @param Request $request The request object containing login credentials.
     * @return \Illuminate\Http\JsonResponse JSON response containing the user data and a token.
     */
    public function login(Request $request)
    {
        // Validate the login credentials.
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt to authenticate the user using the provided credentials.
        if (!Auth::attempt($credentials)) {
            // Return response for unauthorized access.
            return response()->json(['message' => 'Invalid login credentials'], status: 401);
        }

        // Retrieve the authenticated user.
        $user = Auth::user();
        // Generate a new token for the session.
        $token = $user->createToken(name: 'auth_token')->plainTextToken;

        // Return a successful login response along with the user data and token.
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Log out the user by invalidating user's token.
     *
     * @param Request $request The request object.
     * @return \Illuminate\Http\JsonResponse JSON response indicating successful logout.
     */
    public function logout(Request $request)
    {
        // Verwijder de API-tokens van de gebruiker
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

}
