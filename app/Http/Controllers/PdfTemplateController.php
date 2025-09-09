<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PdfField;
use App\Models\PdfResponse;
use App\Models\PdfTemplate;
use App\Models\PdfAssignment;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class PdfTemplateController extends Controller
{
    /**
     * Upload a PDF and create a template
     */
    public function upload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf',
            'name' => 'required|string|max:255',
        ]);

        $path = $request->file('pdf')->store('pdfs', 'public');

        $template = PdfTemplate::create([
            'name'      => $request->name,
            'file_path' => $path,
        ]);

        return response()->json($template);
    }

    /**
     * Save fields for a template
     */
    public function saveFields(Request $request, PdfTemplate $template)
    {
        foreach ($request->fields as $field) {
            PdfField::create([
                'template_id' => $template->id,
                'type'        => $field['type'],
                'page'        => $field['page'],
                'x'           => $field['x'],
                'y'           => $field['y'],
                'width'       => $field['width'],
                'height'      => $field['height'],
                'required'    => $field['required'],
                'label'       => $field['label'] ?? null,
            ]);
        }
        return response()->json(['status' => 'fields_saved']);
    }

    /**
     * Assign a template to a user
     */
    public function assignToUser(PdfTemplate $template, User $user)
    {
        $assignment = PdfAssignment::create([
            'template_id' => $template->id,
            'user_id'     => $user->id,
            'status'      => 'pending',
        ]);

        return response()->json($assignment);
    }

    /**
     * Submit filled/signed PDF
     */
    public function submit(Request $request, PdfAssignment $assignment)
    {
        foreach ($request->responses as $res) {
            PdfResponse::updateOrCreate(
                ['assignment_id' => $assignment->id, 'field_id' => $res['field_id']],
                ['value' => $res['value']]
            );
        }

        // Generate final PDF
        $this->generateFinalPdf($assignment);

        $assignment->update(['status' => 'completed']);

        return response()->json(['status' => 'completed']);
    }

    /**
     * Generate the final signed PDF
     */
    protected function generateFinalPdf(PdfAssignment $assignment)
    {
        $template  = $assignment->template;
        $responses = $assignment->responses()->with('field')->get();

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile(storage_path("app/public/" . $template->file_path));

        for ($page = 1; $page <= $pageCount; $page++) {
            $tplIdx = $pdf->importPage($page);
            $size   = $pdf->getTemplateSize($tplIdx);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplIdx);

            foreach ($responses as $res) {
                if ($res->field->page == $page) {
                    if ($res->field->type === 'signature') {
                        // Signature (base64 → PNG → embed)
                        $tmpDir = storage_path('app/public/tmp');
                        if (!file_exists($tmpDir)) mkdir($tmpDir, 0777, true);

                        $imgPath = $tmpDir . '/' . uniqid() . '.png';
                        file_put_contents($imgPath, base64_decode($res->value));

                        $pdf->Image(
                            $imgPath,
                            $res->field->x,
                            $res->field->y,
                            $res->field->width,
                            $res->field->height
                        );
                    } else {
                        // Text / Date
                        $pdf->SetFont('Helvetica', '', 10);

                        // Adjust text baseline so it centers vertically
                        $x = $res->field->x;
                        $y = $res->field->y + ($res->field->height / 2);

                        $pdf->Text($x, $y, $res->value);
                        // Alternative: wrap in box
                        // $pdf->SetXY($res->field->x, $res->field->y);
                        // $pdf->MultiCell($res->field->width, $res->field->height, $res->value, 0, 'L');
                    }
                }
            }
        }

        // Ensure directory exists
        $dir = storage_path('app/public/signed_pdfs');
        if (!file_exists($dir)) mkdir($dir, 0777, true);

        // Save final PDF
        $finalPath = 'signed_pdfs/' . uniqid() . '.pdf';
        $pdf->Output($dir . '/' . basename($finalPath), 'F');

        // Save path in DB
        $assignment->update(['final_path' => $finalPath]);
    }

    /**
     * Return template + fields for frontend fill
     */
    public function getAssignmentData($id)
    {
        $assignment = PdfAssignment::with('template.fields')->findOrFail($id);

        if (!$assignment->template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        return response()->json([
            'file_path' => $assignment->template->file_path,
            'fields'    => $assignment->template->fields,
        ]);
    }

    /**
     * View signed PDF in browser
     */
    public function viewSignedPdf($id)
    {
        $assignment = PdfAssignment::findOrFail($id);

        if (!$assignment->final_path) {
            return response()->json(['error' => 'No signed document found'], 404);
        }

        $filePath = storage_path('app/public/' . $assignment->final_path);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File missing from storage'], 404);
        }

        return response()->file($filePath);
    }
}
