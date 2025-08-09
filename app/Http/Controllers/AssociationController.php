<?php

namespace App\Http\Controllers;
use App\Services\FirebaseService;

use App\Models\Association;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class AssociationController extends Controller
{

    public function findAll(FirebaseService $firebaseService)
    {
        try {
            $associations = $firebaseService->get('associations');

            return response()->json(['status' => 'success', 'associations' => $associations]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function findById($id, FirebaseService $firebaseService)
    {
        try {
            $association = $firebaseService->get("associations/{$id}");

            if (!$association) {
                return response()->json(['error' => 'Association non trouvée'], 404);
            }

            return response()->json(['status' => 'success', 'associations' => $association]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createAssociation(Request $request, FirebaseService $firebaseService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'logo' => 'required|string',
        ]);

        try {
            $data = $firebaseService->pushWithId('associations', $validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Association créée avec succès',
                'association' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    public function findAssociation(Request $request, FirebaseService $firebaseService)
    {
        try {
            $param = strtolower($request->input('key'));
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $data = $firebaseService->get('associations');

            // Filtrage manuel (recherche par nom)
            $filtered = collect($data)->filter(function ($item) use ($param) {
                return isset($item['name']) && str_contains(strtolower($item['name']), $param);
            })->values();

            $total = $filtered->count();
            $paginated = $filtered->slice($offset, $limit)->values();

            return response()->json([
                'status' => 'success',
                'associations' => $paginated,
                'associationCount' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function upload(Request $request)
    {
        $paths = [];
        if ($request->hasFile('fichier')) {
            foreach ($request->file('fichier') as $file) {
                $path = $file->store('documents', 'public');
                $paths[] = $path;
            }

            return response()->json([
                'message' => 'success',
                'paths' => $paths,
            ]);
        } else {
            return response()->json([
                'message' => 'error',
                'error' => 'No files provided',
            ], 400);
        }
    }
    public function store(Request $request, FirebaseService $firebaseService)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'logo' => 'required|string',
        ]);
        $association = new Association();
        $association->name = $request->input('name');
        $association->desc = $request->input('desc');
        $association->logo = $request->input('logo');

        $firebaseService->push('associations', $association->toArray());

        return response()->json([
            'message' => 'Association enregistrée avec succès',
        ], 201);
    }
    
    public function update(Request $request, FirebaseService $firebaseService)
    {
        try {
            $data = $request->only(['name', 'desc', 'logo']);
            $id = $request->id;

            $firebaseService->update("associations/{$id}", $data);

            return response()->json(['status' => 'success', 'message' => 'Association mise à jour avec succès']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id, FirebaseService $firebaseService)
    {
        try {
            $firebaseService->delete("associations/{$id}");

            return response()->json(['status' => 'success', 'message' => 'Association supprimée avec succès']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
