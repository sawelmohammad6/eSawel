@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Catalog</p>
                <h1 class="mt-2 text-3xl font-black">Brands</h1>
                <form action="{{ route('admin.brands.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <input class="field" type="text" name="name" placeholder="Brand name">
                    <textarea class="field min-h-28" name="description" placeholder="Description"></textarea>
                    <input class="field" type="url" name="logo" placeholder="Logo URL">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1"> Featured</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn-primary w-full" type="submit">Add Brand</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Logo</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($brands as $brand)
                            <tr>
                                <td>{{ $brand->name }}</td>
                                <td>{{ $brand->logo ? 'Set' : 'None' }}</td>
                                <td>
                                    <form action="{{ route('admin.brands.update', $brand) }}" method="POST" class="grid gap-2 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <input class="field" type="text" name="name" value="{{ $brand->name }}">
                                        <input class="field" type="url" name="logo" value="{{ $brand->logo }}" placeholder="Logo URL">
                                        <textarea class="field md:col-span-2" name="description">{{ $brand->description }}</textarea>
                                        <div class="flex flex-wrap gap-3 md:col-span-2">
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1" @checked($brand->is_featured)> Featured</label>
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" @checked($brand->is_active)> Active</label>
                                            <button class="btn-outline" type="submit">Save</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
