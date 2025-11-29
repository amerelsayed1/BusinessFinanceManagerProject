<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Bill::where('user_id', auth()->id())
            ->with('account:id,name')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $bills = $query->get()->map(function ($bill) {
            return [
                'id'          => $bill->id,
                'description' => $bill->description,
                'amount'      => $bill->amount,
                'date'        => $bill->date->format('Y-m-d'),
                'status'      => $bill->status,
                'accountId'   => $bill->account_id,
                'accountName' => $bill->account->name ?? null,
                'image'       => $bill->image,
                'isMonthly'   => $bill->is_monthly,
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
            'accountId'   => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            'image'       => 'nullable|string',
            'isMonthly'   => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($data) {
            $account = Account::where('user_id', auth()->id())->findOrFail($data['accountId']);

            $bill = Bill::create([
                'user_id'     => auth()->id(),
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'date'        => $data['date'],
                'status'      => $data['status'],
                'account_id'  => $data['accountId'],
                'image'       => $data['image'] ?? null,
                'is_monthly'  => $data['isMonthly'] ?? false,
            ]);

            if ($data['status'] === 'paid') {
                $account->lockForUpdate();
                $account->current_balance -= $data['amount'];
                $account->save();
            }

            return response()->json([
                'id'          => $bill->id,
                'description' => $bill->description,
                'amount'      => $bill->amount,
                'date'        => $bill->date->format('Y-m-d'),
                'status'      => $bill->status,
                'accountId'   => $bill->account_id,
                'image'       => $bill->image,
                'isMonthly'   => $bill->is_monthly,
            ], 201);
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'description' => 'sometimes|required|string',
            'amount'      => 'sometimes|required|numeric|min:0.01',
            'date'        => 'sometimes|required|date',
            'status'      => 'sometimes|required|string|in:pending,paid',
            'accountId'   => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            'image'       => 'nullable|string',
            'isMonthly'   => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($data, $id) {
            $bill = Bill::where('user_id', auth()->id())->lockForUpdate()->findOrFail($id);

            // Store original state
            $wasAffectingBalance = ($bill->status === 'paid');
            $originalAccount = null;

            if ($wasAffectingBalance) {
                $originalAccount = Account::where('user_id', auth()->id())
                    ->lockForUpdate()
                    ->findOrFail($bill->account_id);
                // Revert original balance impact
                $originalAccount->current_balance += $bill->amount;
                $originalAccount->save();
            }

            // Update bill
            $updateData = [];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['amount'])) $updateData['amount'] = $data['amount'];
            if (isset($data['date'])) $updateData['date'] = $data['date'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            if (isset($data['accountId'])) $updateData['account_id'] = $data['accountId'];
            if (isset($data['image'])) $updateData['image'] = $data['image'];
            if (isset($data['isMonthly'])) $updateData['is_monthly'] = $data['isMonthly'];

            $bill->update($updateData);
            $bill->refresh();

            // Apply new balance impact if bill is now paid
            if ($bill->status === 'paid') {
                $newAccount = Account::where('user_id', auth()->id())
                    ->lockForUpdate()
                    ->findOrFail($bill->account_id);
                $newAccount->current_balance -= $bill->amount;
                $newAccount->save();
            }

            return response()->json([
                'id'          => $bill->id,
                'description' => $bill->description,
                'amount'      => $bill->amount,
                'date'        => $bill->date->format('Y-m-d'),
                'status'      => $bill->status,
                'accountId'   => $bill->account_id,
                'image'       => $bill->image,
                'isMonthly'   => $bill->is_monthly,
            ], 200);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $bill = Bill::where('user_id', auth()->id())->lockForUpdate()->findOrFail($id);
            $account = Account::where('user_id', auth()->id())->lockForUpdate()->find($bill->account_id);

            if ($bill->status === 'paid' && $account) {
                $account->current_balance += $bill->amount;
                $account->save();
            }

            $bill->delete();

            return response()->json(['success' => true, 'message' => 'Bill deleted successfully']);
        });
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,paid',
        ]);

        return DB::transaction(function () use ($data, $id) {
            $bill = Bill::where('user_id', auth()->id())->lockForUpdate()->findOrFail($id);
            $oldStatus = $bill->status;
            $newStatus = $data['status'];

            if ($oldStatus === $newStatus) {
                return response()->json(['success' => true, 'message' => 'Status unchanged']);
            }

            $bill->status = $newStatus;
            $bill->save();

            $account = Account::where('user_id', auth()->id())->lockForUpdate()->find($bill->account_id);

            if ($account) {
                if ($oldStatus !== 'paid' && $newStatus === 'paid') {
                    $account->current_balance -= $bill->amount;
                } elseif ($oldStatus === 'paid' && $newStatus !== 'paid') {
                    $account->current_balance += $bill->amount;
                }
                $account->save();
            }

            return response()->json(['success' => true, 'message' => 'Status updated successfully']);
        });
    }

    public function getPending(): JsonResponse
    {
        $bills = Bill::where('user_id', auth()->id())
            ->with('account:id,name')
            ->where('status', 'pending')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($bill) {
                return [
                    'id'          => $bill->id,
                    'description' => $bill->description,
                    'amount'      => $bill->amount,
                    'date'        => $bill->date->format('Y-m-d'),
                    'status'      => $bill->status,
                    'accountId'   => $bill->account_id,
                    'accountName' => $bill->account->name ?? null,
                    'image'       => $bill->image,
                    'isMonthly'   => $bill->is_monthly,
                ];
            });

        return response()->json($bills);
    }

    public function getTotalByStatus(): JsonResponse
    {
        $totals = Bill::where('user_id', auth()->id())
            ->selectRaw('status, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'total' => (float) $item->total,
                    'count' => $item->count,
                ];
            });

        return response()->json($totals);
    }
}
