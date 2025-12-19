<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSetting;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\Saving;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['branch', 'addedBy']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        $branches = Branch::active()->get();

        return view('admin.products.index', compact('products', 'branches'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $branches = Branch::active()->get();
        $accounts = \App\Models\SystemAccount::orderBy('code')->get();
        return view('admin.products.create', compact('branches', 'accounts'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|integer',
            'loan_type' => 'required|integer',
            'type' => 'required|integer',
            'period_type' => 'required|integer',
            'description' => 'nullable|string',
            'max_amt' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric|min:0|max:100',
            'cash_sceurity' => 'nullable|numeric|min:0',
            'code' => 'nullable|string|max:50',
            'isactive' => 'nullable|integer|in:0,1',
        ]);

        try {
            // Auto-generate product code if not provided
            // Format: BLN + timestamp (e.g., BLN1760086658)
            if (empty($validated['code'])) {
                $validated['code'] = 'BLN' . time();
            }
            
            // Handle icon upload - using FileStorageService (auto-uploads to DigitalOcean Spaces in production)
            if ($request->hasFile('icon')) {
                $validated['icon'] = FileStorageService::storeFile($request->file('icon'), 'product-icons');
            }

            $validated['added_by'] = auth()->id();

            $product = Product::create($validated);

            // Save product settings if provided
            if (!empty($validated['settings'])) {
                foreach ($validated['settings'] as $setting) {
                    ProductSetting::create([
                        'product_id' => $product->id,
                        'name' => $setting['name'],
                        'value' => $setting['value']
                    ]);
                }
            }

            return redirect()->route('admin.products.show', $product)
                            ->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        $product->load(['branch', 'addedBy', 'settings']);

        // Get product statistics
        $stats = [];
        
        if ($product->type === 'loan') {
            $stats = [
                'total_loans' => Loan::where('product_type', $product->id)->count(),
                'active_loans' => Loan::where('product_type', $product->id)->where('status', 2)->count(),
                'total_disbursed' => Loan::where('product_type', $product->id)->where('status', 2)->sum('principal'),
                'total_repaid' => Loan::where('product_type', $product->id)->sum('paid'),
                'pending_applications' => Loan::where('product_type', $product->id)->where('status', 0)->count(),
            ];
        } else {
            $stats = [
                'total_accounts' => Saving::where('product_id', $product->id)->count(),
                'active_accounts' => Saving::where('product_id', $product->id)->where('status', 1)->count(),
                'total_deposits' => Saving::where('product_id', $product->id)->sum('balance'),
                'pending_applications' => Saving::where('product_id', $product->id)->where('status', 0)->count(),
            ];
        }

        return view('admin.products.show', compact('product', 'stats'));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $branches = Branch::active()->get();
        $product->load('settings');
        
        return view('admin.products.edit', compact('product', 'branches'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'loan_type' => 'required|integer',
            'type' => 'required|integer',
            'period_type' => 'required|integer',
            'description' => 'nullable|string',
            'account' => 'required|integer',
            'max_amt' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric|min:0|max:100',
            'cash_sceurity' => 'nullable|numeric|min:0|max:100',
            'isactive' => 'nullable|integer|in:0,1',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
            'status' => 'nullable|integer|in:0,1',
            
            // Product settings
            'settings' => 'array',
            'settings.*.name' => 'required|string',
            'settings.*.value' => 'required|string',
        ]);

        try {
            // Handle icon upload - using permanent public storage
            if ($request->hasFile('icon')) {
                if ($product->icon && file_exists(public_path($product->icon))) {
                    unlink(public_path($product->icon));
                }
                $validated['icon'] = FileStorageService::storeFile($request->file('icon'), 'product-icons');
            }

            $product->update($validated);

            // Update product settings
            $product->settings()->delete(); // Remove existing settings
            
            if (!empty($validated['settings'])) {
                foreach ($validated['settings'] as $setting) {
                    ProductSetting::create([
                        'product_id' => $product->id,
                        'name' => $setting['name'],
                        'value' => $setting['value']
                    ]);
                }
            }

            return redirect()->route('admin.products.show', $product)
                            ->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        // Check if product is in use
        $loansCount = Loan::where('product_type', $product->id)->count();
        $savingsCount = Saving::where('product_id', $product->id)->count();

        if ($loansCount > 0 || $savingsCount > 0) {
            return redirect()->back()
                            ->with('error', 'Cannot delete product. It is currently in use.');
        }

        try {
            // Delete icon file if exists
            if ($product->icon) {
                Storage::disk('public')->delete($product->icon);
            }

            // Delete product settings
            $product->settings()->delete();

            $product->delete();

            return redirect()->route('admin.products.index')
                            ->with('success', 'Product deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                            ->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(Product $product)
    {
        $product->update([
            'status' => $product->status === 1 ? 0 : 1
        ]);

        $status = $product->status === 1 ? 'activated' : 'deactivated';

        return redirect()->back()
                        ->with('success', "Product {$status} successfully.");
    }

    /**
     * Get product details for AJAX
     */
    public function getDetails(Product $product)
    {
        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'min_amount' => $product->min_amount,
                'max_amount' => $product->max_amount,
                'min_period' => $product->min_period,
                'max_period' => $product->max_period,
                'default_interest' => $product->default_interest,
                'charges' => $product->charges,
                'description' => $product->description,
            ]
        ]);
    }

    /**
     * Get products by type for AJAX
     */
    public function getByType(Request $request)
    {
        $type = $request->type;
        
        $products = Product::where('type', $type)
                          ->where('status', 1)
                          ->get(['id', 'name', 'min_amount', 'max_amount', 'min_period', 'max_period', 'default_interest']);

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
}