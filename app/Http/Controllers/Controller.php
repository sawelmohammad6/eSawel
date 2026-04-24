<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Notifications\MarketplaceNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class Controller
{
    protected function uniqueSlug(string $value, string $modelClass, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug !== '' ? $baseSlug : Str::random(8);
        $counter = 1;

        while ($modelClass::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

    protected function syncProductImages(Product $product, array $imageUrls): void
    {
        $urls = collect($imageUrls)
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->values();

        if ($urls->isEmpty()) {
            return;
        }

        $product->images()->delete();

        $urls->each(function (string $url, int $index) use ($product): void {
            $product->images()->create([
                'path' => $url,
                'alt_text' => $product->name,
                'is_primary' => $index === 0,
                'sort_order' => $index,
            ]);
        });
    }

    protected function deleteStoredPublicFile(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        if (str_starts_with($path, '/storage/')) {
            $path = str_replace('/storage/', '', $path);
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }

    protected function publicStorageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    protected function logActivity(
        ?Authenticatable $user,
        string $action,
        string $description = '',
        ?Model $subject = null,
        array $metadata = []
    ): void {
        ActivityLog::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    protected function notifyUsers(iterable $users, string $title, string $body, ?string $url = null, string $kind = 'info'): void
    {
        $notifiables = Collection::wrap($users)->filter();

        if ($notifiables->isEmpty()) {
            return;
        }

        Notification::send($notifiables, new MarketplaceNotification($title, $body, $url, $kind));
    }
}
