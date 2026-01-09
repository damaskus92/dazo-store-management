<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashierRequest;
use App\Http\Requests\UpdateCashierRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CashierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = User::with('role', 'store')
            ->where('store_id', $request->user()->store_id)
            ->whereHas('role', fn($q) => $q->where('name', 'cashier'));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $cashiers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $cashiers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCashierRequest $request)
    {
        DB::beginTransaction();

        try {
            $cashierRole = Role::where('name', 'cashier')->firstOrFail();

            $cashier = User::create([
                'store_id' => $request->user()->store_id,
                'role_id' => $cashierRole->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $cashier,
                'message' => 'Cashier has been successfully created.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create cashier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $cashier)
    {
        $this->authorizeCashier($cashier, $request);

        $cashier->load('role', 'store');

        return response()->json([
            'success' => true,
            'data' => $cashier,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCashierRequest $request, User $cashier)
    {
        $this->authorizeCashier($cashier, $request);

        $cashier->update($request->only([
            'first_name',
            'last_name',
            'email',
            'phone_number',
        ]));

        if ($request->filled('password')) {
            $cashier->update(['password' => Hash::make($request->password)]);
        }

        return response()->json([
            'success' => true,
            'data' => $cashier,
            'message' => 'Cashier has been successfully updated.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $cashier)
    {
        $this->authorizeCashier($cashier, $request);

        try {
            $cashier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cashier has been successfully deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cashier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ensure admin only manages cashiers in their store
     */
    protected function authorizeCashier(User $cashier, Request $request)
    {
        $cashier->load('role');

        $currentUser = $request->user();

        if (
            $cashier->store_id !== $currentUser->store_id ||
            ! $cashier->role ||
            $cashier->role->name !== 'cashier'
        ) {
            abort(403, 'You are not authorized to manage this cashier.');
        }
    }
}
