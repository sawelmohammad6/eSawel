@extends('layouts.app')

@section('content')
    <section class="shell">
        @include('partials.admin-hub')
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Catalog</p>
                <h1 class="mt-2 text-3xl font-black">Brands</h1>
                <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <input class="field" type="text" name="name" placeholder="Brand name">
                    <textarea class="field min-h-28" name="description" placeholder="Description"></textarea>
                    <input class="field" type="file" name="logo_file" accept="image/jpeg,image/png,image/webp,image/jpg">
                    <p class="text-xs text-slate-500">JPG, PNG, or WebP, up to 4&nbsp;MB. Or paste a logo URL below.</p>
                    <input class="field" type="url" name="logo" placeholder="Logo URL (optional)">
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
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($brands as $brand)
                            <tr>
                                <td>{{ $brand->name }}</td>
                                <td>
                                    @if ($brand->logo)
                                        <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" class="h-12 w-12 rounded-lg border border-slate-200 bg-white object-contain p-1">
                                    @else
                                        <span class="text-xs text-slate-400">None</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data" class="grid gap-2 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <input class="field" type="text" name="name" value="{{ $brand->name }}">
                                        <input class="field md:col-span-2" type="file" name="logo_file" accept="image/jpeg,image/png,image/webp,image/jpg">
                                        <input class="field md:col-span-2" type="url" name="logo" value="{{ $brand->logo }}" placeholder="Logo URL (optional)">
                                        <textarea class="field md:col-span-2" name="description">{{ $brand->description }}</textarea>
                                        <div class="flex flex-wrap gap-3 md:col-span-2">
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1" @checked($brand->is_featured)> Featured</label>
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" @checked($brand->is_active)> Active</label>
                                            <button class="btn-outline" type="submit">Save</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" onsubmit="return confirm('Delete this brand? Products using it will have the brand cleared.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-red-600">Delete</button>
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
