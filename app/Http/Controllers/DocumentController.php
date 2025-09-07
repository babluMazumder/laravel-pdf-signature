<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use setasign\Fpdi\TcpdfFpdi;
use Smalot\PdfParser\Parser;


class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:pdf|max:20000',
        ]);

        $file = $request->file('file');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('documents', $filename, 'public');

        $doc = Document::create([
            'name' => $filename,
            'path' => $path,
        ]);

        return redirect()->route('documents.view', $doc->id);
    }

    public function view(Document $document)
    {
        return view('documents.view', compact('document'));
    }

   public function sign(Request $request, Document $document)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = "signed_" . time() . ".pdf";
            $path = $file->storeAs('documents', $filename, 'public');
            return response()->json(["path" => "/storage/" . $path]);
        }
        return response()->json(['error' => 'No PDF uploaded'], 400);
    }



    public function edit(Document $document)
  {
     return view('pdf-editor', compact('document'));
    }





    public function saveEdited(Request $request, Document $document)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = "edited_" . time() . ".pdf";
            $path = $file->storeAs('documents', $filename, 'public');
            return response()->json(["path" => "/storage/" . $path]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    }


}
