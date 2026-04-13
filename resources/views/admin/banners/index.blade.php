@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Homepage</p>
                <h1 class="mt-2 text-3xl font-black">Banners</h1>
                <form action="{{ route('admin.banners.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <input class="field" type="text" name="title" placeholder="Title">
                    <input class="field" type="text" name="subtitle" placeholder="Subtitle">
                    <input class="field" type="url" name="image" placeholder="Image URL">
                    <input class="field" type="url" name="link" placeholder="Link URL">
                    <select class="field" name="placement">
                        <option value="home_hero">Home hero</option>
                        <option value="promo">Promo block</option>
                    </select>
                    <input class="field" type="number" name="sort_order" value="0" placeholder="Sort order">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn-primary w-full" type="submit">Add Banner</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Placement</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banners as $banner)
                            <tr>
                                <td>{{ $banner->title }}</td>
                                <td>{{ $banner->placement }}</td>
                                <td>
                                    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" class="grid gap-2 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <input class="field" type="text" name="title" value="{{ $banner->title }}">
                                        <input class="field" type="text" name="subtitle" value="{{ $banner->subtitle }}">
                                        <input class="field md:col-span-2" type="url" name="image" value="{{ $banner->image }}" placeholder="Image URL">
                                        <input class="field md:col-span-2" type="url" name="link" value="{{ $banner->link }}" placeholder="Link URL">
                                        <select class="field" name="placement">
                                            <option value="home_hero" @selected($banner->placement === 'home_hero')>Home hero</option>
                                            <option value="promo" @selected($banner->placement === 'promo')>Promo block</option>
                                        </select>
                                        <input class="field" type="number" name="sort_order" value="{{ $banner->sort_order }}">
                                        <div class="flex items-center gap-3 md:col-span-2">
                                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" @checked($banner->is_active)> Active</label>
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
