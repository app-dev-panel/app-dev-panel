@extends('layouts.app')

@section('content')
    <div class="page-header">
        <h1>ADP Laravel Playground</h1>
        <p>A demo application for exploring the Application Development Panel with Laravel 12.</p>
    </div>

    <div class="grid grid-2">
        <a href="/users" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>Users</h3>
            <p>Browse a list of demo users displayed in a table. Triggers log collection and page rendering.</p>
        </a>

        <a href="/contact" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>Contact Form</h3>
            <p>Submit a contact form with validation. Demonstrates POST requests, CSRF protection, and error handling.</p>
        </a>

        <a href="/api-playground" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>API Playground</h3>
            <p>Send HTTP requests to the built-in JSON API endpoints and inspect the responses in real time.</p>
        </a>

        <a href="/error" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>Error Demo</h3>
            <p>Trigger a runtime exception to see how ADP captures and displays error details.</p>
        </a>

        <a href="/log-demo" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>Log Demo</h3>
            <p>Send log messages with different severity levels and context data.</p>
        </a>

        <a href="/var-dumper" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>Var Dumper</h3>
            <p>Dump variables to inspect their structure in ADP.</p>
        </a>

        <a href="/debug/" class="feature-card" style="text-decoration: none; color: inherit;">
            <h3>Debug Panel</h3>
            <p>Open the ADP debug panel to inspect collected logs, events, requests, and exceptions.</p>
        </a>
    </div>
@endsection
