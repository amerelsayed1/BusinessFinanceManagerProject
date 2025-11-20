<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function index(): JsonResponse
    {
        $bills = Bill::with('account:id,name')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($bill) {
                return [
                    'id'        => $bill->id,
                    'description' => $bill->description,
                    'amount'    => $bill->amount,
                    'date'      => $bill->date->format('Y-m-d'),
                    'status'    => $bill->status,
                    'accountId' => $bill->account_id,
                    'image'     => $bill->image,
                ];
            });

        return response()->json($bills);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'status'      => 'required|string|in:pending,paid',
            'accountId'   => 'required|integer|exists:accounts,id',
            'image'       => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $bill = Bill::create([
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'date'        => $data['date'],
                'status'      => $data['status'],
                'account_id'  => $data['accountId'],
                'image'       => $data['image'] ?? null,
            ]);

            if ($data['status'] === 'paid') {
                $account = Account::findOrFail($data['accountId']);
                $account->balance -= $data['amount'];
                $account->save();
            }

            return response()->json([
                'id'        => $bill->id,
                'description' => $bill->description,
                'amount'    => $bill->amount,
                'date'      => $bill->date->format('Y-m-d'),
                'status'    => $bill->status,
                'accountId' => $bill->account_id,
                'image'     => $bill->image,
            ], 201);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $bill = Bill::findOrFail($id);
            $account = $bill->account;

            if ($bill->status === 'paid' && $account) {
                $account->balance += $bill->amount;
                $account->save();
            }

            $bill->delete();

            return response()->json(['success' => true]);
        });
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,paid',
        ]);

        return DB::transaction(function () use ($data, $id) {
            $bill = Bill::findOrFail($id);
            $oldStatus = $bill->status;
            $newStatus = $data['status'];

            if ($oldStatus === $newStatus) {
                return response()->json(['success' => true]);
            }

            $bill->status = $newStatus;
            $bill->save();

            $account = $bill->account;

            if ($account) {
                if ($oldStatus !== 'paid' && $newStatus === 'paid') {
                    $account->balance -= $bill->amount;
                } elseif ($oldStatus === 'paid' && $newStatus !== 'paid') {
                    $account->balance += $bill->amount;
                }
                $account->save();
            }

            return response()->json(['success' => true]);
        });
    }
}
