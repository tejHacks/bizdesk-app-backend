<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ExpenseController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->expenses()->latest('spent_on')->latest('id')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'spent_on' => ['nullable', 'date'],
        ]);

        $expense = $request->user()->expenses()->create([...$data, 'spent_on' => $data['spent_on'] ?? today()]);

        return response()->json(['data' => $expense], 201);
    }

    public function destroy(Request $request, Expense $expense): Response
    {
        $this->owned($request, $expense)->delete();

        return response()->noContent();
    }
}
