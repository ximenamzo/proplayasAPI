<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomePageContent;
use App\Helpers\ApiResponse;

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
        return ApiResponse::success('Contenido del homepage obtenido', HomePageContent::all());
    }

    /**
     * 🔵 Obtener una sección específica del homepage (PÚBLICO)
     */
    public function show($id)
    {
        $section = HomepageContent::find($id);

        return $section
            ? ApiResponse::success('Sección encontrada', $section)
            : ApiResponse::notFound('Sección no encontrada');
}

    /**
     * 🟡 Crear una nueva sección en el homepage. ADMIN
     */
    public function store(Request $request)
    {
        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $validated = $request->validate([
            'section_name' => 'required|string|max:255|unique:homepage_content',
            'content' => 'required|string',
        ]);

        $section = HomePageContent::create($validated);

        return ApiResponse::created('Sección creada correctamente', $section);
    }

    /**
     * 🟠 Actualizar una sección del homepage. ADMIN
     */
    public function update(Request $request, $id)
    {
        $section = HomePageContent::find($id);
    
        if (!$section) {
            return ApiResponse::notFound('Sección no encontrada', 404);
        }

        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }
    
        $validated = $request->validate([
            'section_name' => 'string|max:255|unique:homepage_content,section_name,' . $id,
            'content' => 'required|string',
        ]);

        $section->update($validated);
    
        return ApiResponse::success('Sección actualizada correctamente', $section);
    }

    /**
     * 🔴 Eliminar una sección del homepage. ADMIN
     */
    public function destroy(Request $request, $id)
    {
        $section = HomePageContent::find($id);

        if (!$section) {
            return ApiResponse::notFound('Sección no encontrada', 404);
        }

        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $section->delete();

        return ApiResponse::success('Sección eliminada correctamente', $section);
    }
}
