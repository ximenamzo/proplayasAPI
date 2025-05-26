<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Book;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookController extends BaseContentController
{
    protected string $modelClass = Book::class;
    protected array $searchableFields = ['title', 'description', 'book_author', 'isbn'];
    protected string $type = 'libro';
    

    /** 🟡 Crear libro */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'book_author' => 'required|string|max:255',
                'publication_date' => 'date|nullable',
                'isbn' => 'nullable|string|max:255',
                'description' => 'required|string',
                'link' => 'nullable|url',
                'file_file' => 'nullable|file|mimes:pdf,docx,xlsx|max:20480',
                'file_url' => 'nullable|url',
                'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'cover_image_url' => 'nullable|url'
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
    
            $book = Book::create([
                'title' => $request->title,
                'book_author' => $request->book_author,
                'publication_date' => $request->publication_date,
                'isbn' => $request->isbn,
                'description' => $request->description,
                'link' => $request->link,
                'file_path' => $filePath,
                'cover_image' => $coverPath,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]);

            return ApiResponse::created("Libro creado correctamente", $book);

        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear evento:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al guardar evento', 500, ['debug' => $e->getMessage()]);
        }
    }

    /** 🟡 Actualizar libro */
    public function update($id, Request $request)
    {
        $book = Book::find($id);
        if (!$book) return ApiResponse::notFound('Libro no encontrado');

        $auth = $request->user();
        if ($book->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'book_author' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'isbn' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url'
        ]);

        $book->update($request->only([
            'title', 'book_author', 'publication_date', 'isbn', 'description', 'link'
        ]));

        return ApiResponse::success('Libro actualizado correctamente', $book);
    }
}
