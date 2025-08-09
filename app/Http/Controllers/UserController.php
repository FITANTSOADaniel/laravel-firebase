<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use App\Models\Notification;
use App\Http\Controllers\SendEmailController;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;


class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // public function searchUser(Request $request)
    // {
    //     try {
    //         $param = $request->input('key');
    //         $offset = $request->input('offset');
    //         $limit = $request->input('limit');
    //         $users = User::with('level')->with("association");

    //             if ($param) {
    //                 $users->where(function ($query) use ($param) {
    //                     $query->where('first_name', 'like', "%$param%")
    //                     ->orWhere('email', 'like', "%$param%")
    //                     ->orWhere('last_name', 'like', "%$param%");;
    //                 })->orWhereHas('association', function ($query) use ($param) {
    //                     $query->where('name', 'like', "%$param%");
    //                 });

    //             }
    //         $users->where('is_admin', 0);
    //         $users->whereIn('status', [0,1]);
    //         $users->orderBy('id', 'desc');
    //         $userCount = $users->count();
    //         $users = $users->skip($offset)->take($limit)->get();
    //         return response()->json(['status' => 'success', 'users' => $users, 'userCount' => $userCount]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    public function searchUser(Request $request)
    {
        try {
            $param = $request->input('key');
            $offset = (int) $request->input('offset', 0);
            $limit = (int) $request->input('limit', 10);

            // Récupérer tous les utilisateurs depuis Firebase
            $database = app('firebase.database');
            $reference = $database->getReference('users');
            $snapshot = $reference->getValue();

            $users = collect($snapshot ?? [])->map(function ($user, $uid) {
                $user['uid'] = $uid; // pour garder l’identifiant utilisateur
                return $user;
            });

            // 🔍 Filtrage par mot-clé (nom, prénom, email, association)
            if ($param) {
                $users = $users->filter(function ($user) use ($param) {
                    return Str::contains(Str::lower($user['name'] ?? ''), Str::lower($param)) ||
                        Str::contains(Str::lower($user['last_name'] ?? ''), Str::lower($param)) ||
                        Str::contains(Str::lower($user['email'] ?? ''), Str::lower($param)) ||
                        Str::contains(Str::lower($user['association'] ?? ''), Str::lower($param));
                });
            }

            // 🎯 Filtrage is_admin = false et status = 0 ou 1
            $users = $users->filter(function ($user) {
                return isset($user['is_admin']) && $user['is_admin'] == false &&
                    isset($user['status']) && in_array($user['status'], [0, 1]);
            });

            // 📊 Nombre total avant pagination
            $userCount = $users->count();

            // ⏳ Pagination manuelle
            $users = $users->sortByDesc('createdAt')
                        ->slice($offset, $limit)
                        ->values(); // réindexer

            return response()->json([
                'status' => 'success',
                'users' => $users,
                'userCount' => $userCount
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchOrder(Request $request)
    {
        try {
            $param = $request->input('key');
            $offset = (int) $request->input('offset', 0);
            $limit = (int) $request->input('limit', 10);

            // Récupérer tous les utilisateurs depuis Firebase
            $database = app('firebase.database');
            $reference = $database->getReference('users');
            $snapshot = $reference->getValue();

            $users = collect($snapshot ?? [])->map(function ($user, $uid) {
                $user['uid'] = $uid; // pour garder l’identifiant utilisateur
                return $user;
            });

            // 🔍 Filtrage par mot-clé (nom, prénom, email, association)
            if ($param) {
                $users = $users->filter(function ($user) use ($param) {
                    return Str::contains(Str::lower($user['name'] ?? ''), Str::lower($param)) ||
                        Str::contains(Str::lower($user['last_name'] ?? ''), Str::lower($param)) ||
                        Str::contains(Str::lower($user['email'] ?? ''), Str::lower($param)) ||
                        Str::contains(Str::lower($user['association'] ?? ''), Str::lower($param));
                });
            }

            // 🎯 Filtrage is_admin = false et status = 0 ou 1
            $users = $users->filter(function ($user) {
                return isset($user['is_admin']) && $user['is_admin'] == false &&
                    isset($user['status']) && in_array($user['status'], [2]);
            });

            // 📊 Nombre total avant pagination
            $userCount = $users->count();

            // ⏳ Pagination manuelle
            $users = $users->sortByDesc('createdAt')
                        ->slice($offset, $limit)
                        ->values(); // réindexer

            return response()->json([
                'status' => 'success',
                'users' => $users,
                'userCount' => $userCount
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDetailsClient($id)
    {
        try {
            $database = app('firebase.database');

            $reference = $database->getReference('users/' . $id);
            $client = $reference->getValue();

            if (!$client) {
                return response()->json(['status' => 'error', 'message' => 'Client non trouvé'], 404);
            }

            return response()->json(['status' => 'success', 'client' => $client]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function acceptUsers(Request $request)
        {
            try {
                $userIds = $request->input('userIds');
                $database = app('firebase.database');

                foreach ($userIds as $id) {
                $database->getReference("users/{$id}")
                        ->update(['status' => 1]);
            }

                return response()->json(['success' => "Utilisateurs acceptés avec succès"], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

    public function destroy($id, FirebaseService $firebaseService){
        try {
            $firebaseService->deleteUser("users/{$id}");

            return response()->json(['success' => "Utilisateurs supprimer avec succès"], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateInfo(Request $request, FirebaseService $firebaseService)
    {
        try {
            $data = $request->all();
            $id = $request->id;

            $firebaseService->update("users/{$id}", $data);

            return response()->json(['status' => 'success', 'message' => 'Utilisateurs mise à jour avec succès']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // public function destroy($id)
    // {
    //     try{
    //         $user = User::findOrFail($id);
    //         $user->delete();
    //         return response()->json(['status' => 'success', 'message' => 'Utilisateur supprimé avec succès']);
    //     }
    //     catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }

    // }

}
