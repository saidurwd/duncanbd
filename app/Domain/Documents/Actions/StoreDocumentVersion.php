<?php

namespace App\Domain\Documents\Actions;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreDocumentVersion
{
    public const DISK = 'local';

    public function __invoke(Document $document, UploadedFile $file, User $user): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $user) {
            $next = ((int) $document->versions()->max('version')) + 1;
            $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());

            // Private disk, random name: the client filename is kept only as display metadata.
            $path = $file->storeAs(
                "documents/{$document->uuid}",
                "v{$next}-".Str::random(24).".{$extension}",
                self::DISK,
            );

            $document->versions()->update(['is_current' => false]);

            return $document->versions()->create([
                'version' => $next,
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'mime' => (string) $file->getMimeType(),
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by' => $user->id,
                'is_current' => true,
            ]);
        });
    }
}
