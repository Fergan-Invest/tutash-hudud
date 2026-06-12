<?php

namespace App\Http\Controllers;

use App\Models\RequestImage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequestImageController extends Controller
{
    public function destroy(Request $request, RequestImage $image, AuditLogger $auditLogger)
    {
        $registryRequest = $image->registryRequest;
        $this->authorize('update', $registryRequest);

        $old = $image->toArray();
        Storage::disk('public')->delete($image->path);
        $image->delete();

        $auditLogger->log($registryRequest, 'image_deleted', $old, [], $request);

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('success', 'Rasm o‘chirildi.');
    }
}
