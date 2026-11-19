<?php

namespace App\Http\Controllers;

use App\Models\TicketMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto laporan kerusakan: untuk penyewa pelapor dan pengelola.
 */
class LaporanFotoController extends Controller
{
    public function __invoke(Request $request, TicketMedia $media): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->isPengelola() || $media->laporan->lease->user_id === $user->id, 404);
        abort_unless(Storage::disk('local')->exists($media->file_path), 404);

        return Storage::disk('local')->response($media->file_path, headers: ['Cache-Control' => 'private, no-store']);
    }
}
