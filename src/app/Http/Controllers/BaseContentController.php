<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Services\FileUploadService;

abstract class BaseContentController extends Controller
{
    protected string $modelClass;
    protected array $searchableFields = [];
    protected string $type = 'contenido';
    

     /** 🟢 Obtener contenidos (públicas o todas si es admin) */
    public function index(Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $query = ($this->modelClass)::with(['author:id,name,username,email,role,status'])->orderBy('created_at', 'desc');

        if (!$auth) {
            $query->where('status', 'publico');
        } elseif (!$isAdmin) {
            $query->where(function ($q) use ($authId) {
                $q->where('status', 'publico')->orWhere('author_id', $authId);
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%$search%");
                }
        
                // Buscar en campos JSON si existen
                if (property_exists($this, 'jsonSearchableFields')) {
                    foreach ($this->jsonSearchableFields as $jsonField) {
                        $q->orWhereJsonContains($jsonField, $search);
                    }
                }
            });
        }

        $results = $query->paginate(20)->appends($request->query());

        return ApiResponse::success("Lista de {$this->type}s obtenida", $results->items(), [
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
            'last_page' => $results->lastPage(),
        ]);
    }

    /** 🟢 Obtener contenidos propios (públicos y privados) */
    public function own(Request $request)
    {
        $auth = $request->user();
        if (!$auth) return ApiResponse::unauthenticated();

        $query = ($this->modelClass)::with(['author:id,name,username,email,role,status'])
            ->where('author_id', $auth->id)
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%$search%");
                }
            });
        }

        $results = $query->paginate(20)->appends($request->query());

        return ApiResponse::success("Lista de {$this->type}s propias obtenida", $results->items(), [
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total(),
            'last_page' => $results->lastPage(),
        ]);
    }

    /** 🔵 Ver un registro por ID */
    public function show($id, Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $item = ($this->modelClass)::with(['author:id,name,username,email,role,status'])->find($id);
        if (!$item) return ApiResponse::notFound("{$this->type} no encontrado");

        if ($item->status === 'archivado' && !$isAdmin && $item->author_id !== $authId) {
            return ApiResponse::unauthorized("No autorizado para ver este {$this->type}");
        }

        return ApiResponse::success("Detalle de {$this->type} obtenido", $item);
    }

    /** 🟡 Crear registro es en controladores hijos */
    /** 🟠 Actualizar registro es en controladores hijos */

    /** 🟡 Actualizar imagen de portada */
    public function uploadCoverImage($id, Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,webp|max:5120']);
        $item = ($this->modelClass)::find($id);
        if (!$item) return ApiResponse::notFound("{$this->type} no encontrado");

        $auth = $request->user();
        if ($item->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized("No autorizado");
        }

        $filename = FileUploadService::uploadImage($request->file('image'), 'covers', $item->cover_image);
        $item->cover_image = $filename;
        $item->save();

        return ApiResponse::success("Imagen de portada actualizada", $item->only([
            'id', 'author_id', 'title', 'description', 'cover_image', 'file_path'
        ]));
    }

    /** 🟡 Actualizar archivo */
    public function uploadFile($id, Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:pdf,docx,xlsx|max:20480']);
        $item = ($this->modelClass)::find($id);
        if (!$item) return ApiResponse::notFound("{$this->type} no encontrado");

        $auth = $request->user();
        if ($item->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized("No autorizado");
        }

        $filename = FileUploadService::uploadFile($request->file('file'), 'docs', $item->file_path);
        $item->file_path = $filename;
        $item->save();

        return ApiResponse::success("Archivo actualizado", $item->only([
            'id', 'author_id', 'title', 'description', 'cover_image', 'file_path'
        ]));
    }

    /** 🟠 Alternar visibilidad */
    public function toggleStatus($id, Request $request)
    {
        $item = ($this->modelClass)::find($id);
        if (!$item) return ApiResponse::notFound("{$this->type} no encontrado");

        $auth = $request->user();
        if ($item->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized("No autorizado");
        }

        $item->status = $item->status === 'publico' ? 'archivado' : 'publico';
        $item->save();

        return ApiResponse::success("Estado de {$this->type} actualizado", $item);
    }

    /** 🔴 Eliminar contenido permanentemente */
    public function destroy($id, Request $request)
    {
        $item = ($this->modelClass)::find($id);
        if (!$item) return ApiResponse::notFound("{$this->type} no encontrado");

        $auth = $request->user();
        if ($item->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized("No autorizado");
        }

        FileUploadService::delete($item->cover_image);
        FileUploadService::delete($item->file_path);
        $item->delete();

        return ApiResponse::success("{$this->type} eliminado correctamente");
    }
}
