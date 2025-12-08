@extends('admin::layouts.master')

@section('page_title')
    System Logs
@stop

@section('content-wrapper')
    <div class="content full-page">
        <div class="page-header">
            <div class="page-title">
                <h1>System Logs</h1>
            </div>
            <div class="page-action">
                <a href="{{ route('admin.settings.logs.download') }}" class="btn btn-sm btn-primary">Download Log</a>
            </div>
        </div>
        <div class="page-content">
            <div class="card">
                <div class="card-title">Last 100 Log Entries</div>
                <div class="card-info" style="max-height: 500px; overflow-y: auto; background: #222; color: #eee; font-family: monospace; font-size: 13px; padding: 10px;">
                    @if(count($lines))
                        @foreach($lines as $line)
                            <div>{{ $line }}</div>
                        @endforeach
                    @else
                        <div>No log entries found.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
