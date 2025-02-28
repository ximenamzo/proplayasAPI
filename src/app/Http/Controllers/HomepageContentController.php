<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomePageContent;

class HomepageContentController extends Controller
{
    public function __construct()
    {
        // Solo los admins pueden modificar el contenido
        //$this->middleware(['auth:sanctum', 'role:admin'])->except(['index', 'show']);
        $this->middleware(['jwt.auth'])->except(['index', 'show']);
    }

    /**
     * 🟢 Listar todas las secciones del homepage (PÚBLICO)
     */
    public function index()
    {
        return response()->json([
            'status' => 200,
            'message' => 'Contenido del homepage obtenido',
            'data' => HomePageContent::all()
        ], 200);
    }

    /**
     * 🔵 Obtener una sección específica del homepage (PÚBLICO)
     */
    public function show($id)
    {
        $section = HomepageContent::find($id);

        return $section
        ? response()->json([
            'status' => 200, 
            'data' => $section
        ], 200)
        : response()->json([
            'status' => 404, 
            'error' => 'Sección no encontrada'
        ], 404);
}

    /**
     * 🟡 Crear una nueva sección en el homepage. ADMIN
     */
    public function store(Request $request)
    {
        if ($request->user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'section_name' => 'required|string|max:255|unique:homepage_content',
            'content' => 'required|string',
        ]);

        $section = HomePageContent::create($validated);

        return response()->json([
            'status' => 201, 
            'message' => 'Sección creada', 
            'data' => $section
        ], 201);
    }

    /**
     * 🟠 Actualizar una sección del homepage. ADMIN
     */
    public function update(Request $request, $id)
    {
        $section = HomePageContent::find($id);
    
        if (!$section) {
            return response()->json([
                'status' => 404, 
                'error' => 'Sección no encontrada'
            ], 404);
        }

        if ($request->user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }
    
        $validated = $request->validate([
            'section_name' => 'string|max:255|unique:homepage_content,section_name,' . $id,
            'content' => 'required|string',
        ]);

        $section->update($validated);
    
        return response()->json([
            'status' => 200,
            'message' => 'Sección actualizada correctamente',
            'data' => $section
        ], 200);
    }

    /**
     * 🔴 Eliminar una sección del homepage. ADMIN
     */
    public function destroy(Request $request, $id)
    {
        $section = HomePageContent::find($id);

        if (!$section) {
            return response()->json([
                'status' => 404, 
                'error' => 'Sección no encontrada'
            ], 404);
        }

        if ($request->user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $section->delete();

        return response()->json([
            'status' => 200, 
            'message' => 'Sección eliminada'
        ], 200);
    }
}
