<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use  App\User;


class AuthController extends BaseController
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Create new user.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function register(Request $request)
    {
        
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);
         
        try {

            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);

            $user->save();

            
            return response()->json(['status' => 200, 
                                     'user' => $user, 
                                     'message' => 'CREATED']);

        } catch (\Exception $e) {
            
            return response()->json(['status' => 409,
                                    'message' => 'User Registration Failed!']);
        }

    }


    /**
     * Get the token authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
           
        $this->validate($request, [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
            
        $credentials = $request->only(['email', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            
            return response()->json(['status' => 401, 
                                     'message' => 'Unauthorized']);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['status' => 200,
                                 'message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}