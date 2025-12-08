@extends('admin::layouts.content')

@section('page_title')
    {{ __('admin::app.brands.edit-title') }}
@stop

@section('content')
    <div class="content" style="padding: 20px;">
        <form method="POST" action="{{ route('admin.brands.update', $brand->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="page-header">
                <div class="page-title">
                    <h1>
                        <i class="icon angle-left-icon back-link" onclick="window.location = '{{ route('admin.brands.index') }}'"></i>
                        {{ __('admin::app.brands.edit-title') }}
                    </h1>
                </div>

                <div class="page-action">
                    <button type="submit" class="btn btn-lg btn-primary">
                        {{ __('admin::app.brands.save-btn-title') }}
                    </button>
                </div>
            </div>

            <div class="page-content">
                <div class="form-container">
                    <div class="control-group" :class="{'has-error': errors.has('name')}">
                        <label for="name" class="required">{{ __('admin::app.brands.name') }}</label>
                        <input type="text" class="control" name="name" value="{{ old('name', $brand->name) }}" v-validate="'required'" data-vv-as="&quot;{{ __('admin::app.brands.name') }}&quot;">
                        <span class="control-error" v-if="errors.has('name')">@{{ errors.first('name') }}</span>
                    </div>

                    <div class="control-group">
                        <label for="slug">{{ __('admin::app.brands.slug') }}</label>
                        <input type="text" class="control" name="slug" value="{{ old('slug', $brand->slug) }}">
                        <span class="control-info">{{ __('admin::app.brands.slug-info') }}</span>
                    </div>

                    <div class="control-group">
                        <label for="description">{{ __('admin::app.brands.description') }}</label>
                        <textarea class="control" name="description">{{ old('description', $brand->description) }}</textarea>
                    </div>

                    <div class="control-group">
                        <label for="logo">{{ __('admin::app.brands.logo') }}</label>
                        <input type="file" class="control" name="logo">
                        @if ($brand->logo)
                            <img src="{{ asset('storage/' . $brand->logo) }}" alt="Brand Logo" style="width: 100px; height: auto; margin-top: 10px;">
                        @endif
                        <span class="control-info">{{ __('admin::app.brands.logo-info') }}</span>
                    </div>

                    <div class="control-group">
                        <label for="website">{{ __('admin::app.brands.website') }}</label>
                        <input type="url" class="control" name="website" value="{{ old('website', $brand->website) }}">
                    </div>

                    <div class="control-group">
                        <label for="position">{{ __('admin::app.brands.position') }}</label>
                        <input type="number" class="control" name="position" value="{{ old('position', $brand->position) }}">
                    </div>

                    <div class="control-group">
                        <label for="status">{{ __('admin::app.brands.status') }}</label>
                        <select class="control" name="status">
                            <option value="1" {{ $brand->status ? 'selected' : '' }}>{{ __('admin::app.brands.active') }}</option>
                            <option value="0" {{ !$brand->status ? 'selected' : '' }}>{{ __('admin::app.brands.inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop