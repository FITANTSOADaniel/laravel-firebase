<?php

namespace App\Services;
use Illuminate\Http\Request;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Contract\Auth;

class FirebaseService
{
    protected Database $database;
    protected $auth;

    public function __construct(Auth $auth)
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase/credentials.json'))->withDatabaseUri('https://react-crud-1aa96-default-rtdb.firebaseio.com');

        $this->database = $factory->createDatabase();
        $this->auth = $auth;
    }

    public function set(string $path, array $data)
    {
        return $this->database->getReference($path)->set($data);
    }

    public function push(string $path, array $data)
    {
        return $this->database->getReference($path)->push($data);
    }

    public function get(string $path)
    {
        return $this->database->getReference($path)->getValue();
    }

    public function delete(string $path)
    {
        return $this->database->getReference($path)->remove();
    }

    public function update(string $path, array $data): void
    {
        $this->database->getReference($path)->update($data);
    }

    public function register(Request $request)
    {
       $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]);
        $email = $request->input('email');
        $password = $request->input('password');
        $createdUser = $this->auth->createUserWithEmailAndPassword($email, $password);
        return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $createdUser,
            ]);
    }

    public function pushWithId(string $path, array $data)
    {
        $reference = $this->database->getReference($path)->push(); // génère une clé unique
        $id = $reference->getKey();

        $data['id'] = $id; // ajoute l'id généré dans les données
        $reference->set($data); // enregistre les données dans Firebase

        return $data; // retourne les données insérées
    }
    public function deleteUser(string $path)
    {
        // Extraire l'uid depuis le path, ex: "users/UID"
        $segments = explode('/', $path);
        $uid = end($segments);

        if (empty($uid)) {
            throw new \Exception('UID manquant dans le chemin');
        }

        // 1. Supprimer dans Firebase Auth
        try {
            $this->auth->deleteUser($uid);
        } catch (UserNotFound $e) {
            // On peut ignorer si déjà supprimé de Auth
        }

        // 2. Supprimer dans Realtime Database
        $this->database->getReference($path)->remove();

        return true;
    }

    public function getDatabase()
    {
        return $this->database;
    }
}
