<html>
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PDF Editor</title>
  <style>
    body { margin:0; font-family:Arial, sans-serif; }
    #viewer { height: 95vh; width: 100%; }
  </style>

  <!-- ✅ PDF.js Express (free tier) -->
  <script src="https://pdfjs.express/lib/webviewer.min.js"></script>
</head>
<body>
  <div id="viewer"></div>

  <script>
    WebViewer({
      path: 'https://pdfjs.express/lib', // library assets
      initialDoc: "{{ asset('storage/'.$document->path) }}" // your Laravel PDF
    }, document.getElementById('viewer')).then(instance => {
      const { docViewer, annotManager } = instance;

      console.log("✅ PDF loaded for editing");

      // Example: handle save button (export annotations + PDF)
      instance.setHeaderItems(header => {
        header.push({
          type: 'actionButton',
          img: 'https://img.icons8.com/ios-filled/24/000000/save.png',
          onClick: async () => {
            const data = await instance.downloadPdf(); // get edited PDF
            const blob = new Blob([data], { type: "application/pdf" });

            const formData = new FormData();
            formData.append("file", blob, "edited.pdf");

            fetch("{{ route('documents.sign',$document->id) }}", {
              method: "POST",
              headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
              body: formData
            })
            .then(res => res.json())
            .then(data => alert("✅ Saved at: " + data.path));
          }
        });
      });
    });
  </script>
</body>
</html>
