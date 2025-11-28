<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('account')
            ->where('user_id', auth()->id());

        $from = $request->input('from');
        $to = $request->input('to');
        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
        }

        if ($request->filled('supplier_name')) {
            $query->where('supplier_name', 'like', '%' . $request->supplier_name . '%');
        }

        return response()->json($query->orderByDesc('date')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'supplier_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
            'invoice_image' => 'nullable|image',
        ]);

        $account = Account::where('user_id', auth()->id())
            ->lockForUpdate()
            ->findOrFail($data['account_id']);

        return DB::transaction(function () use ($data, $account, $request) {
            $path = null;
            if ($request->hasFile('invoice_image')) {
                $path = $request->file('invoice_image')->store('purchase_invoices', 'public');
            }

            $purchase = Purchase::create([
                'user_id' => auth()->id(),
                'account_id' => $account->id,
                'date' => $data['date'],
                'supplier_name' => $data['supplier_name'] ?? null,
                'reference' => $data['reference'] ?? null,
                'total_amount' => $data['total_amount'],
                'note' => $data['note'] ?? null,
                'invoice_image_path' => $path,
            ]);

            $account->decrement('current_balance', $data['total_amount']);

            return response()->json($purchase, 201);
        });
    }

    public function show($id)
    {
        $purchase = Purchase::with('account')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($purchase);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate([
            'date' => 'sometimes|date',
            'account_id' => 'sometimes|exists:accounts,id',
            'supplier_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'total_amount' => 'sometimes|numeric|min:0.01',
            'note' => 'nullable|string',
            'invoice_image' => 'nullable|image',
        ]);

        return DB::transaction(function () use ($data, $request, $purchase) {
            $oldAccount = $purchase->account()->lockForUpdate()->first();
            $oldAmount = $purchase->total_amount;

            // Revert previous balance impact
            $oldAccount->increment('current_balance', $oldAmount);

            $account = $oldAccount;
            if (isset($data['account_id'])) {
                $account = Account::where('user_id', auth()->id())
                    ->lockForUpdate()
                    ->findOrFail($data['account_id']);
            }

            if ($request->hasFile('invoice_image')) {
                if ($purchase->invoice_image_path) {
                    Storage::disk('public')->delete($purchase->invoice_image_path);
                }
                $data['invoice_image_path'] = $request->file('invoice_image')->store('purchase_invoices', 'public');
            }

            $purchase->update(array_merge($data, ['account_id' => $account->id]));

            $account->decrement('current_balance', $purchase->total_amount);

            return response()->json($purchase);
        });
    }
}
