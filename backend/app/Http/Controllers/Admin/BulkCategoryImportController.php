<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Csv\Reader;

class BulkCategoryImportController extends Controller
{
    /**
     * Show import page
     */
    public function index()
    {
        $imports = ProductImport::where('type', 'category')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.categories.import.index', compact('imports'));
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate()
    {
        $headers = [
            'name',
            'slug',
            'parent_slug',
            'description',
            'status',
            'position',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'image_url',
        ];

        $sampleData = [
            [
                'Electronics',
                'electronics',
                '',
                'Electronic items and gadgets',
                '1',
                '0',
                'Electronics - Buy Online',
                'Shop for electronics online',
                'electronics, gadgets, technology',
                'https://picsum.photos/400/400',
            ],
            [
                'Laptops',
                'laptops',
                'electronics',
                'Laptop computers',
                '1',
                '1',
                'Laptops - Best Deals',
                'Buy laptops at best prices',
                'laptops, computers, notebooks',
                'https://picsum.photos/400/401',
            ],
        ];

        $filename = 'category_import_template_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://temp', 'w+');
        
        fputcsv($handle, $headers);
        foreach ($sampleData as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $output = stream_get_contents($handle);
        fclose($handle);

        return response($output, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Upload and validate CSV
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('csv_file');
            $path = $file->store('imports', 'public');
            
            // Count rows
            $csv = Reader::createFromPath($file->getRealPath());
            $csv->setHeaderOffset(0);
            $totalRows = count($csv);

            // Create import record
            $import = ProductImport::create([
                'type' => 'category',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending',
                'total_rows' => $totalRows,
                'uploaded_by' => auth()->guard('admin')->id(),
            ]);

            return redirect()
                ->route('admin.categories.import.index')
                ->with('success', "File uploaded! Total {$totalRows} categories ready to import.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Process import
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
            $parentMap = []; // Store slug => id mapping

            // First pass: Create all categories without parent
            $categoriesToProcess = [];
            foreach ($records as $index => $record) {
                $categoriesToProcess[] = $record;
            }

            // Create categories
            foreach ($categoriesToProcess as $index => $record) {
                try {
                    $processedRows++;
                    
                    // Generate unique slug
                    $slug = !empty($record['slug']) ? Str::slug($record['slug']) : Str::slug($record['name']);
                    $originalSlug = $slug;
                    $counter = 1;
                    
                    while (Category::where('slug', $slug)->exists()) {
                        $slug = $originalSlug . '-' . $counter;
                        $counter++;
                    }

                    // Create category
                    $category = Category::create([
                        'name' => $record['name'],
                        'slug' => $slug,
                        'description' => $record['description'] ?? '',
                        'status' => $record['status'] ?? 1,
                        'position' => $record['position'] ?? 0,
                        'meta_title' => $record['meta_title'] ?? '',
                        'meta_description' => $record['meta_description'] ?? '',
                        'meta_keywords' => $record['meta_keywords'] ?? '',
                        'parent_id' => null, // Set later
                    ]);

                    // Store in map
                    $parentMap[$slug] = [
                        'id' => $category->id,
                        'parent_slug' => $record['parent_slug'] ?? '',
                    ];

                    // Handle image with timeout
                    if (!empty($record['image_url'])) {
                        try {
                            $this->downloadAndSaveImage($category->id, $record['image_url']);
                        } catch (\Exception $e) {
                            // Continue even if image download fails
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

            // Second pass: Set parent relationships
            foreach ($parentMap as $slug => $data) {
                if (!empty($data['parent_slug'])) {
                    $parentSlug = Str::slug($data['parent_slug']);
                    if (isset($parentMap[$parentSlug])) {
                        Category::where('id', $data['id'])->update([
                            'parent_id' => $parentMap[$parentSlug]['id']
                        ]);
                    }
                }
            }

            $import->update([
                'status' => 'completed',
                'errors' => $errors,
                'completed_at' => now(),
            ]);

            return redirect()
                ->route('admin.categories.import.index')
                ->with('success', "Import completed! Success: {$successCount}, Failed: {$failedCount}");
        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'completed_at' => now(),
            ]);

            return redirect()
                ->route('admin.categories.import.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download image from URL and save
     */
    private function downloadAndSaveImage($categoryId, $imageUrl)
    {
        try {
            // Set timeout for image download (10 seconds max)
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
            
            $fileName = 'categories/' . uniqid() . '.' . $extension;
            Storage::disk('public')->put($fileName, $imageContents);

            Category::where('id', $categoryId)->update(['image' => $fileName]);
        } catch (\Exception $e) {
            \Log::error("Failed to download category image: " . $imageUrl . " - " . $e->getMessage());
        }
    }

    /**
     * Delete import record
     */
    public function destroy($id)
    {
        $import = ProductImport::findOrFail($id);
        
        if (Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }

        $import->delete();

        return redirect()
            ->route('admin.categories.import.index')
            ->with('success', 'Import record deleted!');
    }
}
