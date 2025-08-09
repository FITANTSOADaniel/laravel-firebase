<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Kreait\Laravel\Firebase\Facades\Firebase;

class AuthController extends Controller
{
    protected $firebaseService;
    protected $firebaseAuth;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->firebaseAuth = Firebase::auth();
    }
    // public function register(Request $request)
    // {
    //     try {
    //         // 1. Create user in Firebase Authentication
    //         $userProperties = [
    //             'email' => $request->email,
    //             'password' => $request->password,
    //         ];

    //         $createdUser = $this->auth->createUser($userProperties);
    //         $uid = $createdUser->uid;

    //         // 2. Add user to Realtime Database under "users/UID"
    //     $this->database
    //             ->getReference('users/' . $uid)
    //             ->set([
    //                 'email' => $request->email,
    //                 'created_at' => now()->toDateTimeString(),
    //             ]);

    //         return response()->json(['message' => 'User created and stored in Realtime Database']);

    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    public function register(Request $request){
        $this->firebaseService->register($request);
    }
    public function register1(Request $request){
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]);
        $email = $request->input('email');
        $password = $request->input('password');
        $createdUser = $this->firebaseAuth->createUserWithEmailAndPassword($email, $password);
        return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $createdUser,
            ]);
    }

    public function login_Admin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mot de passe ou email invalide',
            ], 401);
        }

        $user = Auth::user();
        if (!$user->status){
            return response()->json([
                'status' => 'error',
                'message' => 'Compte désactivée',
            ], 401);
        }
        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mot de passe ou email invalide',
            ], 401);
        }
        $data= [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_admin' => $user->is_admin,
        ];
        $generateToken = Auth::claims($data)->attempt($credentials);
        return response()->json([
                'status' => 'success',
                'user' => $user,
                'authorisation' => [
                    'token' => $generateToken,
                    'type' => 'bearer',
                ]
            ]);
    }
    public function login_Client(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mot de passe ou email invalide',
            ], 401);
        }

        $user = Auth::user();

        $data= [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_admin' => $user->is_admin,
            'id_assoc' => $user->association_id,
            'id_level' => $user->level_id,
        ];
        $generateToken = Auth::claims($data)->attempt($credentials);
        return response()->json([
                'status' => 'success',
                'user' => $user,
                'authorisation' => [
                    'token' => $generateToken,
                    'type' => 'bearer',
                ]
            ]);
    }
    // public function register(Request $request)
    // {
    //     try {

    //             $request->validate([
    //                 'email' => 'required|string|email|max:255|unique:users',
    //                 'password' => 'required|string|min:6',
    //                 'first_name' => 'required|string|min:3',
    //                 'last_name' => 'required|string|max:255'
    //             ], [
    //                 'email.required' => 'Le champ email est requis.',
    //                 'email.email' => 'L\'email doit être une adresse email valide.',
    //                 'email.unique' => 'L\'adresse email est déjà utilisée.',
    //                 'password.required' => 'Le champ mot de passe est requis.',
    //                 'password.min' => 'Le mot de passe doit avoir au moins :min caractères.',
    //                 'first_name.required' => 'Le champ prénom est requis.',
    //                 'first_name.min' => 'Le nom doit contenir au moins :min caractères.',
    //                 'last_name.required' => 'Le champ nom est requis.',
    //                 'last_name.min' => 'Le prénom doit avoir au moins :min caractères.'
    //             ]);

    //         $user = User::create([
    //             'is_admin' => 1,
    //             'email' => $request->email,
    //             'password' => Hash::make($request->password),
    //             'first_name' => $request->first_name,
    //             'last_name' => $request->last_name,
    //             'status' => 1,
    //             'profil'=> $request->profil,
    //             'level_id' => $request->level_id,
    //             'association_id' => $request->association_id,
    //         ]);

    //         $token = Auth::login($user);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'User created successfully',
    //             'user' => $user,
    //             'authorisation' => [
    //                 'token' => $token,
    //                 'type' => 'bearer',
    //             ]
    //         ]);

    //       } catch (\Illuminate\Validation\ValidationException $exception) {
    //      $firstError = $exception->validator->getMessageBag()->first();
    //         return response()->json(['error' => $firstError], 422);
    //      } catch (\Exception $e) {
    //          return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    public function logout()
    {
        try{
            Auth::logout();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
}



// use Kreait\Firebase\Auth;

// class AuthController extends Controller
// {
//     protected $auth;

//     public function __construct(Auth $auth)
//     {
//         $this->auth = $auth;
//     }

//     public function register(Request $request)
//     {
//         $userProperties = [
//             'email' => $request->email,
//             'password' => $request->password,
//         ];

//         $this->auth->createUser($userProperties);
//         return response()->json(['message' => 'User created successfully']);
//     }
// }

// https://medium.com/@mayurkoshti12/kreait-laravel-firebase-e22939c3a4d2#5bdb



// import * as React from 'react';
// import CircularProgress from '@mui/material/CircularProgress';
// import Box from '@mui/material/Box';

// export default function CircularIndeterminate() {
//   return (
//     <Box sx={{ display: 'flex' }}>
//       <CircularProgress />
//     </Box>
//   );
// }




// use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;
// use Kreait\Firebase\Auth as FirebaseAuth;
// use Kreait\Firebase\Database;

// class AuthController extends Controller
// {
//     protected $auth;
//     protected $database;

//     public function __construct(FirebaseAuth $auth, Database $database)
//     {
//         $this->auth = $auth;
//         $this->database = $database;
//     }

//     public function register(Request $request)
//     {
//         $userProperties = [
//             'email' => $request->email,
//             'password' => $request->password,
//         ];

//         $createdUser = $this->auth->createUser($userProperties);
//         $uid = $createdUser->uid;

//         $this->database
//             ->getReference('users/' . $uid)
//             ->set([
//                 'email' => $request->email,
//                 'created_at' => now()->toDateTimeString(),
//             ]);

//         return response()->json(['message' => 'User created and saved in Realtime Database']);
//     }
// }
