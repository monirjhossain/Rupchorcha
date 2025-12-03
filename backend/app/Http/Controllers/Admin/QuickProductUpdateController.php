<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class QuickProductUpdateController extends Controller
{
    /**
     * Show quick update page
     */
    public function index()
    {
        $imports = ProductImport::where('type', 'quick_update')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.products.quick-update.index', compact('imports'));
    }

    /**
     * Download quick update template
     */
    public function downloadTemplate()
    {
        $headers = [
            'sku',
            'price',
            'cost_price',
            'special_price',
            'quantity',
            'status',
            'featured',
            'new',
        ];

        $sampleData = [
            ['SKU001', '1500', '1000', '1200', '50', '1', '0', '1'],
            ['SKU002', '2500', '2000', '2200', '30', '1', '1', '0'],
            ['SKU003', '3500', '3000', '', '100', '1', '0', '0'],
        ];

        $filename = 'quick_update_template_' . date('Y-m-d') . '.csv';
        
        $callback = function() use ($headers, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Upload CSV file
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
                'type' => 'quick_update',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending',
                'total_rows' => $totalRows,
                'uploaded_by' => auth()->guard('admin')->id(),
            ]);

            return redirect()
                ->route('admin.products.quick-update.index')
                ->with('success', "File uploaded! {$totalRows} products ready to update.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Process quick update
     */
    public function process($id)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $import = ProductImport::findOrFail($id);

        if ($import->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'This update has already been processed.');
        }

        try {
            $import->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $csv = Reader::createFromPath(storage_path('app/public/' . $import->file_path));
            $csv->setHeaderOffset(0);
            
            // Clean headers
            $headers = $csv->getHeader();
            $headers = array_map('trim', $headers);
            $headers = array_map(function($h) {
                return str_replace("\xEF\xBB\xBF", '', $h);
            }, $headers);
            
            $records = $csv->getRecords($headers);

            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            $processedRows = 0;
            $notFoundSkus = [];

            foreach ($records as $index => $record) {
                try {
                    $processedRows++;
                    
                    if (empty($record['sku'])) {
                        $failedCount++;
                        $errors[] = "Row " . ($index + 2) . ": SKU is required";
                        continue;
                    }

                    // Find product by SKU
                    $product = Product::where('sku', $record['sku'])->first();
                    
                    if (!$product) {
                        $failedCount++;
                        $notFoundSkus[] = $record['sku'];
                        $errors[] = "Row " . ($index + 2) . ": SKU '{$record['sku']}' not found";
                        continue;
                    }

                    // Update only provided fields
                    $updateData = [];
                    
                    if (isset($record['price']) && $record['price'] !== '') {
                        $updateData['price'] = $record['price'];
                    }
                    if (isset($record['cost_price']) && $record['cost_price'] !== '') {
                        $updateData['cost_price'] = $record['cost_price'];
                    }
                    if (isset($record['special_price']) && $record['special_price'] !== '') {
                        $updateData['special_price'] = $record['special_price'];
                    }
                    if (isset($record['quantity']) && $record['quantity'] !== '') {
                        $updateData['quantity'] = $record['quantity'];
                    }
                    if (isset($record['status']) && $record['status'] !== '') {
                        $updateData['status'] = $record['status'];
                    }
                    if (isset($record['featured']) && $record['featured'] !== '') {
                        $updateData['featured'] = $record['featured'];
                    }
                    if (isset($record['new']) && $record['new'] !== '') {
                        $updateData['new'] = $record['new'];
                    }

                    if (!empty($updateData)) {
                        $product->update($updateData);
                        $successCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Row " . ($index + 2) . ": No valid fields to update";
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }

                $import->update([
                    'processed_rows' => $processedRows,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]);
            }

            $import->update([
                'status' => 'completed',
                'completed_at' => now(),
                'errors' => $errors,
            ]);

            $message = "Update completed! Success: {$successCount}, Failed: {$failedCount}";
            if (!empty($notFoundSkus)) {
                Log::warning("SKUs not found: " . implode(', ', $notFoundSkus));
            }

            return redirect()
                ->route('admin.products.quick-update.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
                'errors' => ['Process failed: ' . $e->getMessage()],
            ]);

            return redirect()
                ->route('admin.products.quick-update.index')
                ->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete import record
     */
    public function destroy($id)
    {
        try {
            $import = ProductImport::findOrFail($id);
            
            // Delete file
            if (Storage::disk('public')->exists($import->file_path)) {
                Storage::disk('public')->delete($import->file_path);
            }
            
            $import->delete();
            
            return redirect()
                ->route('admin.products.quick-update.index')
                ->with('success', 'Import record deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}
