@extends('site.master')
@section('title', 'File View | ' . env('APP_NAME'))

@section('content')



    <div id="nutrient" style="height: 100vh"></div>





@endsection


@section('scripts')

    <script src="{{ asset('assets/dist/nutrient-viewer.js') }}"></script>

    <script>
        NutrientViewer.load({
                container: "#nutrient",
                document: "{{ asset($file->file) }}"
            })
            .then(function(instance) {
                console.log("Nutrient loaded", instance);
            })
            .catch(function(error) {
                console.error(error.message);
            });
    </script>


@endsection
