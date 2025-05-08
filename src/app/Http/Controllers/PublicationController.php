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
    /** 🟢 Obtener publicaciones públicas o propias */
    public function index(Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $query = Publication::with(['author:id,name,username,email,role,status'])
            ->orderBy('created_at', 'desc');

        if (!$auth) {
            $query->where('status', 'publico');
        } elseif (!$isAdmin) {
            $query->where(fn($q) => 
                $q->where('status', 'publico')
                  ->orWhere('author_id', $authId
                ));
        }

        return ApiResponse::success('Lista de publicaciones obtenida', $query->get());
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
        $request->validate([
            'type' => 'required|in:boletin,guia,articulo',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
            'doi' => 'nullable|string',
            'issn' => 'nullable|string',
            'file_path' => 'nullable|string',
            'cover_image_file' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();
        $coverPath = null;

        if ($request->hasFile('cover_image_file')) {
            $coverPath = FileUploadService::uploadImage(
                $request->file('cover_image_file'), 'covers');
        }

        $publication = Publication::create([
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description,
            'link' => $request->link,
            'doi' => $request->doi,
            'issn' => $request->issn,
            'file_path' => $request->file_path,
            'cover_image' => $coverPath,
            'author_id' => $user->id,
            'status' => 'publico'
        ]);    

        return ApiResponse::created('Publicación creada correctamente', $publication);
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
            'issn' => 'nullable|string',
            'file_path' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'status' => 'in:publico,archivado'
        ]);

        $pub->update($request->only([
            'title', 'description', 'link', 'doi', 'issn', 
            'file_path', 'cover_image', 'status'
        ]));

        return ApiResponse::success('Publicación actualizada correctamente', $pub);
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

        $pub->delete();

        return ApiResponse::success('Publicación eliminada permanentemente');
    }
}
