<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    public function __construct(private Vendor $vendor) {}

    public function all()
    {
        try {
            $vendors = $this->vendor->all();
            return response()->json($vendors);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function index()
    {
        try {
            $vendors = $this->vendor->where('user_id', Auth::id())->get();
            return response()->json($vendors);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email',
                'address' => 'nullable|string',
            ]);

            $vendor = $this->vendor->create([
                'id' => Str::uuid(),
                'user_id' => Auth::id(),
                'company_name' => $request->company_name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
            ]);

            return response()->json(['message' => 'Vendor registered successfully', 'vendor' => $vendor], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $vendor = Vendor::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            return response()->json($vendor);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'company_name' => 'sometimes|required|string|max:255',
                'contact_person' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email',
                'address' => 'nullable|string',
            ]);

            $vendor = $this->vendor->findOrFail($id);
            $vendor->update($request->only(['company_name', 'contact_person', 'phone', 'email', 'address']));

            return response()->json(['message' => 'Vendor updated successfully', 'vendor' => $vendor]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $vendor = Vendor::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            $vendor->delete();

            return response()->json(['message' => 'Vendor deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
