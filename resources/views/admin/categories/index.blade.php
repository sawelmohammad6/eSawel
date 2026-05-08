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
            $parentLookup = $parentCategories->keyBy('id');
        @endphp

        @include('partials.admin-hub')
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Catalog</p>
                <h1 class="mt-2 text-3xl font-black">Categories</h1>
                <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <select class="field" name="parent_id">
                        <option value="">No parent category (create parent)</option>
                        @foreach ($parentCategories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('parent_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input class="field" type="text" name="name" value="{{ old('name') }}" placeholder="Category name">
                    <textarea class="field min-h-28" name="description" placeholder="Description">{{ old('description') }}</textarea>
                    <input class="field" type="file" name="image_file" accept="image/*">
                    <input class="field" type="number" name="sort_order" placeholder="Sort order" value="{{ old('sort_order', 0) }}">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> Featured</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active</label>
                    <button class="btn-primary w-full" type="submit">Add Category</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Parent</th>
                            <th>Update</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            @php
                                $isSubcategory = $category->parent_id !== null;
                                $categoryParentName = $category->parent_id ? ($parentLookup[$category->parent_id]->name ?? '-') : '-';
                            @endphp
                            <tr>
                                <td>
                                    <img src="{{ $mediaUrl($category->image) }}" alt="{{ $category->name }}" class="h-12 w-12 rounded-lg object-cover">
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if ($isSubcategory)
                                            <span class="text-slate-400">--</span>
                                        @endif
                                        <span class="font-semibold">{{ $category->name }}</span>
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">{{ $isSubcategory ? 'Subcategory' : 'Parent' }}</span>
                                    </div>
                                </td>
                                <td>{{ $categoryParentName }}</td>
                                <td>
                                    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="grid gap-2 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <input class="field" type="text" name="name" value="{{ $category->name }}">
                                        <select class="field" name="parent_id">
                                            <option value="">No parent</option>
                                            @foreach ($parentCategories as $parent)
                                                @continue($category->id === $parent->id)
                                                <option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>
                                            @endforeach
                                        </select>
                                        <input class="field md:col-span-2" type="file" name="image_file" accept="image/*">
                                        <textarea class="field md:col-span-2" name="description">{{ $category->description }}</textarea>
                                        <input class="field md:col-span-2" type="number" name="sort_order" value="{{ $category->sort_order }}" placeholder="Sort order">
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
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-sm text-slate-500">No categories created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
