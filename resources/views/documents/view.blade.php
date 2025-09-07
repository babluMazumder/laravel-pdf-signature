<html>
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    #pdf-container { position:relative; }
    .pdf-page { border:1px solid #ddd; margin-bottom:20px; display:block; }
    .signature-preview {
      position:absolute; display:none;
      width:120px; cursor:move; z-index:10;
    }
    #signature-pad { border:1px solid #000; }
  </style>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.worker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

</head>
<body>
  <h3>PDF Viewer</h3>
  <div id="pdf-container"></div>

  <h3>Draw Signature</h3>
  <canvas id="signature-pad" width="300" height="150"></canvas><br>
  <button id="clear-sign">Clear</button>
  <button id="save-sign">Use Signature</button>
  <button id="save-pdf">Save Signed PDF</button>

<script>
let pdfDoc=null, scale=1.2, container=document.getElementById('pdf-container');
let activeSignature=null, pdfBytes=null;

// ✅ load PDF.js and pdf-lib source
fetch("{{ asset('storage/'.$document->path) }}")
  .then(res=>res.arrayBuffer())
  .then(async buffer=>{
    pdfBytes=buffer;
    pdfDoc=await pdfjsLib.getDocument({data:buffer}).promise;
    for(let pageNum=1;pageNum<=pdfDoc.numPages;pageNum++){renderPage(pageNum);}
});

function renderPage(num){
  pdfDoc.getPage(num).then(page=>{
    let viewport=page.getViewport({scale:scale});
    let canvas=document.createElement('canvas');
    canvas.className="pdf-page";
    let ctx=canvas.getContext('2d');
    canvas.height=viewport.height;
    canvas.width=viewport.width;
    container.appendChild(canvas);
    page.render({canvasContext:ctx,viewport:viewport});
    canvas.dataset.pageNum=num;
  });
}

// ✅ Signature Pad
const signPad=new SignaturePad(document.getElementById('signature-pad'));
document.getElementById('clear-sign').onclick=()=>signPad.clear();
document.getElementById('save-sign').onclick=()=>{
  let data=signPad.toDataURL();
  let img=document.createElement("img");
  img.src=data;
  img.className="signature-preview";
  img.style.left="20px"; img.style.top="20px";
  container.appendChild(img);
  img.style.display="block";
  makeDraggable(img);
  activeSignature=img;
};

// ✅ Make Signature Draggable
function makeDraggable(el){
  let offsetX,offsetY,isDragging=false;
  el.onmousedown=e=>{isDragging=true; offsetX=e.offsetX; offsetY=e.offsetY;};
  document.onmouseup=()=>isDragging=false;
  document.onmousemove=e=>{
    if(isDragging){
      el.style.left=(e.pageX-offsetX-container.offsetLeft)+"px";
      el.style.top=(e.pageY-offsetY-container.offsetTop)+"px";
      el.dataset.x=e.pageX-offsetX;
      el.dataset.y=e.pageY-offsetY;
    }
  };
}

// ✅ Save with pdf-lib
document.getElementById('save-pdf').onclick = async () => {
  if (!activeSignature) {
    alert("Please draw signature");
    return;
  }

  const pdfDocLib = await PDFLib.PDFDocument.load(pdfBytes);
  const pages = pdfDocLib.getPages();

  let sigRect = activeSignature.getBoundingClientRect();

  // find page under signature
  let pageCanvas = Array.from(document.querySelectorAll(".pdf-page")).find(c => {
    let rect = c.getBoundingClientRect();
    return sigRect.top >= rect.top && sigRect.bottom <= rect.bottom;
  });
  if (!pageCanvas) {
    alert("Signature not on page");
    return;
  }

  let rect = pageCanvas.getBoundingClientRect();

  // signature position/size in canvas space
  let sigXCanvas = sigRect.left - rect.left;
  let sigYCanvasTop = sigRect.top - rect.top;
  let sigWCanvas = sigRect.width;
  let sigHCanvas = sigRect.height;

  // normalize ratios
  let xRatio = sigXCanvas / rect.width;
  let yTopRatio = sigYCanvasTop / rect.height;
  let wRatio = sigWCanvas / rect.width;
  let hRatio = sigHCanvas / rect.height;

  // get PDF page
  let pageIndex = Number(pageCanvas.dataset.pageNum) - 1;
  let pdfPage = pages[pageIndex];
  const pngImage = await pdfDocLib.embedPng(activeSignature.src);
  const { width: pdfW, height: pdfH } = pdfPage.getSize();

  // map ratios → PDF units
  let x = xRatio * pdfW;
  let sigW = wRatio * pdfW;
  let sigH = hRatio * pdfH;

  // flip Y (canvas top → PDF bottom)
  let yFromTop = yTopRatio * pdfH;
  let y = pdfH - yFromTop - sigH;

  pdfPage.drawImage(pngImage, {
    x: x,
    y: y,
    width: sigW,
    height: sigH
  });

  const newPdf = await pdfDocLib.save();

  // Upload new PDF to server
  let formData = new FormData();
  formData.append("file", new Blob([newPdf], { type: "application/pdf" }), "signed.pdf");

  fetch("{{ route('documents.sign',$document->id) }}", {
    method: "POST",
    headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
    body: formData
  })
    .then(res => res.json())
    .then(data => alert("Signed PDF saved: " + data.path));
};


</script>
</body>
</html>
