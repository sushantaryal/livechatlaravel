@extends('layouts.app')

@section('content')
    <chat-board :user="{{ auth()->user() }}"></chat-board>
@endsection
