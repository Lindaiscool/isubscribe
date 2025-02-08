<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Retrieve and display all users.
     *
     * @return \Illuminate\Http\JsonResponse Returns all users as a JSON response with HTTP status 200.
     */
    public function index()
    {
        // Fetch all user records from the database and return them.
        return response()->json(User::all(), 200);
    }

    /**
     * Show the form for creating a new user.
     * Note: Typically not implemented in API-driven applications as form would be handled by the frontend.
     */
    public function create()
    {
        // Method intentionally left empty.
    }

    /**
     * Store a newly created user in the database.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the user data.
     * @return \Illuminate\Http\JsonResponse Returns the newly created user as JSON with HTTP status 201.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,admin', // Validate role as either 'user' or 'admin'
        ]);

        // Create and save the new user.
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password); // Hash the password for security.
        $user->role = $request->role;
        $user->save();

        // Return the newly created user data.
        return response()->json($user, 201);
    }

    /**
     * Display a specific user by their model instance.
     *
     * @param User $user The user model instance dependency injected by Laravel.
     * @return \Illuminate\Http\JsonResponse Returns the specified user as JSON with HTTP status 200.
     */
    public function show(User $user)
    {
        // Directly return the user instance which is automatically retrieved by Laravel.
        return response()->json($user, 200);
    }

    /**
     * Show the form for editing the specified user.
     * Note: Typically not implemented for API backends as form would be handled by the frontend.
     */
    public function edit(string $id)
    {
        // Method intentionally left empty.
    }

    /**
     * Update the specified user in the database.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the new user data.
     * @param  string $id The ID of the user to update.
     * @return \Illuminate\Http\JsonResponse Returns the updated user as JSON with HTTP status 200.
     */
    public function update(Request $request, string $id)
    {
        // Validate the incoming request data.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id, // Ensure email is unique excluding the current user.
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,admin',
        ]);

        // Find the user by ID, update its properties, and save it.
        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password); // Re-hash the updated password.
        $user->role = $request->role;
        $user->save();

        // Return the updated user data.
        return response()->json($user, 200);
    }

    /**
     * Remove the specified user from the database.
     *
     * @param string $id The ID of the user to delete.
     * @return \Illuminate\Http\JsonResponse Returns a 204 HTTP status to indicate that the deletion was successful without any content.
     */
    public function destroy(string $id)
    {
        // Find the user and delete it.
        $user = User::findOrFail($id);
        $user->delete();

        // Return a 204 No Content status to indicate successful deletion.
        return response()->json(null, 204);
    }
}
