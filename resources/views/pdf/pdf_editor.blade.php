<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Editor Demo</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f8f9fa; }
        #pdf-container { border: 1px solid #ccc; display:inline-block; position:relative; }
        #pdf-canvas { display:block; }
        #overlay { position:absolute; top:0; left:0; }
        .field-box {
            background: rgba(255,255,255,0.7);
            border: 2px dashed red;
            font-size: 12px;
            color: #d00;
            display:flex;
            align-items:center;
            justify-content:center;
            cursor: move;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h3 class="mb-3">📄 PDF Template Editor</h3>

    {{-- Upload PDF --}}
    <form id="uploadForm" class="mb-3" enctype="multipart/form-data">
        @csrf
        <div class="row g-2">
            <div class="col-md-4">
                <input type="file" name="pdf" accept="application/pdf" class="form-control" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="name" placeholder="Template Name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Upload</button>
            </div>
        </div>
    </form>

    <div id="pdf-container" class="mb-3">
        <canvas id="pdf-canvas"></canvas>
        <div id="overlay"></div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm" onclick="setAddMode('text')">Add Text</button>
        <button class="btn btn-info btn-sm" onclick="setAddMode('date')">Add Date</button>
        <button class="btn btn-warning btn-sm" onclick="setAddMode('signature')">Add Signature</button>
        <button class="btn btn-primary btn-sm" onclick="saveFields()">Save Fields</button>
    </div>
</div>

{{-- PDF.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
let pdfDoc = null;
let scale = 1.2;
let addMode = null;
let fields = [];
let currentTemplateId = null;

const canvas = document.getElementById('pdf-canvas');
const ctx = canvas.getContext('2d');
const overlay = document.getElementById('overlay');

// 1. Upload PDF
document.getElementById('uploadForm').addEventListener('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);

    fetch("{{ url('/pdf/upload') }}", {
        method: "POST",
        body: formData,
    })
    .then(res => res.json())
    .then(data => {
        currentTemplateId = data.id;
        renderPDF("{{ url('storage') }}/" + data.file_path);
    })
    .catch(err => alert("Upload failed: " + err));
});

// 2. Render PDF
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

        // Overlay size match
        overlay.style.width = viewport.width + "px";
        overlay.style.height = viewport.height + "px";

        page.render({
            canvasContext: ctx,
            viewport: viewport
        });
    });
}

// 3. Add field on click
overlay.addEventListener('click', function(e){
    if(!addMode) return;
    let rect = overlay.getBoundingClientRect();
    let x = e.clientX - rect.left;
    let y = e.clientY - rect.top;

    let field = {
        type: addMode,
        page: 1,
        x: x,
        y: y,
        width: 120,
        height: 30,
        required: true
    };
    fields.push(field);

    let div = document.createElement('div');
    div.classList.add('field-box');
    div.style.left = x + "px";
    div.style.top = y + "px";
    div.style.width = field.width + "px";
    div.style.height = field.height + "px";
    div.style.position = "absolute";
    div.innerText = addMode.toUpperCase();
    overlay.appendChild(div);
});

function setAddMode(type){
    addMode = type;
    alert("Click on PDF to add " + type);
}

// 4. Save fields
function saveFields(){
    if(!currentTemplateId){
        alert("Upload a PDF first!");
        return;
    }
    fetch(`/pdf/${currentTemplateId}/fields`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ fields: fields })
    })
    .then(res => res.json())
    .then(data => {
        alert("Fields saved!");
    })
    .catch(err => alert("Save failed: " + err));
}
</script>
</body>
</html>
