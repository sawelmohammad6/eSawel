@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">User Management</h1>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}<br><span class="text-xs text-slate-400">{{ $user->email }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                            <td>{{ ucfirst($user->status) }}</td>
                            <td>
                                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="flex flex-wrap gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select class="field min-w-40" name="role">
                                        @foreach (['customer', 'seller', 'admin', 'sub_admin'] as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
                                    <select class="field min-w-40" name="status">
                                        @foreach (['active', 'pending', 'blocked'] as $status)
                                            <option value="{{ $status }}" @selected($user->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-outline" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $users->links() }}</div>
        </div>
    </section>
@endsection
