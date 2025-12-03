@if ($paginator->hasPages())
    <style>
        .modern-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .modern-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .modern-pagination .page-link:hover:not(.disabled):not(.active) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .modern-pagination .page-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            font-weight: 600;
        }
        
        .modern-pagination .page-link.disabled {
            color: #d1d5db;
            background: #f9fafb;
            border-color: #e5e7eb;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .modern-pagination .page-link.arrow {
            font-size: 16px;
            font-weight: 600;
        }
        
        .modern-pagination .page-link.dots {
            border: none;
            background: transparent;
            box-shadow: none;
            pointer-events: none;
            min-width: auto;
        }
        
        .modern-pagination .page-info {
            display: inline-flex;
            align-items: center;
            padding: 0 15px;
            font-size: 13px;
            color: #6b7280;
            background: #f3f4f6;
            border-radius: 20px;
            height: 32px;
            margin: 0 10px;
        }
        
        @media (max-width: 640px) {
            .modern-pagination {
                gap: 6px;
            }
            
            .modern-pagination .page-link {
                min-width: 36px;
                height: 36px;
                padding: 0 10px;
                font-size: 13px;
            }
            
            .modern-pagination .page-info {
                display: none;
            }
        }
    </style>
    
    <div class="modern-pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <a class="page-link arrow disabled">
                <i class="icon angle-left-icon"></i>
            </a>
        @else
            <a href="{{ urldecode($paginator->previousPageUrl()) }}" class="page-link arrow">
                <i class="icon angle-left-icon"></i>
            </a>
        @endif

        {{-- Page Info --}}
        <span class="page-info">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <a class="page-link dots">
                    {{ $element }}
                </a>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <a class="page-link active">
                            {{ $page }}
                        </a>
                    @else
                        <a class="page-link" href="{{ urldecode($url) }}">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ urldecode($paginator->nextPageUrl()) }}" class="page-link arrow">
                <i class="icon angle-right-icon"></i>
            </a>
        @else
            <a class="page-link arrow disabled">
                <i class="icon angle-right-icon"></i>
            </a>
        @endif
    </div>
@endif
