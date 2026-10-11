<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function ask(Request $request, AssistantService $assistant): JsonResponse
    {
        abort_unless(AssistantService::enabled(), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array', 'max:'.(AssistantService::MAX_TURNS * 2)],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:6000'],
        ], [
            'message.required' => 'กรุณาพิมพ์คำถาม',
            'message.max' => 'คำถามยาวเกินไป (ไม่เกิน 1,000 ตัวอักษร)',
        ]);

        // The history comes from the browser: keep it only if it is a clean
        // user/assistant alternation starting with the user, as the API expects.
        $history = array_values($data['history'] ?? []);
        foreach ($history as $i => $turn) {
            if ($turn['role'] !== ($i % 2 === 0 ? 'user' : 'assistant')) {
                $history = [];
                break;
            }
        }
        if (count($history) % 2 === 1) {
            array_pop($history);
        }

        return response()->json($assistant->reply($request->user(), trim($data['message']), $history));
    }
}
