<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Store::with('parent');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $stores = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        try {
            $store = Store::create($request->only(['parent_id', 'name', 'level', 'address', 'phone']));

            $adminRole = Role::where('name', 'admin')->firstOrFail();
            $cashierRole = Role::where('name', 'cashier')->firstOrFail();

            User::create([
                'store_id' => $store->id,
                'role_id' => $adminRole->id,
                'first_name' => 'Admin',
                'last_name' => $store->name,
                'email' => strtolower('admin@'.Str::slug($store->name).'.com'),
                'phone_number' => null,
                'password' => Hash::make('password123'),
            ]);

            User::create([
                'store_id' => $store->id,
                'role_id' => $cashierRole->id,
                'first_name' => 'Cashier',
                'last_name' => $store->name,
                'email' => strtolower('cashier@'.Str::slug($store->name).'.com'),
                'phone_number' => null,
                'password' => Hash::make('password123'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $store,
                'message' => 'Store has been successfully created.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create store: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        $store->load(['parent', 'users.role', 'users.store.parent']);

        return response()->json([
            'success' => true,
            'data' => $store,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        // Parent store cannot be itself
        if ($request->parent_id && $request->parent_id === $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'Parent store cannot be itself.',
            ], 422);
        }

        $store->update($request->only(['name', 'level', 'address', 'phone', 'parent_id']));

        return response()->json([
            'success' => true,
            'data' => $store,
            'message' => 'Store has been successfully updated.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        try {
            $store->delete();

            return response()->json([
                'success' => true,
                'message' => 'Store has been successfully deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete store: '.$e->getMessage(),
            ], 500);
        }
    }
}
