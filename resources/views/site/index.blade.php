@extends('site.master')
@section('title', 'Home | ' . env('APP_NAME'))

@section('content')

    @auth
        <div class="container py-5">
            <h2 class="mb-4"><b>Upload Your PDF/Word Files In This Form</b></h2>

            @if (session('msg'))
                <div class="alert alert-{{ session('type') }}">
                    {{ session('msg') }}
                </div>
            @endif

            <form class="mb-5" action="{{ route('cloud-project.upload-files') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="file" class="mb-3"><b>File</b></label>
                    <input type="file" name="file" id="file"
                        class="form-control @error('file')
                        is-invalid
                @enderror">
                    @error('file')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <button class="btn btn-primary">Submit</button>
            </form>


            <table class="table table-bordered table-hover table-striped">

                <thead>
                    <tr class="text-center table-dark">
                        <td colspan="5">
                            <h3><b>(Files Sorted By Title) (Sorting Time: {{ $sortingTime }} seconds) | Files Count:
                                    ({{ $filesCount }})</b></h3>
                        </td>
                    </tr>
                    <tr class="text-center">
                        <th>File Name</th>
                        <th>File type</th>
                        <th>Title</th>
                        <th>File Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($files as $file)
                        <tr class="text-center">
                            <td>{{ pathinfo(basename($file->file), PATHINFO_FILENAME) }}</td>
                            <td>{{ $file->file_type }}</td>
                            <td>{{ $file->title }}</td>
                            <td>{{ $file->size }}</td>
                            <td><a href="{{ route('cloud-project.view-file', $file->id) }}" class="btn btn-primary">View</a>
                                <form class="d-inline" action="{{ route('cloud-project.delete-file', $file->id) }}"
                                    method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <th colspan="4" class="text-center">No Data Found.</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $files->links('pagination::bootstrap-5') }}
        </div>
    @endauth
    @guest
        <div class="container py-5">
            <h3><b>To upload files and see analytics you need to be authenticated. Click <a
                        href="{{ route('login') }}">Here</a>
                    to
                    sign in.</b></h3>
        </div>
    @endguest

@endsection
