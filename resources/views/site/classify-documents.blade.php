@extends('site.master')

@section('title', 'Classify Your Documents | ' . env('APP_NAME'))


@section('content')


    <table class="table table-bordered table-hover table-striped">
        <thead>
            <tr class="text-center table-dark">
                <td colspan="5">
                    <h3><b>Files Count: ({{ $filesCount }}) | Classification Time:</strong>
                            {{ $classificationTime }} seconds</b></h3>
                </td>
            </tr>
            <tr class="text-center">
                <th>File Name</th>
                <th>File type</th>
                <th>Title</th>
                <th>Classification</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($files as $file)
                <tr class="text-center">
                    <td>{{ pathinfo(basename($file->file), PATHINFO_FILENAME) }}</td>
                    <td>{{ $file->file_type }}</td>
                    <td>{{ $file->title }}</td>
                    <td>
                        <b>{{ $file->classification['level1'] }}</b>
                        @if ($file->classification['level2'])
                            <b> > {{ $file->classification['level2'] }} </b>
                        @endif
                        @if ($file->classification['level3'])
                            <b> > {{ $file->classification['level3'] }} </b>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('cloud-project.view-file', $file->id) }}" class="btn btn-primary">View</a>
                        <hr>
                        <form class="d-inline" action="{{ route('cloud-project.delete-file', $file->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <th colspan="5" class="text-center">No Data Found.</th>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $files->links('pagination::bootstrap-5') }}










@endsection
