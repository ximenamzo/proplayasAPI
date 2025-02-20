<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomePageContent;

class HomepageContentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * 🟢 Listar todas las secciones del homepage.
     */
    public function index()
    {
        return response()->json(HomepageContent::all());
    }

    /**
     * 🔵 Obtener una sección específica del homepage.
     */
    public function show($id)
    {
        $section = HomepageContent::find($id);

        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        return response()->json($section);
    }

    /**
     * 🟡 Crear una nueva sección en el homepage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'section_name' => 'required|string|max:255|unique:homepage_content',
            'content' => 'required|string',
        ]);

        $section = HomepageContent::create($request->all());

        return response()->json($section, 201);
    }

    /**
     * 🟠 Actualizar una sección del homepage.
     */
    public function update(Request $request, $id)
    {
        $section = HomepageContent::find($id);

        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $request->validate([
            'section_name' => 'string|max:255|unique:homepage_content,section_name,' . $id,
            'content' => 'string',
        ]);

        $section->update($request->all());

        return response()->json($section);
    }

    /**
     * 🔴 Eliminar una sección del homepage.
     */
    public function destroy($id)
    {
        $section = HomepageContent::find($id);

        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Sección eliminada']);
    }
}
