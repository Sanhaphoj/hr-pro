<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:documents.view', only: ['index', 'download']),
            new Middleware('permission:documents.manage', only: ['store', 'destroy']),
        ];
    }

    public function index(): View
    {
        $documents = Document::query()
            ->with('uploader:id,name')
            ->latest()
            ->paginate(config('hrpro.per_page'));

        return view('documents.index', [
            'documents' => $documents,
            'categories' => Document::CATEGORY_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:'.implode(',', array_keys(Document::CATEGORY_LABELS))],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'title' => $data['title'],
            'category' => $data['category'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'is_public' => true,
        ]);

        AuditLogger::log('created', "อัปโหลดเอกสาร: {$document->title}", $document);

        return redirect()->route('documents.index')->with('success', 'อัปโหลดเอกสารเรียบร้อย');
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path),
        );
    }

    public function destroy(Document $document): RedirectResponse
    {
        Storage::disk('public')->delete($document->file_path);
        $title = $document->title;
        $document->delete();

        AuditLogger::log('deleted', "ลบเอกสาร: {$title}", $document);

        return redirect()->route('documents.index')->with('success', 'ลบเอกสารเรียบร้อย');
    }
}
