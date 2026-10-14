@section('library_bottom')
    @parent
    <!-- Editor -->
    <!-- This editor signed in by dev.kartic@gmail.com -->
    <script src="https://cdn.tiny.cloud/1/5rzk7dzfqggihjrbjqgvyh3di3xlwrruidfl5lk5sdkryo2y/tinymce/6/tinymce.min.js"
            referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        });
    </script>
@endsection
