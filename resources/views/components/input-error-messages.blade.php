<!-- Errors Message from session. e.g  'message' => 'this Exception messages too will be passed to the toastr' -->
@if ($errors->any())
    <br>
    @foreach ($errors->all() as $error)
        @php
            toastr()->error($error); // pass error(s) to the toastr Library
        @endphp
    @endforeach


@endif
