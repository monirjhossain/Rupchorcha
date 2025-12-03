<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews
     */
    public function index(Request $request)
    {
        $query = ProductReview::with('product');
        
        // Search by product name, customer name, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhereHas('product', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Filter by rating
        if ($request->has('rating') && $request->rating !== '') {
            $query->where('rating', $request->rating);
        }
        
        $reviews = $query->latest()->paginate(15);
        
        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Show the review details
     */
    public function show($id)
    {
        $review = ProductReview::with('product')->findOrFail($id);
        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Approve a review
     */
    public function approve($id)
    {
        $review = ProductReview::findOrFail($id);
        $review->update(['status' => 'approved']);
        
        return redirect()->back()
                        ->with('success', 'Review approved successfully');
    }

    /**
     * Decline a review
     */
    public function decline($id)
    {
        $review = ProductReview::findOrFail($id);
        $review->update(['status' => 'declined']);
        
        return redirect()->back()
                        ->with('success', 'Review declined successfully');
    }

    /**
     * Remove the review
     */
    public function destroy($id)
    {
        $review = ProductReview::findOrFail($id);
        $review->delete();
        
        return redirect()->route('admin.reviews.index')
                        ->with('success', 'Review deleted successfully');
    }

    /**
     * Mass destroy reviews
     */
    public function massDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:custom_product_reviews,id'
        ]);
        
        ProductReview::whereIn('id', $request->ids)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Reviews deleted successfully'
        ]);
    }
    
    /**
     * Mass approve reviews
     */
    public function massApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:custom_product_reviews,id'
        ]);
        
        ProductReview::whereIn('id', $request->ids)->update(['status' => 'approved']);
        
        return response()->json([
            'success' => true,
            'message' => 'Reviews approved successfully'
        ]);
    }
    
    /**
     * Mass decline reviews
     */
    public function massDecline(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:custom_product_reviews,id'
        ]);
        
        ProductReview::whereIn('id', $request->ids)->update(['status' => 'declined']);
        
        return response()->json([
            'success' => true,
            'message' => 'Reviews declined successfully'
        ]);
    }
}
