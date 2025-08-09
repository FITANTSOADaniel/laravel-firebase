<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use App\Services\FirebaseService;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class FirebaseAuthController extends Controller
{
    protected $auth;
    
    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }
    
    // public function signUp(Request $request)
    // {
        
    //     $userProperties = [
    //         'email' => $request->input('email'),
    //         'emailVerified' => false,
    //         'password' => $request->input('password'),
    //         'displayName' => $request->input('name'),
    //         'disabled' => false,
    //     ];
        
    //     $createdUser = $this->auth->createUser($userProperties);

    //     $database = app('firebase.database');
    //     $userData = [
    //         'name' => $request->input('name'),
    //         'email' => $request->input('email'),
    //         'is_admin'=> false,
    //         'status'=> 1,
    //         'last_name'=> $request->input('last_name'),
    //         'level'=> "débutant",
    //         'association'=> $request->input('association'),
    //         'profil'=> $request->input('profil'),
    //         'createdAt' => time(),
    //         'updatedAt' => time(),
    //     ];

    //     $database->getReference('users/' . $createdUser->uid)
    //              ->set($userData);

    //      return response()->json([
    //         'status' => 'success',
    //         'message' => 'ajouter avec succès',
    //     ]);
    // }

    public function signUp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'association' => 'nullable|string',
            'profil' => 'nullable|string',
        ]);

        // Récupération sécurisée des champs
        $email = $request->input('email');
        $password = $request->input('password');
        $name = $request->input('first_name'); // displayName
        $lastName = $request->input('last_name');
        $association = $request->input('association_id');
        $profil = $request->input('profil');

        // Construction du tableau sans valeurs nulles
        $userProperties = [
            'email' => $email,
            'emailVerified' => false,
            'password' => $password,
            'disabled' => false,
        ];

        // Ajouter displayName seulement s’il est défini
        if (!empty($name)) {
            $userProperties['displayName'] = $name;
        }

        // Création de l'utilisateur Firebase Auth
        $createdUser = $this->auth->createUser($userProperties);

        // Enregistrement des infos utilisateur dans la base Realtime Database
        $database = app('firebase.database');
        $userData = [
            'id' => $createdUser->uid,
            'name' => $name,
            'email' => $email,
            'is_admin' => false,
            'status' => 1,
            'last_name' => $lastName,
            'level' => 'débutant',
            'association' => $association,
            'profil' => $profil,
            'createdAt' => time(),
            'updatedAt' => time(),
        ];

        // Sauvegarde dans la base
        $database->getReference('users/' . $createdUser->uid)
                ->set($userData);

        return response()->json([
            'status' => 'success',
            'message' => 'Ajouté avec succès',
        ]);
    }


    public function login(Request $request)
        {
            $credentials = $request->only('email', 'password');
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6'
            ]);

            try {
                $signInResult = $this->auth->signInWithEmailAndPassword(
                    $validated['email'],
                    $validated['password']
                );
                
                $user = $this->auth->getUser($signInResult->firebaseUserId());
                $data= [
                    'first_name' => 'Daniel',
                    'last_name' => 'Fitantsoa',
                    'is_admin' => 1,
                ];

                $generatedToken = Auth::claims($data)->attempt($credentials);

                return response()->json([
                    'status' => 'success',
                    'user' => $user,
                    'authorisation' => [
                        'token' => $generatedToken,
                    ]
                ]);

            } catch (UserNotFound $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            } catch (InvalidPassword $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mot de passe incorrect'
                ], 401);
            } catch (AuthException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur d\'authentification'
                ], 500);
            }
        }
    public function login_client(Request $request,FirebaseService $firebaseService)
        {
            $credentials = $request->only('email', 'password');
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6'
            ]);

            try {
                $signInResult = $this->auth->signInWithEmailAndPassword(
                    $validated['email'],
                    $validated['password']
                );
                
                $user = $this->auth->getUser($signInResult->firebaseUserId());
                $data= [
                    'first_name' => 'Daniel',
                    'last_name' => 'Fitantsoa',
                    'is_admin' => 1,
                ];

                $generatedToken = Auth::claims($data)->attempt($credentials);

                return response()->json([
                    'status' => 'success',
                    'user' => $user,
                    'authorisation' => [
                        'token' => $generatedToken,
                    ]
                ]);

            } catch (UserNotFound $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            } catch (InvalidPassword $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mot de passe incorrect'
                ], 401);
            } catch (AuthException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur d\'authentification'
                ], 500);
            }
        }
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