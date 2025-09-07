<form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" required>
    <button type="submit">Upload PDF</button>
</form>
