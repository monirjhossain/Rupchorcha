<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Csv\Reader;

class BulkProductImportController extends Controller
{
    /**
     * Show bulk import page
     */
    public function index()
    {
        $imports = ProductImport::with('uploadedBy')
            ->latest()
            ->paginate(20);

        return view('admin.products.import.index', compact('imports'));
    }

    /**
     * Download sample CSV template
     */
    public function downloadTemplate()
    {
        $headers = [
            'name',
            'sku',
            'description',
            'short_description',
            'price',
            'cost_price',
            'special_price',
            'quantity',
            'weight',
            'status',
            'featured',
            'new',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'categories',
            'brands',
            'primary_image_url',
            'additional_image_url_1',
            'additional_image_url_2',
            'additional_image_url_3',
            'additional_image_url_4',
        ];

        $sampleData = [
            [
                'Sample Product',
                'SKU12345',
                'This is a detailed product description',
                'Short description here',
                '1500',
                '1000',
                '1200',
                '50',
                '0.5',
                '1',
                '0',
                '1',
                'Sample Product Meta Title',
                'Sample meta description',
                'sample, product, keywords',
                'Electronics,Smartphones',
                'Apple',
                'https://picsum.photos/800/800',
                'https://picsum.photos/800/801',
                'https://picsum.photos/800/802',
                '',
                '',
            ]
        ];

        $filename = 'product_import_template_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://temp', 'w+');
        
        fputcsv($handle, $headers);
        foreach ($sampleData as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Upload and validate CSV file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('csv_file');
            $path = $file->store('imports', 'public');

            // Read CSV and count rows
            $csv = Reader::createFromPath(storage_path('app/public/' . $path));
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
            $totalRows = iterator_count($records);

            // Create import record
            $import = ProductImport::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending',
                'total_rows' => $totalRows,
                'uploaded_by' => auth()->guard('admin')->id(),
            ]);

            return redirect()
                ->route('admin.products.import.index')
                ->with('success', "File uploaded successfully! {$totalRows} products ready to import.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Process the import
     */
    public function process($id)
    {
        // Increase execution time and memory
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');
        
        $import = ProductImport::findOrFail($id);

        if ($import->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'This import has already been processed or is currently processing.');
        }

        try {
            $import->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $csv = Reader::createFromPath(storage_path('app/public/' . $import->file_path));
            $csv->setHeaderOffset(0);
            
            // Get headers and trim them (remove BOM and spaces)
            $headers = $csv->getHeader();
            $headers = array_map('trim', $headers);
            $headers = array_map(function($h) {
                return str_replace("\xEF\xBB\xBF", '', $h); // Remove UTF-8 BOM
            }, $headers);
            
            $records = $csv->getRecords($headers);

            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            $processedRows = 0;
            $batchSize = 10; // Process in batches

            foreach ($records as $index => $record) {
                try {
                    $processedRows++;
                    
                    // Update progress every 10 rows
                    if ($processedRows % $batchSize === 0) {
                        $import->update([
                            'processed_rows' => $processedRows,
                            'success_count' => $successCount,
                            'failed_count' => $failedCount,
                        ]);
                    }
                    
                    // Check for duplicate SKU (update existing or create new)
                    $existingProduct = Product::where('sku', $record['sku'])->first();
                    
                    if ($existingProduct) {
                        // Update existing product
                        $existingProduct->update([
                            'name' => $record['name'],
                            'description' => $record['description'] ?? $existingProduct->description,
                            'short_description' => $record['short_description'] ?? $existingProduct->short_description,
                            'price' => floatval($record['price'] ?? $existingProduct->price),
                            'cost_price' => !empty($record['cost_price']) ? floatval($record['cost_price']) : $existingProduct->cost_price,
                            'special_price' => !empty($record['special_price']) ? floatval($record['special_price']) : $existingProduct->special_price,
                            'quantity' => intval($record['quantity'] ?? $existingProduct->quantity),
                            'weight' => !empty($record['weight']) ? floatval($record['weight']) : $existingProduct->weight,
                            'status' => intval($record['status'] ?? $existingProduct->status),
                            'featured' => intval($record['featured'] ?? $existingProduct->featured),
                            'new' => intval($record['new'] ?? $existingProduct->new),
                            'meta_title' => $record['meta_title'] ?? $existingProduct->meta_title,
                            'meta_description' => $record['meta_description'] ?? $existingProduct->meta_description,
                            'meta_keywords' => $record['meta_keywords'] ?? $existingProduct->meta_keywords,
                        ]);
                        
                        $product = $existingProduct;
                        $isUpdate = true;
                    } else {
                        // Generate unique slug for new product
                        $slug = Str::slug($record['name']);
                        $originalSlug = $slug;
                        $counter = 1;
                        
                        while (Product::where('slug', $slug)->exists()) {
                            $slug = $originalSlug . '-' . $counter;
                            $counter++;
                        }

                        // Create new product
                        $product = Product::create([
                            'name' => $record['name'],
                            'slug' => $slug,
                            'sku' => $record['sku'],
                            'description' => $record['description'] ?? '',
                            'short_description' => $record['short_description'] ?? '',
                            'price' => floatval($record['price']),
                            'cost_price' => !empty($record['cost_price']) ? floatval($record['cost_price']) : null,
                            'special_price' => !empty($record['special_price']) ? floatval($record['special_price']) : null,
                            'quantity' => intval($record['quantity'] ?? 0),
                            'weight' => !empty($record['weight']) ? floatval($record['weight']) : null,
                            'status' => intval($record['status'] ?? 1),
                            'featured' => intval($record['featured'] ?? 0),
                            'new' => intval($record['new'] ?? 0),
                            'meta_title' => $record['meta_title'] ?? '',
                            'meta_description' => $record['meta_description'] ?? '',
                            'meta_keywords' => $record['meta_keywords'] ?? '',
                        ]);
                        
                        $isUpdate = false;
                    }

                    // Attach categories (supports both IDs and names)
                    if (!empty($record['categories'])) {
                        $categories = array_map('trim', explode(',', $record['categories']));
                        $categoryIds = [];
                        
                        foreach ($categories as $category) {
                            // Check if it's an ID (numeric) or name (string)
                            if (is_numeric($category)) {
                                $categoryIds[] = $category;
                            } else {
                                // Find category by name or slug
                                $cat = \App\Models\Category::where('name', $category)
                                    ->orWhere('slug', Str::slug($category))
                                    ->first();
                                if ($cat) {
                                    $categoryIds[] = $cat->id;
                                }
                            }
                        }
                        
                        if (!empty($categoryIds)) {
                            $product->categories()->sync($categoryIds);
                        }
                    }

                    // Attach brands (supports both IDs and names)
                    if (!empty($record['brands'])) {
                        $brands = array_map('trim', explode(',', $record['brands']));
                        $brandIds = [];
                        $notFoundBrands = [];
                        
                        foreach ($brands as $brand) {
                            if (empty($brand)) continue;
                            
                            // Check if it's an ID (numeric) or name (string)
                            if (is_numeric($brand)) {
                                // Verify brand ID exists
                                if (\App\Models\Brand::where('id', $brand)->exists()) {
                                    $brandIds[] = $brand;
                                } else {
                                    $notFoundBrands[] = "ID:{$brand}";
                                }
                            } else {
                                // Find brand by name or slug (case-insensitive)
                                $br = \App\Models\Brand::whereRaw('LOWER(name) = ?', [strtolower($brand)])
                                    ->orWhereRaw('LOWER(slug) = ?', [strtolower(Str::slug($brand))])
                                    ->first();
                                if ($br) {
                                    $brandIds[] = $br->id;
                                } else {
                                    $notFoundBrands[] = $brand;
                                }
                            }
                        }
                        
                        if (!empty($brandIds)) {
                            $product->brands()->sync($brandIds);
                        }
                        
                        // Log if brands not found
                        if (!empty($notFoundBrands)) {
                            \Log::warning("Brands not found for product {$product->name}: " . implode(', ', $notFoundBrands));
                        }
                    }

                    // Handle images (only download if update or no existing images)
                    if (!$isUpdate || $product->images()->count() == 0) {
                        // Handle primary image (position 0) with timeout
                        if (!empty($record['primary_image_url'])) {
                            try {
                                $this->downloadAndSaveImage($product->id, $record['primary_image_url'], 0);
                            } catch (\Exception $e) {
                                // Continue even if image download fails
                            }
                        }

                        // Handle additional images (positions 1-4) with timeout
                        for ($i = 1; $i <= 4; $i++) {
                            $key = 'additional_image_url_' . $i;
                            if (!empty($record[$key])) {
                                try {
                                    $this->downloadAndSaveImage($product->id, $record[$key], $i);
                                } catch (\Exception $e) {
                                    // Continue even if image download fails
                                }
                            }
                        }
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }

                // Update progress
                $import->update([
                    'processed_rows' => $processedRows,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]);
            }

            $import->update([
                'status' => $failedCount > 0 ? 'completed' : 'completed',
                'errors' => $errors,
                'completed_at' => now(),
            ]);

            return redirect()
                ->route('admin.products.import.index')
                ->with('success', "Import completed! Success: {$successCount}, Failed: {$failedCount}");
        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'completed_at' => now(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download image from URL and save
     */
    private function downloadAndSaveImage($productId, $imageUrl, $position)
    {
        try {
            // Set timeout for image download (10 seconds max per image)
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true,
                ]
            ]);
            
            $imageContents = @file_get_contents($imageUrl, false, $context);
            
            if ($imageContents === false) {
                throw new \Exception('Failed to download image');
            }
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }
            
            $fileName = 'products/' . uniqid() . '.' . $extension;
            Storage::disk('public')->put($fileName, $imageContents);

            ProductImage::create([
                'product_id' => $productId,
                'path' => $fileName,
                'position' => $position,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the entire import
            \Log::error("Failed to download image: " . $imageUrl . " - " . $e->getMessage());
        }
    }

    /**
     * Delete import record
     */
    public function destroy($id)
    {
        $import = ProductImport::findOrFail($id);
        
        // Delete file
        if (Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }

        $import->delete();

        return redirect()
            ->back()
            ->with('success', 'Import record deleted successfully!');
    }
}
