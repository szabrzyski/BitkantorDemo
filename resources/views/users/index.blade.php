@extends('layouts.app')
{{-- @push('js')
    <script src="{{ asset('js/users.js') }}"></script>
@endpush --}}
@section('content')
    @include('layouts.modals.deleteConfirmation')
    @include('layouts.admin.navigationBar')
    <div class="container mt-4">
        <div class="row mb-4" id="komunikat">
            <div class="col-12">
                @include('layouts.alert')
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <a class="btn btn-success" href="{{ route('users.create') }}" role="button">Dodaj nowego użytkownika</a>
            </div>
        </div>
        <div class="row">
            {{-- Lista użytkowników --}}
            <div class="col-12">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Email</th>
                            <th scope="col">Imię</th>
                            <th scope="col">Nazwisko</th>
                            <th scope="col" class="text-center">Wyświetl</th>
                            <th scope="col" class="text-center">Edytuj</th>
                            <th scope="col" class="text-center">Usuń</th>
                        </tr>
                    </thead>

                    @foreach ($results as $user)
                        <tr>
                            <th scope="row">{{ $user->id }}</th>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->surname }}</td>
                            <td class="text-center"><button type="button" class="btn btn-link p-0"><a
                                        href="{{ route('users.show', $user->id) }}"><i
                                            class="fas fa-eye"></i></a></button></td>
                            <td class="text-center"><button type="button" class="btn btn-link p-0"><a
                                        href="{{ route('users.edit', $user->id) }}"><i
                                            class="fas fa-edit"></i></a></button></td>
                            <td class="text-center"><button type="button" class="btn btn-link p-0" data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmationModal"
                                    data-bs-deleteLink="{{ route('users.destroy', $user->id) }}"><i
                                        class="fas fa-times"></i></button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @include('layouts.admin.paginationBar')
    </div>

@endsection
