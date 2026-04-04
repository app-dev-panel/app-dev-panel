<h1>{{ $title }}</h1>
<p>Welcome, {{ $user }}</p>
@include('test.partials.header', ['siteName' => $title])
@include('test.partials.footer', ['year' => date('Y')])
