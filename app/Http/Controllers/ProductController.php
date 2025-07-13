<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Jobs\ImportProductsJob;

class ProductController extends Controller
{
    public function __construct(private Product $product) {}

    public function index()
    {
        try {
            $products = $this->product->with('vendor')->get();
            return response()->json($products);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to fetch products', 'details' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'vendor_id' => 'required|exists:vendors,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
            ]);

            $product = $this->product->create([
                'id' => Str::uuid(),
                'vendor_id' => $request->vendor_id,
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
            ]);

            return response()->json(['message' => 'Product created', 'product' => $product], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to store product', 'details' => $e->getMessage()], 500);
        }
    }

    public function importCsv(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:5120'
            ]);

            $importDir = storage_path('app/imports');
            if (!file_exists($importDir)) {
                mkdir($importDir, 0775, true);
            }

            $filename = uniqid() . '.csv';
            $file = $request->file('file');
            $file->move($importDir, $filename);

            $relativePath = 'imports/' . $filename;
            $fullPath = $importDir . '/' . $filename;

            app(\Illuminate\Contracts\Queue\Queue::class)->push(new ImportProductsJob($relativePath));

            
            return response()->json(['message' => 'Import job dispatched.']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to dispatch job', 'details' => $e->getMessage()], 500);
        }
    }


    public function show(string $id)
    {
        try {
            $product = $this->product->with('vendor')->findOrFail($id);
            return response()->json($product);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Product not found', 'details' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
            ]);

            $product = $this->product->findOrFail($id);
            $product->update($request->only(['name', 'description', 'price', 'stock']));

            return response()->json(['message' => 'Product updated', 'product' => $product]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to update product', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $product = $this->product->findOrFail($id);
            $product->delete();

            return response()->json(['message' => 'Product deleted']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to delete product', 'details' => $e->getMessage()], 500);
        }
    }
}
