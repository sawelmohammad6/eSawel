@extends('layouts.app')

@section('content')
    <section class="shell">
        @php
            $mediaUrl = function (?string $path): string {
                $path = trim((string) $path);

                if ($path === '') {
                    return asset('images/placeholder.svg');
                }

                if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
                    return $path;
                }

                return asset('storage/'.$path);
            };
        @endphp

        @include('partials.admin-hub')
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Catalog</p>
                <h1 class="mt-2 text-3xl font-black">Categories</h1>
                <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <select class="field" name="parent_id">
                        <option value="">No parent category</option>
                        @foreach ($parentCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input class="field" type="text" name="name" placeholder="Category name">
                    <textarea class="field min-h-28" name="description" placeholder="Description"></textarea>
                    <input class="field" type="file" name="image_file" accept="image/*">
                    <input class="field" type="number" name="sort_order" placeholder="Sort order" value="0">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1"> Featured</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn-primary w-full" type="submit">Add Category</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Update</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>
                                    <img src="{{ $mediaUrl($category->image) }}" alt="{{ $category->name }}" class="h-12 w-12 rounded-lg object-cover">
                                </td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->parent?->name ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="grid gap-2 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <input class="field" type="text" name="name" value="{{ $category->name }}">
                                        <select class="field" name="parent_id">
                                            <option value="">No parent</option>
                                            @foreach ($parentCategories as $parent)
                                                <option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>
                                            @endforeach
                                        </select>
                                        <input class="field md:col-span-2" type="file" name="image_file" accept="image/*">
                                        <textarea class="field md:col-span-2" name="description">{{ $category->description }}</textarea>
                                        <div class="flex flex-wrap gap-3 md:col-span-2">
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1" @checked($category->is_featured)> Featured</label>
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Active</label>
                                            <button class="btn-outline" type="submit">Save</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?');">
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
