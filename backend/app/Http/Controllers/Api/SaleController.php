<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    /**
     * Display a listing of sales.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Sale::with(['items', 'payments', 'cashier']);

        // Filter by transaction number
        if ($search) {
            $query->where('transaction_number', 'like', "%{$search}%");
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $user = $request->user();

        // Cashier hanya boleh lihat sales di tokonya
        if ($user->isCashier()) {
            $query->where('store_id', $user->store_id);
        }

        // Admin bisa lihat semua sales di tokonya juga
        if ($user->isAdmin()) {
            $query->where('store_id', $user->store_id);
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    /**
     * Store a new sale (cart) in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();

            $sale = Sale::create([
                'transaction_number' => 'TRX-'.strtoupper(Str::random(8)),
                'store_id' => $user->store_id,
                'cashier_id' => $user->id,
                'total_amount' => 0,
                'paid_amount' => 0,
                'change_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $totalAmount += $subtotal;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);
            }

            $sale->update(['total_amount' => $totalAmount]);

            return response()->json([
                'success' => true,
                'data' => $sale->load('items'),
                'message' => 'Sale created successfully',
            ], 201);
        });
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale, Request $request)
    {
        $this->authorizeSaleView($sale, $request);

        $sale->load(['items', 'payments', 'cashier']);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    /**
     * Process payment for a sale.
     */
    public function pay(StorePaymentRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $sale = Sale::findOrFail($request->sale_id);

            $this->authorizeSalePay($sale, $request);

            $payment = Payment::create([
                'sale_id' => $sale->id,
                'method' => $request->method,
                'amount' => $request->amount,
            ]);

            $sale->paid_amount += $request->amount;
            $sale->change_amount = $sale->paid_amount - $sale->total_amount;
            $sale->save();

            return response()->json([
                'success' => true,
                'data' => $sale->load('payments', 'items'),
                'message' => 'Payment processed successfully',
            ]);
        });
    }

    /**
     * Authorization for viewing sale (admin & cashier)
     */
    protected function authorizeSaleView(Sale $sale, Request $request)
    {
        $user = $request->user();

        if (! in_array($user->role->name, ['admin', 'cashier']) || $sale->store_id !== $user->store_id) {
            abort(403, 'You are not authorized to view this sale.');
        }
    }

    /**
     * Authorization for paying a sale (only cashier)
     */
    protected function authorizeSalePay(Sale $sale, Request $request)
    {
        $user = $request->user();
        if (! $user->isCashier() || $sale->store_id !== $user->store_id) {
            abort(403, 'You are not authorized to pay this sale.');
        }
    }
}
