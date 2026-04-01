@extends('layouts.app')

@section('content')
    <div class="page-header">
        <h1>Contact</h1>
        <p>Submit the form to generate a POST request. Validation errors and success messages are logged by ADP.</p>
    </div>

    @if ($success)
        <div class="alert alert-success">Your message has been sent successfully. Check the debug panel to see the logged data.</div>
    @endif

    @if (!empty($errors))
        <div class="alert alert-error">Please fix the errors below and try again.</div>
    @endif

    <div class="card">
        <form method="POST" action="/contact">
            @csrf

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" class="form-control {{ isset($errors['name']) ? 'error' : '' }}" value="{{ $data['name'] ?? '' }}">
                @if (isset($errors['name']))
                    <div class="form-error">{{ $errors['name'] }}</div>
                @endif
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control {{ isset($errors['email']) ? 'error' : '' }}" value="{{ $data['email'] ?? '' }}">
                @if (isset($errors['email']))
                    <div class="form-error">{{ $errors['email'] }}</div>
                @endif
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" class="form-control {{ isset($errors['message']) ? 'error' : '' }}">{{ $data['message'] ?? '' }}</textarea>
                @if (isset($errors['message']))
                    <div class="form-error">{{ $errors['message'] }}</div>
                @endif
            </div>

            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>
@endsection
