<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fill & Sign PDF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      #pdf-container { position: relative; display:inline-block; border:1px solid #ccc; }
      #pdf-canvas { display:block; }
      .field-input {
          position: absolute;
          border: 1px solid #007bff;
          background: rgba(255,255,255,0.9);
          font-size: 12px;
          padding: 2px;
      }
      canvas.field-input {
          background: #fff;
      }
  </style>
</head>
<body>
<div class="container py-4">
    <h3>Fill & Sign PDF</h3>

    <div id="pdf-container">
        <canvas id="pdf-canvas"></canvas>
        <div id="overlay"></div>
    </div>

    <button class="btn btn-success mt-3" onclick="submitForm()">✅ Submit Signed PDF</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
let pdfDoc = null;
let scale = 1.2;
let fields = [];
let responses = [];
let signaturePads = {}; // 🔹 Keep all signature pads
const id = "{{ $assignmentId }}";

const canvas = document.getElementById('pdf-canvas');
const ctx = canvas.getContext('2d');
const overlay = document.getElementById('overlay');

// 1. Load assignment data
fetch(`/pdf-test/assignment-data/${id}`)
.then(res => res.json())
.then(data => {
    fields = data.fields;
    renderPDF("{{ asset('storage') }}/" + data.file_path);
});

// Render PDF
function renderPDF(url){
    pdfjsLib.getDocument(url).promise.then(function(pdf){
        pdfDoc = pdf;
        renderPage(1);
    });
}

function renderPage(num){
    pdfDoc.getPage(num).then(function(page){
        let viewport = page.getViewport({ scale: scale });
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        overlay.style.width = viewport.width + "px";
        overlay.style.height = viewport.height + "px";
        overlay.style.position = "absolute";
        overlay.style.top = 0;
        overlay.style.left = 0;

        page.render({ canvasContext: ctx, viewport: viewport });

        // 🔹 Draw fields
        fields.forEach(f => {
            if(f.type === 'signature'){
                let sigCanvas = document.createElement('canvas');
                sigCanvas.width = f.width;
                sigCanvas.height = f.height;
                sigCanvas.classList.add('field-input');
                sigCanvas.style.left = f.x+"px";
                sigCanvas.style.top = f.y+"px";
                sigCanvas.style.position = "absolute";
                sigCanvas.dataset.field_id = f.id;
                overlay.appendChild(sigCanvas);

                // Keep signaturePad instance
                signaturePads[f.id] = new SignaturePad(sigCanvas);
            } else {
                let input = document.createElement('input');
                input.type = (f.type === 'date') ? 'date' : 'text';
                input.classList.add('field-input');
                input.style.width = f.width+"px";
                input.style.height = f.height+"px";
                input.style.left = f.x+"px";
                input.style.top = f.y+"px";
                input.style.position = "absolute";
                input.dataset.field_id = f.id;
                overlay.appendChild(input);
            }
        });
    });
}

// 2. Submit form
function submitForm(){
    responses = [];

    // 🔹 Collect inputs
    document.querySelectorAll('.field-input').forEach(el => {
        let fieldId = el.dataset.field_id;
        let val = "";

        if(el.tagName === 'CANVAS'){
            let sigPad = signaturePads[fieldId];
            if(sigPad && !sigPad.isEmpty()){
                val = sigPad.toDataURL().replace(/^data:image\/png;base64,/, "");
            } else {
                alert("Signature is required!");
                return;
            }
        } else {
            val = el.value;
        }

        responses.push({ field_id: fieldId, value: val });
    });

    // 🔹 Send to backend
    fetch(`/pdf/assignment/${id}/submit`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ responses })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'completed'){
            alert("✅ Signed PDF submitted!");
            // Redirect to view signed PDF
            window.location.href = `/pdf/assignment/${assignmentId}/view`;
        } else {
            alert("Error: " + JSON.stringify(data));
        }
    })
    .catch(err => alert("Submit failed: " + err));
}
</script>
</body>
</html>
