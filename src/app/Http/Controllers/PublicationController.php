<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PublicationController extends Controller
{
    /** 🟢 Obtener publicaciones (públicas o todas si es admin) */
    public function index(Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $query = Publication::with(['author:id,name,username,email,role,status'])
            ->orderBy('created_at', 'desc');

        // Filtro por visibilidad
        if (!$auth) {
            $query->where('status', 'publico');
        } elseif (!$isAdmin) {
            $query->where(function ($q) use ($authId) {
                $q->where('status', 'publico')->orWhere('author_id', $authId);
            });
        }

        // Filtro por búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%");
            });
        }

        // Paginación
        $perPage = 20;
        $publications = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::success('Lista de publicaciones obtenida', $publications->items(), [
            'current_page' => $publications->currentPage(),
            'per_page' => $publications->perPage(),
            'total' => $publications->total(),
            'last_page' => $publications->lastPage(),
        ]);
    }

    /** 🟢 Obtener publicaciones propias (públicas y privadas) */
    public function ownPublications(Request $request)
    {
        $auth = $request->user();

        if (!$auth) {
            return ApiResponse::unauthenticated();
        }

        $query = Publication::with(['author:id,name,username,email,role,status'])
            ->where('author_id', $auth->id)
            ->orderBy('created_at', 'desc');

        // Filtro por búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%");
            });
        }

        // Paginación
        $perPage = 20;
        $publications = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::success('Lista de publicaciones propias obtenida', $publications->items(), [
            'current_page' => $publications->currentPage(),
            'per_page' => $publications->perPage(),
            'total' => $publications->total(),
            'last_page' => $publications->lastPage(),
        ]);
    }


    /** 🔵 Ver una publicación */
    public function show($id, Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $pub = Publication::with(['author:id,name,username,email,role,status'])->find($id);

        if (!$pub) {
            return ApiResponse::notFound('Publicación no encontrada');
        }

        if ($pub->status === 'archivado' && !$isAdmin && $pub->author_id !== $authId) {
            return ApiResponse::unauthorized('No autorizado para ver esta publicación');
        }

        return ApiResponse::success('Detalle de publicación obtenido', $pub);
    }

    /** 🟡 Crear publicación */
    public function store(Request $request)
    {        
        try {
            $request->validate([
                'type' => 'required|in:boletin,guia,articulo',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'link' => 'nullable|url',
                'doi' => 'nullable|string',
                'issn' => 'nullable|string',
                'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'cover_image_url' => 'nullable|url',
                'file_file' => 'nullable|file|mimes:pdf,docx,xlsx|max:20480',
                'file_url' => 'nullable|url'
            ]);
    
            $coverPath = null;
            $filePath = null;
    
            if ($request->hasFile('cover_image_file')) {
                $coverPath = FileUploadService::uploadImage($request->file('cover_image_file'), 'covers');
            } elseif ($request->filled('cover_image_url')) {
                $coverPath = $request->input('cover_image_url');
            }
        
            if ($request->hasFile('file_file')) {
                $filePath = FileUploadService::uploadFile($request->file('file_file'), 'docs');
            } elseif ($request->filled('file_url')) {
                $filePath = $request->input('file_url');
            }
    
            $publication = Publication::create([
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'link' => $request->link,
                'doi' => $request->doi,
                'issn' => $request->issn,
                'file_path' => $filePath,
                'cover_image' => $coverPath,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]); 
    
            return ApiResponse::created('Publicación creada correctamente', $publication);
            
        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, [
                'errors' => $e->errors()
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error inesperado al crear publicación: ' . $e->getMessage());
            return ApiResponse::error('Error inesperado al guardar publicación', 500, [
                'debug' => $e->getMessage(),
            ]);
        }
    }

    /** 🟠 Editar publicación */
    public function update($id, Request $request)
    {
        $pub = Publication::find($id);

        if (!$pub) return ApiResponse::notFound('Publicación no encontrada');

        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id;

        if ($pub->author_id !== $authId && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'string|max:255|nullable',
            'description' => 'string|nullable',
            'link' => 'nullable|url',
            'doi' => 'nullable|string',
            'issn' => 'nullable|string'
        ]);

        $pub->update($request->only(['title', 'description', 'link', 'doi', 'issn']));

        return ApiResponse::success('Publicación actualizada correctamente', $pub);
    }

    /** 🟡 Subir imagen de portada de una publicación */
    public function uploadCoverImage($id, Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,webp|max:5120'
            ]);

            $authUser = $request->user();
            $pub = Publication::find($id);
            
            if (!$pub) return ApiResponse::notFound('Publicación no encontrada', 404);

            $isOwner = $authUser->id === $pub->author_id;
            $isAdmin = $authUser->role === 'admin';

            if (!$isOwner && !$isAdmin) {
                return ApiResponse::unauthorized('No autorizado para editar esta publicación', 403);
            }

            $oldFilename = $pub->cover_image; // solo el nombre del archivo
            $filename = FileUploadService::uploadImage($request->file('image'), 'covers', $oldFilename);

            $pub->cover_image = $filename;
            $pub->save();

            return ApiResponse::success('Imagen de portada subida correctamente', $pub->only([
                'id', 'author_id', 'title', 'description', 'cover_image', 'file_path'
            ]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al subir imagen de portada:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al subir imagen', 500, [
                'debug' => $e->getMessage()
            ]);
        }
    }

    /** 🟡 Subir archivo de una publicación */
    public function uploadFile($id, Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,docx,xlsx|max:20480'
            ]);

            $authUser = $request->user();
            $pub = Publication::find($id);

            if (!$pub) return ApiResponse::notFound('Publicación no encontrada', 404);

            $isOwner = $authUser->id === $pub->author_id;
            $isAdmin = $authUser->role === 'admin';

            if (!$isOwner && !$isAdmin) {
                return ApiResponse::unauthorized('No autorizado para editar esta publicación', 403);
            }

            $oldFilename = $pub->file_path; // solo el nombre del archivo
            $filename = FileUploadService::uploadFile($request->file('file'), 'docs', $oldFilename);

            $pub->file_path = $filename;
            $pub->save();

            return ApiResponse::success('Archivo subido correctamente', $pub->only([
                'id', 'author_id', 'title', 'description', 'cover_image', 'file_path'
            ]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al subir archivo adjunto:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al subir archivo', 500, [
                'debug' => $e->getMessage()
            ]);
        }
    }


    /** 🟠 Alternar visibilidad */
    public function toggleStatus($id, Request $request)
    {
        $pub = Publication::find($id);

        if (!$pub) return ApiResponse::notFound('Publicación no encontrada');

        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id;

        if ($pub->author_id !== $authId && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $pub->status = $pub->status === 'publico' ? 'archivado' : 'publico';
        $pub->save();

        return ApiResponse::success('Estado de publicación actualizado correctamente', $pub);
    }

    /** 🔴 Eliminar publicación permanentemente */
    public function destroy($id, Request $request)
    {
        $pub = Publication::find($id);

        if (!$pub) return ApiResponse::notFound('Publicación no encontrada');

        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id;

        if ($pub->author_id !== $authId && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        // Eliminar archivos SOLO si existen
        if ($pub->cover_image) {
            FileUploadService::delete($pub->cover_image);
        }
        
        if ($pub->file_path) {
            FileUploadService::delete($pub->file_path);
        }

        $pub->delete();

        return ApiResponse::success('Publicación eliminada permanentemente');
    }
}
