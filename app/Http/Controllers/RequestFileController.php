<?php

namespace App\Http\Controllers;

use App\Models\RequestFile;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequestFileController extends Controller
{
    public function destroy(Request $request, RequestFile $file, AuditLogger $auditLogger)
    {
        $registryRequest = $file->registryRequest;
        $this->authorize('update', $registryRequest);

        $old = $file->toArray();
        Storage::disk('public')->delete($file->path);
        $file->delete();

        $auditLogger->log($registryRequest, 'file_deleted', $old, [], $request);

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('success', 'Fayl o‘chirildi.');
    }
}
